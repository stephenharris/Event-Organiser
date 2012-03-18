<?php
/**
* Event importer / exporter
 */
if ( ! function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a part of plugin, not much I can do when called directly.";
	exit;
}
class Event_Organiser_Im_Export  {

	static private $classobj = NULL;

	/**
	 * Handler for the action 'init'. Instantiates this class.
	 */
	public function get_object() {
		
		if ( NULL === self :: $classobj ) {
			self :: $classobj = new self;
		}

		return self :: $classobj;
	}
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $pagenow, $EO_Errors;

		if(!isset($EO_Errors)) $EO_Errors = new WP_Error();

		$feed = get_query_var('feed');
		if(isset($feed) &&$feed=='eo-events'):
			$this->get_export_file();
		endif;

		/*		
		*If importing / exporting events make sure we a logged in and check nonces.
*		*/
		//Exporting events
		if (is_admin() && isset( $_GET['eventorganiser_download_events'] )&& check_admin_referer( 'eventorganiser_export' )
			&& current_user_can('manage_options')&& $pagenow=="options-general.php"):
			$this->get_export_file();
		endif;

		if(isset($_REQUEST['action']) &&$_REQUEST['action']=='eventorganiser_calendar_scrape' ){
			$this->get_export_file();
		}


		//Importing events
		if ( is_admin() && isset( $_POST['eventorganiser_import_events'] )&& check_admin_referer( 'eventorganiser_import' )
			&& current_user_can('manage_options')&& $pagenow=="options-general.php"):
		

			//Perform checks on file:
			if (($_FILES["ics"]["type"] == "text/calendar")&& ($_FILES["ics"]["size"] < 2000000)):
				if($_FILES["ics"]["error"] > 0){
					$EO_Errors = new WP_Error('eo_error', sprintf(__("File Error encountered: %d"), $_FILES["ics"]["error"]));
				}else{
					//Import file
					$this->import_file($_FILES['ics']['tmp_name']);
  				}
			elseif(!isset($_FILES) || empty($_FILES['ics']['name'])):
				$EO_Errors = new WP_Error('eo_error', __("No file detected.",'eventorganiser'));

			else:
				$EO_Errors = new WP_Error('eo_error', __("Invalid file uploaded. The file must be a ics calendar file, no larger than 2MB.",'eventorganiser'));

			endif;

		endif;
						
		add_action( 'eventorganiser_event_settings_imexport', array( $this, 'get_im_export_markup' ) );						
	}


	/**
	 * get markup for ex- and import on settings page
	 * 
	 * @since  	1.0.0
	 */
	public function get_im_export_markup() {
		?>
			<h3 class="title"><?php _e('Export Events', 'eventorganiser'); ?></h3>
			<p><?php _e( 'The export button below generates an ICS file of your events that can be imported in to other calendar applications such as Google Calendar.', 'eventorganiser'); ?></p>
			<form method="get" action="">
				<?php wp_nonce_field( 'eventorganiser_export' ); ?>
				<input type="hidden" name="page" value="event-settings" />
				<p class="submit">
					<input type="submit" name="submit" value="<?php _e( 'Download Export File', 'eventorganiser' ); ?> &raquo;" />
					<input type="hidden" name="eventorganiser_download_events" value="true" />
				</p>
			</form>

			<h3 class="title"><?php _e('Import Events', 'eventorganiser'); ?></h3>
			<div class="inside">
			<p><?php _e( 'Import an ICS file.', 'eventorganiser'); ?></p>
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field('eventorganiser_import'); ?>
					<input type="checkbox" name="eo_import_venue" value=1 /> <?php _e( 'Import venues', 'eventorganiser' ); ?>
					<input type="checkbox" name="eo_import_cat" value=1 /> <?php _e( 'Import categories', 'eventorganiser' ); ?>
					<p class="submit">
						<input type="file" name="ics" />
						<input type="submit" name="submit" value="<?php _e( 'Upload ICS file', 'eventorganiser' ); ?> &raquo;" />
						<input type="hidden" name="eventorganiser_import_events" value="true" />
					</p>
				</form>
			</div>
		<?php 
	}


/**
* Gets an ICAL file of events in the database, to be downloaded

 * @since 1.0.0
 */
	public function get_export_file() {
		$filename = urlencode( 'event-organiser_' . date('Y-m-d') . '.ics' );
		$this -> export_events( $filename, 'text/calendar' );
	}

/**
* Creates an ICAL file of events in the database

 * @since 1.0.0
*  @param string filename - the name of the file to be created
*  @param string filetype - the type of the file ('text/calendar')
 */
	public function export_events( $filename, $filetype ){ 
	//Collect output 
	ob_start();

	// File header
	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header('Content-type: text/calendar');
	header("Pragma: 0");
	header("Expires: 0");
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//<?php  get_bloginfo('name'); ?>//NONSGML Events //EN
CALSCALE:GREGORIAN
X-WR-CALNAME:<?php echo get_bloginfo('name');?> - Events
X-ORIGINAL-URL:<?php echo EO_Event::link_structure(); ?>

X-WR-CALDESC:<?php echo get_bloginfo('name');?> - Events
<?php
  
	// Query for events
	$events = eo_get_events(array('numberofposts'=>-1,'showrepeats'=>0,'showpastevents'=>1));
 
	// Loop through events
	if ($events):
		global $post;
		foreach ($events as $post):

			//If event has no corresponding row in events table then skip it
			if(!isset($post->event_id) || $post->event_id==-1)
				continue;

			$now = new DateTime();
			$dtstamp =$now->format('Ymd\THis\Z');

			//Set up event data
			setup_postdata($post);
			$event = new EO_Event($post->ID);
			$start = clone $event->start;
			$end = clone $event->end;

			$created = new DateTime($post->post_date_gmt);
			$created_date = $created->format('Ymd\THis\Z');

			$modified = new DateTime($post->post_modified_gmt);
			$modified_date = $modified->format('Ymd\THis\Z');


			//Set up start and end date times
			if($event->is_all_day()){
				$format =	'Ymd';
				$start_date = $start->format($format);
				$end->modify('+1 second');
				$end_date = $end->format($format);				
			}else{
				$format =	'Ymd\THis\Z';
				$UTC_tz = new DateTimeZone('UTC');
				$start->setTimezone($UTC_tz);
				$start_date =$start->format($format);
				$end->setTimezone($UTC_tz);
				$end_date = $end->format($format);
			}

			//Get the reoccurrence rule in ICS format
			$reoccurrence_rule =$event->ics_rrule();

			//Generate Event status
			if(get_post_status($post->ID)=='publish')
				$status = 'CONFIRMED';
			else
				$status = 'TENTATIVE';

			//Generate a globally unique UID:
			$rand='';
			$host = $_SERVER['SERVER_NAME'];
			$base   = 'aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPrRsStTuUvVxXuUvVwWzZ1234567890';
    			$start  = 0;
    			$end = strlen( $base ) - 1;
    			$length = 6;

    			for( $p = 0; $p < $length; $p++ ):
      				$rand .= $base{mt_rand( $start, $end )};
			endfor;

			  $uid  = $now->format('Ymd\THiT').microtime(true).'-'.$rand.'-EO'.$post->ID.'@'.$host;

			//Output event
?>
BEGIN:VEVENT
UID:<?php echo $uid;?>

STATUS:<?php echo $status;?>

DTSTAMP:<?php echo $dtstamp;?>

CREATED:<?php echo $created_date;?>

LAST-MODIFIED:<?php echo $modified_date;?>

DTSTART:<?php echo $start_date ; ?>

DTEND:<?php echo $end_date; ?>

<?php if ($reoccurrence_rule):?>
RRULE:<?php echo $reoccurrence_rule;?>

<?php endif;?>
SUMMARY:<?php echo $this->escape_icalText(get_the_title()); ?>

<?php
	$excerpt = get_the_excerpt();
	$excerpt = apply_filters('the_excerpt_rss', $excerpt);
	if(!empty($excerpt)):
?>
DESCRIPTION:<?php echo $this->escape_icalText($excerpt);?>

<?php 
	endif;

if($event->venue_set()): 
	$venue =eo_get_venue_name().", ".implode(', ',eo_get_venue_address());
?>
LOCATION: <?php echo $this->escape_icalText($venue);?>

<?php endif; 
	$author = get_the_author();
?>
ORGANIZER: <?php echo $this->escape_icalText($author);?>

END:VEVENT
<?php
		endforeach;
	endif;
?>
END:VCALENDAR
<?php

	//Collect output and echo 
	$eventsical = ob_get_contents();
	ob_end_clean();
	echo $eventsical;
	exit();
	}	


function escape_icalText($text){
	$text = str_replace("\\", "\\\\", $text);
	$text = str_replace(",", "\,", $text);
	$text = str_replace(";", "\;", $text);
	$text = str_replace("\n", "\n ", $text);
	
	return $text;
}


/**
* Reads in an ICAL file into an array, then parses the array and inserts events into database

 * @since 1.1.0
 *
 * @param string $cal_file - the file to import
 */
	function import_file($cal_file){
		global $EO_Errors;

		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_events' ))
			wp_die( __('You do not have sufficient permissions to import events.','event-organiser') );

		//Returns the file as an array of lines
		$file_array =$this->parse_file($cal_file);

		//Go through array and import events and insert them into database.
		$result = $this->import_events($file_array);	
	}

/* Reads an ICAL into an array ofline

 * @since 1.1.0
 *
 * @param string $cal_file - the file to parse
 */
	function parse_file($cal_file){
		global $EO_Errors;

		$file_handle = @fopen($cal_file, "rb");
    		$lines = array();

		if(!$file_handle)
			return false;

		//Feed lines into array
		while (!feof($file_handle) ): 
			$line_of_text = fgets($file_handle, 4096);
			$lines[]= $line_of_text;
		endwhile;

		fclose($file_handle);

		return $lines;
	}

/**
* Parses through an array of lines (of an ICAL file), creates events and inserts them as they are found.

 * @since 1.1.0
 *
 * @param array - $lines, array of lines of an ICAL file
 */
	function import_events($lines){
		global $EO_Errors;
		$state = "NONE";
    		$error = false;

		$error_count =0;
		$event_count =0;

    		$event_array = array();
		$event_array['event'] = array();
		$event_array['event_post'] = array();
		$event_array['event_meta'] = array();

		//Get Blog timezone, set Calendar timezone to this by default
		$blog_tz = EO_Event::get_timezone();
		$cal_tz =$blog_tz; 
		$output="";

		//Record number of venues / categories created
		global $eventorganiser_venues_created,$eventorganiser_cats_created;
		$eventorganiser_venues_created = 0;
		$eventorganiser_cats_created = 0;

		//Read through each line
		for ( $n = 0; $n < count ( $lines ) && ! $error; $n++ ):
			$buff = trim($lines[$n]);

			if(!empty($buff)):
				$line = explode(':',$buff,2);

				//On the right side of the line we may have DTSTART;TZID= or DTSTART;VALUE= 
				$modifiers = explode (';', $line[0]); 
				$property =array_shift($modifiers);
				$value = (isset($line[1]) ? trim($line[1]) : '');

				//If we are in EVENT state
		      		if ($state == "VEVENT") {

					//If END:VEVENT, insert event into database
					if($property=='END' && $value=='VEVENT'){
						$state = "VCALENDAR";

						$event_array['event']['YmdFormated']= true;
						$event_array['event']['dateObjects']= true;
				
						//Insert new post from objects
						$post_id = EO_Event::insertNewEvent($event_array['event_post'],$event_array['event']);
						if(!$post_id){
							$error_count++;
						}
						
					}else{
						//Otherwise, parse event property
						try{
							$event_array = $this->parse_Event_Property($event_array,$property,$value,$modifiers,$blog_tz,$cal_tz);

						}catch(Exception $e){
							$error_count++;
							$EO_Errors->add('eo_error', __($e->getMessage()));

							//Abort parsing event
							$state = "VCALENDAR";
						}
					}

				// If we are in CALENDAR state
				}elseif ($state == "VCALENDAR") {

					//Begin event
					if( $property=='BEGIN' && $value=='VEVENT'){
						$state = "VEVENT";
						$event_count++;
						$event_array['event'] = array();
						$event_array['event_post'] = array();
						$event_array['event_meta'] = array();

					}elseif ( $property=='END' && $value=='VCALENDAR'){
						$state = "NONE";

					}elseif($property=='X-WR-TIMEZONE'){
						try{
							$cal_tz = self::parse_TZID($value);
						}catch(Exception $e){
							$EO_Errors->add('eo_error', __($e->getMessage()));
							break;
						}
					}

			 	}elseif($state == "NONE" && $property=='BEGIN' && $value=='VCALENDAR') {
					$state = "VCALENDAR";
				}

			endif; //If line is not empty
   	 	endfor; //For each line

		//Display message
		if($event_count ==0):
			$EO_Errors->add('eo_error', __("No events were imported.",'eventorganiser'));
		elseif($error_count >0):
			$EO_Errors->add('eo_error',sprintf( __("There was an error with %1$d of %2$d events in the ical file"),$error_count, $event_count));
		else:

			if($event_count==1)
				$message=__("1 event was successfully imported",'eventorganiser').".";
			else
				$message= sprintf( __("%d events were successfully imported",'eventorganiser'),$event_count).".";

			if($eventorganiser_venues_created==1){
				$message.= " ".__("1 venue was created",'eventorganiser').".";
			}elseif($eventorganiser_venues_created>1){
				$message .= " ".sprintf( __("%d venues were created",'eventorganiser'),$eventorganiser_venues_created).".";
			}

			if($eventorganiser_cats_created==1){
				$message.= " ".__("1 category was created",'eventorganiser').".";
			}elseif($eventorganiser_cats_created>1){
				$message .= " ".sprintf( __("%d categories were created",'eventorganiser'),$eventorganiser_cats_created).".";
			}

			$EO_Errors->add('eo_notice',$message);
		endif;
		return true;
	}

/**
* Returns the supplied array with the additional data parsed from the given property-value pair
* May require the blog and/or calendar time-zone in date manipulations
* May also require modifiers in date manipulations (e.g. TZID, VALUE)
*
 * @since 1.1.0
 *
 * @param array $event_array - array of event details to be added to
 * @param string $property - the property being parsed
 * @param string $value - the value of the  property being parsed
 * @param string $modifiers - array of modifiers associated with the property
 * @param DateTimeZone $blog_tz - blog's timezone
 * @param DateTimeZone $cal_tzid - calendar's default timezone
 * @return DateTimeZone - the timezone with the given identifier or false if it isn't recognised
 */
	function parse_Event_Property($event_array,$property,$value,$modifiers,$blog_tz,$cal_tz){
		extract($event_array);

		$import_venues = (isset($_POST['eo_import_venue']) ? true : false);
		$import_cats = (isset($_POST['eo_import_cat']) ? true : false);

		$date_tz="";
	
		if(!empty($modifiers)):
			foreach($modifiers as $modifier):
				if (stristr($modifier, 'TZID')){
					$date_tz = self::parse_TZID(substr($modifier, 5));

				}elseif(stristr($modifier, 'VALUE')){
					$meta = substr($modifier, 6);
				}
			endforeach;
		endif;

		//For dates - if there is not an associated timezone, use calendar default.
		if(empty($date_tz))
			$date_tz = $cal_tz;

		switch($property):
			//Date properties
			case 'CREATED':
			case 'DTSTART':
			case 'DTEND':
				if(isset($meta) && $meta=='DATE'):
					$date = $this->parse_icalDate($value, $blog_tz);
					$allday=1;
				else:
					$date = $this->parse_icalDateTime($value, $date_tz);
					$allday=0;
				endif;

				if(empty($date))
					break;

				switch($property):
					case'DTSTART':
						$event['start']= $date;
						$event['allday']=$allday;
						break;
	
					case 'DTEND':
						if($allday==1)
							$date->modify('-1 second');
						$event['end']= $date;
						break;

					case 'CREATED':
						$date->setTimezone(new DateTImeZone('utc'));
						$event_post['post_date_gmt']= $date->format('Y-m-d H:i:s');
						break;

				endswitch;
				break;

			case 'EXDATE':
				//The modifiers have been dealt with above. We do similiar to above, except for an array of dates...
				$value_array = explode(',',$value);
							
				//Note, we only consider the Date part and ignore the time
				foreach($value_array as $val):
					$date = $this->parse_icalDate($val, $blog_tz);
					$event['exception_dates'][] = $date;
				endforeach;
		
				break;							

			//Reoccurrence rule properties
			case 'RRULE':
				$event += $this->parse_RRule($value);
				break;

			//The event's summary (AKA post title)
			case 'SUMMARY':
				$event_post['post_title']=$this->parse_icalText($value);
				break;

			//The event's description (AKA post content)
			case 'DESCRIPTION':
				$event_post['post_content']=$this->parse_icalText($value);
				break;

			//Event venues, assign to existing venue - or if set, create new one
			case 'LOCATION':
				$venue_ids = array();
				if(!empty($value)):
					$venue_name = trim($value);
					$venue = get_term_by('name',$venue_name,'event-venue');
					if($venue){
						$venue_ids[] = (int) $venue->term_id;
					}elseif($import_venues){
						//Create new venue, get ID. Count of venues created++
						global $eventorganiser_venues_created;
						$return = EO_Venue::insert(array('venue_name'=>$venue_name));

						if($return){
							$venue_ids[] = (int) $return['term_id'];
							$eventorganiser_venues_created++;
							$event['venue']= $return['term_id']; //XXX This is depreciated
						}
					}
					$venue_ids = array_filter($venue_ids);
					if(!empty($venue_ids)){
						$event_post['tax_input']['event-venue']=$venue_ids;	
					}
				endif;
				break;			

			//Event categories, assign to existing categories - or if set, create new ones
			case 'CATEGORIES':
				$cats=explode(',',$value);
				$cat_ids = array();
				if(!empty($cats)):
					foreach ($cats as $cat_name):
						$cat_name = trim($cat_name);
						$cat = get_term_by('name',$cat_name,'event-category');
						if($cat){
							$cat_ids[] = (int) $cat->term_id;
						}elseif($import_cats){
							//Create new category, get ID. Count of cats created++
							global $eventorganiser_cats_created;
							$return = wp_insert_term($cat_name,'event-category',array());
							if(!is_wp_error($return)){
								$cat_ids[] = (int) $return['term_id'];
								$eventorganiser_cats_created++;
							}
						}
					endforeach;

					$cat_ids = array_filter($cat_ids);
					if(!empty($cat_ids))
						$event_post['tax_input']['event-category']=$cat_ids;
				endif;
				break;	

			//The event's status
			case 'STATUS':
				switch($value):
					case 'CONFIRMED':
						$event_post['post_status'] = 'publish';
						break;

					case 'CANCELLED':
						$event_post['post_status'] = 'trash';
						break;

					default:
						$event_post['post_status'] = 'draft';
				endswitch;
				break;
	
			//An url associated with the event
			case 'URL':
				$event_meta['eo_url']=$value;
				break;
	
		endswitch;

		$event_array= array('event'=>$event,'event_post'=>$event_post,'event_meta'=>$event_meta);

		return $event_array;
	}

/**
* Takes escaped text and returns the text unescaped.

 * @since 1.1.0
 *
 * @param string $text - the escaped test
 * @return string $text - the text, unescaped.
 */
	function parse_icalText($text){
		$text = str_replace("\\r\\n","</br>",$text);
		$text = str_replace("\\n","</br>",$text);
		$text = stripslashes($text);
		return $text;
	}

/**
* Takes a date-time in ICAL and returns a datetime object

 * @since 1.1.0
 *
 * @param string $tzid - the value of the ICAL TZID property
 * @return DateTimeZone - the timezone with the given identifier or false if it isn't recognised
 */
	function parse_TZID($tzid){
		$tzid = str_replace('-','/',$tzid);
		$tz = new DateTimeZone($tzid);
		return $tz;
	}

/**
* Takes a date in ICAL and returns a datetime object

 * @since 1.1.0
 *
 * @param string $ical_date - date in ICAL format
 * @param DateTimeZone $blog_tz - Blog timezone object
 * @return DateTime - the $ical_date as DateTime object
 */
	function parse_icalDate($ical_date, $blog_tz){
		//Expects: YYYYMMDD;
		preg_match('/^(\d{8})*/', $ical_date, $matches);

		if(count($matches)!=2){
			throw new Exception('Invalid date');
		}

		$datetime = new DateTime($matches[1],$blog_tz);

		return $datetime;
	}

/**
* Takes a date-time in ICAL and returns a datetime object

 * @since 1.1.0
 *
 * @param string $ical_date - date-time in ICAL format
 * @param DateTimeZone $blog_tz - Blog timezone object
 * @return DateTime - the $ical_date as DateTime object
 */
	function parse_icalDateTime($ical_date,$tz){
		/*
		Expects
			utc:  YYYYMMDDTHHiissZ
			local:  YYYYMMDDTHHiiss
		*/
		preg_match('/^((\d{8}T\d{6})(Z)?)/', $ical_date, $matches);
		
		if(count($matches)==3){
			//floating / local date

		}elseif(count($matches)==4){
			$tz = new DateTimeZone('UTC');

		}else{
			throw new Exception('Invalid date');
			return false;
		}

		$datetime = new DateTime($matches[2],$tz);

		return $datetime;
	}

/**
* Takes a date-time in ICAL and returns a datetime object

 * @since 1.1.0
 *
 * @param string $RRule - the value of the ICAL RRule property
 * @return array - a reoccurrence rule array as understood by Event Organiser
 */
	function parse_RRule($RRule){
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
						$date = $this->parse_icalDateTime($value, new DateTimeZone('UTC'));
					else
						$date = $this->parse_icalDate($value, new DateTimeZone('UTC'));

					$rule_array['schedule_end'] = $date;
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
		if($rule_array['schedule']=='monthly'):
			if(isset($byday)){
				preg_match('/(\d+)([a-zA-Z]+)/', $byday, $matches);	
				$rule_array['schedule_meta'] ='BYDAY='.$matches[1].$matches[2];

			}elseif(isset($bymonthday)){
				$rule_array['schedule_meta'] ='BYMONTHDAY='.$bymonthday;

			}else{
				throw new Exception('Incomplete scheduling information');
			}

		elseif($rule_array['schedule']=='weekly'):
			preg_match('/([a-zA-Z,]+)/', $byday, $matches);	
			$rule_array['schedule_meta'] =explode(',',$matches[1]);

		endif;

		return $rule_array;
	}


} // end class
