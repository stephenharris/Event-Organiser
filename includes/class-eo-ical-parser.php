<?php

/**
 * Parses a local or remote ICAL file
 * 
 * Example usage
 * <code>
 *      $ical = new EO_ICAL_Parser();
 *      $ical->parse( 'http://www.dol.govt.nz/er/holidaysandleave/publicholidays/publicholidaydates/ical/auckland.ics' );
 *      
 *      $ical->events; //Array of events
 *      $ical->venues; //Array of venue names
 *      $ical->categories; //Array of category names
 *      $ical->errors; //Array of WP_Error errors
 *      $ical->warnings; //Array of WP_Error 'warnings'. This are "non-fatal" errors (e.g. warnings about timezone 'guessing').
 * </code>
 * 
 * You can configire default settings by passing an array to the class constructor.
 * <code>
 *      $ical = new EO_ICAL_Parser( array( ..., 'default_status' => 'published', ... ) );
 * </code>
 * Available settings include:
 * 
 *  *  **status_map** - How to interpret the ICAL STATUS property.
 *  *  **default_status** - Default status of posts (unless otherwise specified by STATUS). Default is 'draft'
 * 
 * @link http://www.ietf.org/rfc/rfc2445.txt ICAL Specification 
 * @link http://www.kanzaki.com/docs/ical/ ICAL Specification excerpts
 * @author stephen
 * @package ical-functions
 *
 */
class EO_ICAL_Parser{

	var $remote_timeout = 10;
	
	var $events = array();
	var $venues = array();
	var $categories = array();
	var $errors = array();
	var $warnings = array();

	var $events_parsed = 0;
	var $venue_parsed = 0;
	var $categories_parsed = 0;

	var $current_event = array();
	
	var $line = 0; //Current line being parsed

	/**
	 * Constructor with settings passed as arguments
	 * Available options include 'status_map' and 'default_status'.
	 * 
	 * @param array $args
	 */
	function __construct( $args = array() ){

		$args = array_merge( array(
					'status_map' => array(
						'CONFIRMED' => 'publish',
						'CANCELLED' => 'trash',
						'TENTATIVE' => 'draft',
					),
					'default_status' => 'draft',		
				), $args );
		

		$this->calendar_timezone = eo_get_blog_timezone();
		
		$this->default_status = $args['default_status'];
		$this->status_map = $args['status_map'];
	}


	/**
	 * Parses the given $file. Returns WP_Error on error.
	 * 
	 * @param string $file Path to iCal file or an url to an ical file
	 * @return bool|WP_Error. True if parsed. Returns WP_Error on error;
	 */
	function parse( $file ){
		
		//Local file
		if( is_file($file) && file_exists($file)  ){
			$this->ical_array = $this->file_to_array( $file );

		//Remote file
		}elseif( preg_match('!^(http|https|ftp)://!i', $file) ){
			$this->ical_array = $this->url_to_array( $file );

		}else{
			$this->ical_array =  WP_Error( 'invalid-ical-source', 
				__( 'There was an error detecting ICAL source.', 'eventorgansier' )
				);
		}

		if( is_wp_error( $this->ical_array ) )
			return $this->ical_array;

		//Go through array and parse events
		$result = $this->parse_ical_array();

		$this->events_parsed = count( $this->events );
		$this->venue_parsed = count( $this->venues );
		$this->categories_parsed = count( $this->categories );
		
		return true;
	}
	
	/**
	 * Fetches ICAL calendar from a feed url and returns its contents as an array.
	 * 
	 * @ignore
	 * @param sring $url The url of the ICAL feed 
	 * @return array|bool Array of line in ICAL feed, false on error 
	 */
	protected function url_to_array( $url ){
		$response =  wp_remote_get( $url, array( 'timeout' => $this->remote_timeout ) );
		$contents = wp_remote_retrieve_body( $response );
		$response_code = wp_remote_retrieve_response_code( $response );
		
		if( $response_code != 200 ){
			return new WP_Error( 'unable-to-fetch',
					sprintf(
							'%s. Response code: %s.',
							wp_remote_retrieve_response_message( $response ),
							$response_code
					));
		}
		
		if( $contents )
			return explode( "\n", $contents );
		
		if( is_wp_error( $response ) )
			return $response;
		
		return new WP_Error( 'unable-to-fetch', 
				sprintf( 
					__( 'There was an error fetching the feed. Response code: %s.', 'eventorgansier' ),
					$response_code
				));
	}

	/**
	 * Fetches ICAL calendar from a file and returns its contents as an array.
	 *
	 * @ignore
	 * @param sring $url The ICAL file
	 * @return array|bool Array of line in ICAL feed, false on error
	 */
	protected function file_to_array( $file ){

		$file_handle = @fopen( $file, "rb");
		$lines = array();

		if( !$file_handle )
			return new WP_Error( 
						'unable-to-open', 
					__( 'There was an error opening the ICAL file.', 'eventorgansier' )
					);

		//Feed lines into array
		while (!feof( $file_handle ) ):
			$line_of_text = fgets( $file_handle, 4096 );
			$lines[]= $line_of_text;
		endwhile;

		fclose($file_handle);

		return $lines;
	}


	/**
	 * Parses through an array of lines (of an ICAL file)
	 * @ignore
	 */
	protected function parse_ical_array(){

		$state = "NONE";//Initial state
		$this->line = 1;

		//Read through each line
		for ( $this->line = 1; $this->line <= count ( $this->ical_array ) && empty( $this->errors ); $this->line++ ):
			$buff = trim(  $this->ical_array[$this->line-1] );

			if( !empty( $buff ) ):
				$line = explode(':',$buff,2);

				//On the right side of the line we may have DTSTART;TZID= or DTSTART;VALUE=
				$modifiers = explode( ';', $line[0] );
				$property = array_shift( $modifiers );
				$value = ( isset( $line[1] ) ? trim( $line[1] ) : '' );

				//If we are in EVENT state
				if ( $state == "VEVENT" ) {

					//If END:VEVENT, add event to parsed events and clear $event
					if( $property=='END' && $value=='VEVENT' ){
						$state = "VCALENDAR";
						$this->events[] = $this->current_event;
						$this->current_event = array();

					//Otherwise, parse event property
					}else{
						try{
							while( isset( $this->ical_array[$this->line] ) && $this->ical_array[$this->line-1][0] == ' ' ){
								//Remove initial white space {@link http://www.ietf.org/rfc/rfc2445.txt Section 4.1}
								$value .= substr( $this->ical_array[$this->line-1], 1 );
								$this->line++;
							}
						
							$this->parse_event_property( $property, $value, $modifiers );

						}catch( Exception $e ){
							$this->report_error( $this->line, 'event-property-error', $e->getMessage() );
							$state = "VCALENDAR";//Abort parsing event
						}
					}

				// If we are in CALENDAR state
				}elseif ($state == "VCALENDAR") {

					//Begin event
					if( $property=='BEGIN' && $value=='VEVENT'){
						$state = "VEVENT";
						$this->current_event = array();

					}elseif ( $property=='END' && $value=='VCALENDAR'){
						$state = "NONE";
		
					}elseif($property=='X-WR-TIMEZONE'){
						$this->calendar_timezone = $this->parse_timezone($value);
					}

				//Other
				}elseif($state == "NONE" && $property=='BEGIN' && $value=='VCALENDAR') {
					$state = "VCALENDAR";
				}
			endif; //If line is not empty
		endfor; //For each line
	}


	/**
	 * Report an error with an iCal file
	 * @ignore
	 * @param int $line The line on which the error occurs.
	 * @param string $type The type of error
	 * @param string $message Verbose error message
	 */
	protected function report_error( $line, $type, $message ){

		$this->errors[] = new WP_Error(
				$type,
				sprintf( __( '[Line %1$d]', 'eventorganiser' ), $line ).' '.$message
		);
	}
	
	/**
	 * Report an warnings with an iCal file
	 * @ignore
	 * @param int $line The line on which the error occurs.
	 * @param string $type The type of error
	 * @param string $message Verbose error message
	 */
	protected function report_warning( $line, $type, $message ){
	
		$this->warnings[] = new WP_Error(
				$type,
				sprintf( __( '[Line %1$d]', 'eventorganiser' ), $line ).' '.$message
		);
	}


	/**
	 * @ignore
	 */
	protected function parse_event_property( $property, $value, $modifiers ){

		if( !empty( $modifiers ) ):
			foreach( $modifiers as $modifier ):
				if ( stristr( $modifier, 'TZID' ) ){
			
					$date_tz = $this->parse_timezone( substr( $modifier, 5 ) );

				}elseif( stristr( $modifier, 'VALUE' ) ){
					$meta = substr( $modifier, 6 );
				}
			endforeach;
		endif;

		//For dates - if there is not an associated timezone, use calendar default.
		if( empty( $date_tz ) )
			$date_tz = $this->calendar_timezone;

		switch( $property ):
		case 'UID':
			$this->current_event['uid'] = $value;
		break;

		case 'CREATED':
		case 'DTSTART':
		case 'DTEND':
			if( isset( $meta ) && $meta == 'DATE' ):
				$date = $this->parse_ical_date( $value );
				$allday = 1;
			else:
				$date = $this->parse_ical_datetime( $value, $date_tz );
				$allday = 0;
			endif;

			if( empty( $date ) )
				break;

			switch( $property ):
				case'DTSTART':
					$this->current_event['start'] = $date;
					$this->current_event['all_day'] = $allday;
				break;

				case 'DTEND':
					if( $allday == 1 )
						$date->modify('-1 second');
					$this->current_event['end'] = $date;
				break;

				case 'CREATED':
					$date->setTimezone( new DateTimeZone('utc') );
					$this->current_event['post_date_gmt'] = $date->format('Y-m-d H:i:s');
				break;

			endswitch;
		break;

		case 'EXDATE':
		case 'RDATE':
			//The modifiers have been dealt with above. We do similiar to above, except for an array of dates...
			$value_array = explode( ',', $value );

			//Note, we only consider the Date part and ignore the time
			foreach( $value_array as $val ):
				$date = $this->parse_ical_date( $val );
				
				if( $property == 'EXDATE' ){
					$this->current_event['exclude'][] = $date;
				}else{
					$this->current_event['include'][] = $date;
				}
			endforeach;
		break;

			//Reoccurrence rule properties
		case 'RRULE':
			$this->current_event += $this->parse_RRule($value);
		break;

			//The event's summary (AKA post title)
		case 'SUMMARY':
			$this->current_event['post_title'] = $this->parse_ical_text( $value );
		break;

			//The event's description (AKA post content)
		case 'DESCRIPTION':
			$this->current_event['post_content'] = $this->parse_ical_text( $value );
		break;

			//Event venues, assign to existing venue - or if set, create new one
		case 'LOCATION':
			if( !empty( $value ) ):

			$venue_name = trim($value);
				
			if( !isset( $this->venues[$venue_name] ) )
				$this->venues[$venue_name] = $venue_name;
				
			$this->current_event['event-venue'] = $venue_name;
			endif;
		break;

		case 'CATEGORIES':
			$cats = explode( ',', $value );
			
			if( !empty( $cats ) ):

			foreach ($cats as $cat_name):
				$cat_name = trim($cat_name);

				if( !isset( $this->categories[$cat_name] ) )
					$this->categories[$cat_name] = $cat_name;
				
				if( !isset($this->current_event['event-category']) || !in_array( $cat_name, $this->current_event['event-category']) )
					$this->current_event['event-category'][] = $cat_name;
				
			endforeach;

			endif;
		break;

			//The event's status
		case 'STATUS':
			$map = $this->status_map;

			$this->current_event['post_status'] = isset( $map[$value] ) ? $map[$value] : $this->default_status;
		break;

			//An url associated with the event
		case 'URL':
			$this->current_event['url'] = $value;
		break;

			endswitch;

	}


	/**
	 * Takes escaped text and returns the text unescaped.
	 * 
	 * @ignore
	 * @param string $text - the escaped test
	 * @return string $text - the text, unescaped.
	 */
	protected function parse_ical_text($text){
		//Get rid of carriage returns:
		$text = str_replace("\r\n","\n",$text);
		$text = str_replace("\r","\n",$text);

		//Some calendar apps break up text
		$text = str_replace("\n ","",$text);
		$text = str_replace("\r ","",$text);

		//Any intended carriage returns/new-lines converted to HTML
		$text = str_replace("\\r\\n","",$text);
		$text = str_replace("\\n","</br>",$text);
		$text = stripslashes($text);
		return $text;
	}

	/**
	 * Takes a date-time in ICAL and returns a datetime object
	 * @ignore
	 * @param string $tzid - the value of the ICAL TZID property
	 * @return DateTimeZone - the timezone with the given identifier or false if it isn't recognised
	 */
	protected function parse_timezone( $tzid ){
		
		$tzid = str_replace( '-', '/', $tzid );

		//Try just using the passed timezone ID
		try{
			$tz = new DateTimeZone( $tzid );
		}catch( exception $e ){
			$tz = null;
		}

		//If we have something like (GMT+01.00) Amsterdam / Berlin / Bern / Rome / Stockholm / Vienna lets try the cities
		if( is_null( $tz ) && preg_match( '/GMT(?P<offset>.+)\)(?P<cities>.+)/', $tzid, $matches ) ){
			
			$parts = explode( '/', $matches['cities'] );
			$tz_cities = array_map( 'trim', $parts );
			$identifiers = timezone_identifiers_list();
			
			foreach( $tz_cities as $tz_city ){
			
				$tz_city = ucfirst( strtolower( $tz_city ) );
			
				foreach( $identifiers as $identifier ){
			
					$parts = explode('/', $identifier );
					$city = array_pop( $parts );
						
					if( $city != $tz_city )
						continue;
			
					try{
						$tz = new DateTimeZone( $identifier );
						break 2;
					}catch( exception $e ){
						$tz = null;
					}
				}
			}
		}	

		//Let plugins over-ride this
		$tz = apply_filters( 'eventorganiser_ical_timezone', $tz, $tzid );
		
		if ( ! ($tz instanceof DateTimeZone ) ) {
			$tz = eo_get_blog_timezone();
		}
		
		if( $tz->getName() != $tzid ){
			$this->report_warning( 
				$this->line, 
				'timezone-parser-warning', 
				sprintf( "Unknown timezone %s interpretted as %s.", $tzid, $tz->getName() )
			);
		}
		
		return $tz;
	}


	
	/**
	 * Takes a date in ICAL and returns a datetime object
	 * 
	 * Expects date in yyyymmdd format
	 * @ignore
	 * @param string $ical_date - date in ICAL format
	 * @return DateTime - the $ical_date as DateTime object
	 */
	protected function parse_ical_date( $ical_date ){

		preg_match('/^(\d{8})*/', $ical_date, $matches);

		if( count( $matches ) !=2 ){
			throw new Exception(__('Invalid date. Date expected in YYYYMMDD format.','eventorganiser'));
		}

		//No time is given, so ignore timezone. (So use blog timezone).
		$datetime = new DateTime( $matches[1], eo_get_blog_timezone() );

		return $datetime;
	}

	/**
	 * Takes a date-time in ICAL and returns a datetime object
	 * 
	 * It returns the datetime in the specified 
	 * 
	 * Expects
	 *  * utc:  YYYYMMDDTHHiissZ
	 *  * local:  YYYYMMDDTHHiiss
	 *  
	 * @ignores
	 * @param string $ical_date - date-time in ICAL format
	 * @param DateTimeZone $tz - Timezone 'local' is interpreted as
	 * @return DateTime - the $ical_date as DateTime object
	 */
	protected function parse_ical_datetime( $ical_date, $tz ){
		
		preg_match('/^((\d{8}T\d{6})(Z)?)/', $ical_date, $matches);

		if( count( $matches ) == 3 ){
			//floating / local date

		}elseif( count($matches) == 4 ){
			$tz = new DateTimeZone('UTC');

		}else{
			throw new Exception(__('Invalid datetime. Date expected in YYYYMMDDTHHiissZ or YYYYMMDDTHHiiss format.','eventorganiser'));
			return false;
		}

		$datetime = new DateTime( $matches[2], $tz );

		return $datetime;
	}

	/**
	 * Takes a date-time in ICAL and returns a datetime object

	 * @since 1.1.0
	 * @ignore
	 * @param string $RRule - the value of the ICAL RRule property
	 * @return array - a reoccurrence rule array as understood by Event Organiser
	 */
	protected function parse_RRule($RRule){
		//RRule is a sequence of rule parts seperated by ';'
		$rule_parts = explode(';',$RRule);

		foreach ($rule_parts as $rule_part):

		//Each rule part is of the form PROPERTY=VALUE
		$prop_value =  explode('=',$rule_part, 2);
		$property = $prop_value[0];
		$value = $prop_value[1];

		switch($property):
			case 'FREQ':
				$rule_array['schedule'] =strtolower($value);
			break;

			case 'INTERVAL':
				$rule_array['frequency'] =intval($value);
			break;

			case 'UNTIL':
				//Is the scheduled end a date-time or just a date?
				if(preg_match('/^((\d{8}T\d{6})(Z)?)/', $value))
					$date = $this->parse_ical_datetime( $value, new DateTimeZone('UTC') );
				else
					$date = $this->parse_ical_date( $value );
			
				$rule_array['schedule_last'] = $date;
			break;
			
			case 'COUNT':
				$rule_array['number_occurrences'] = absint( $value );
			break;

			case 'BYDAY':
				$byday = $value;
			break;

			case 'BYMONTHDAY':
				$bymonthday = $value;
			break;
			
			endswitch;

		endforeach;

		//Meta-data for Weekly and Monthly schedules
		if( $rule_array['schedule']=='monthly' ):
			if( isset( $byday ) ){
				preg_match('/(\d+)([a-zA-Z]+)/', $byday, $matches);
				$rule_array['schedule_meta'] ='BYDAY='.$matches[1].$matches[2];

			}elseif( isset( $bymonthday ) ){
				$rule_array['schedule_meta'] ='BYMONTHDAY='.$bymonthday;

			}else{
				throw new Exception('Incomplete scheduling information');
			}

		elseif( $rule_array['schedule'] == 'weekly' ):
			preg_match( '/([a-zA-Z,]+)/', $byday, $matches );
			$rule_array['schedule_meta'] = explode(',',$matches[1]);

		endif;

		//If importing indefinately recurring, recurr up to some large point in time.
		//TODO make a log of this somewhere.
		if( empty( $rule_array['schedule_last'] ) && empty( $rule_array['number_occurrences'] ) ){
			$rule_array['schedule_last'] = new DateTime( '2038-01-19 00:00:00' );
			
			$this->report_warning(
				$this->line,
				'indefinitely-recurring-event',
				"Feed contained an indefinitely recurring event. This event will recurr until 2038-01-19."
			);
		}
		
		return $rule_array;
	}

}