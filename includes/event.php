<?php
/**
 *@package event-functions
 */
/**
* This functions updates a post of event type, with data given in the $post_data
* and event data given in $event_data. Returns the post_id. 
*
* Triggers {@see `eventorganiser_save_event`} passing event (post) ID
*
* The event data array can contain
*
* * `schedule` => (custom | once | daily | weekly | monthly | yearly)  -- specifies the reoccurrence pattern
* * `schedule_meta` =>
*   * For monthly schedules,
*      * (string) BYMONTHDAY=XX to repeat on XXth day of month, e.g. BYMONTHDAY=01 to repeat on the first of every month.
*      * (string) BYDAY=ND. N= 1|2|3|4|-1 (first, second, third, fourth, last). D is day of week SU|MO|TU|WE|TH|FR|SA. E.g. BYDAY=2TU (repeat on second tuesday)
*   * For weekly schedules,
*      * (array) Days to repeat on: (SU,MO,TU,WE,TH,FR,SA). e.g. set to array('SU','TU') to repeat on Tuesdays & Sundays. 
*      * Can be left blank to repeat weekly from the start date.
* * `frequency` => (int) positive integer, sets frequency of reoccurrence (every 2 days, or every 3 days etc)
* * `all_day` => 1 if its an all day event, 0 if not
* * `start` =>  start date (of first occurrence)  as a datetime object
* * `end` => end date (of first occurrence)  as a datetime object
* * `schedule_last` =>  **START** date of last occurrence (or upper-bound thereof) as a datetime object
* * `include` => array of datetime objects to include in the schedule
* * `exclude` => array of datetime objects to exclude in the schedule
*
* @since 1.5
* @uses wp_insert_post()
*
* @param int $post_id - the event (post) ID for the event you want to update
* @param array $post_data - array of data to be used by wp_update_post.
* @param array $event_data - array of event data
* @return int $post_id - the post ID of the updated event
*/
	function eo_update_event($post_id, $event_data=array(), $post_data=array() ){

		$post_id = (int) $post_id;

		if( empty($post_id) )
			return new WP_Error('eo_error','Empty post ID.');

		if( !empty($event_data['venue']) || !empty($event_data['category']) ){
			$post_data['taxonomy']['event-venue'] = isset($event_data['venue']) ? $event_data['venue'] : null;
			$post_data['taxonomy']['event-category'] = isset($event_data['category']) ? $event_data['category'] : null;
			unset($event_data['venue']);
			unset($event_data['category']);
		}

		if( !empty($post_data) ){
			$post_data['ID'] = $post_id;		
			wp_update_post( $post_data );
		}

		//Get previous data, parse with data to be updated
		$prev = eo_get_event_schedule($post_id);
		$event_data = wp_parse_args( $event_data, $prev );

		//If schedule is 'once' and dates are included - set to 'custom':
		if( ( empty($event_data['schedule']) || 'once' == $event_data['schedule'] ) && !empty($event_data['include']) ){
			$event_data['schedule'] = 'custom';
		}

		//Do we need to delete existing dates from db?
		$delete_existing = false;
		$diff = array();
		if( $prev ){
			foreach ( $prev as $key => $prev_value ){
				if( $event_data[$key] != $prev_value ){
					if('monthly' == $event_data['schedule'] && $key =='schedule_meta'){
						if( $event_data['occurs_by'] != $prev['occurs_by'] ){
							$diff[]=$key;
							$delete_existing = true;
							break;
						}
					}else{
						$diff[]=$key;
						$delete_existing = true;
						break;
					}
				}
			}
		}

		//Need to replace occurrences
		if( $delete_existing || !empty( $event_data['force_regenerate_dates'] ) ){
			//Generate occurrences
			$event_data = _eventorganiser_generate_occurrences($event_data);

			if( is_wp_error($event_data) )
				return $event_data;

			//Delete old dates
			eo_delete_event_occurrences($post_id);

			//Insert new ones and update meta
			$re = _eventorganiser_insert_occurrences($post_id,$event_data);
		}

		do_action('eventorganiser_save_event', $post_id);
		return $post_id;
	}


/**
* This functions inserts a post of event type, with data given in the $post_data
* and event data given in $event_data. Returns the post ID.
*
* Triggers {@see `eventorganiser_save_event`} passing event (post) ID
*
* The event data array can contain
*
* * `schedule` => (custom | once | daily | weekly | monthly | yearly)  -- specifies the reoccurrence pattern
* * `schedule_meta` =>
*   * For monthly schedules,
*      * (string) BYMONTHDAY=XX to repeat on XXth day of month, e.g. BYMONTHDAY=01 to repeat on the first of every month.
*      * (string) BYDAY=ND. N= 1|2|3|4|-1 (first, second, third, fourth, last). D is day of week SU|MO|TU|WE|TH|FR|SA. E.g. BYDAY=2TU (repeat on second tuesday)
*   * For weekly schedules,
*      * (array) Days to repeat on: (SU,MO,TU,WE,TH,FR,SA). e.g. set to array('SU','TU') to repeat on Tuesdays & Sundays. 
*      * Can be left blank to repeat weekly from the start date.
* * `frequency` => (int) positive integer, sets frequency of reoccurrence (every 2 days, or every 3 days etc)
* * `all_day` => 1 if its an all day event, 0 if not
* * `start` =>  start date (of first occurrence)  as a datetime object
* * `end` => end date (of first occurrence)  as a datetime object
* * `schedule_last` =>  **START** date of last occurrence (or upper-bound thereof) as a datetime object
* * `include` => array of datetime objects to include in the schedule
* * `exclude` => array of datetime objects to exclude in the schedule
*
* @since 1.5
* @link http://www.stephenharris.info/2012/front-end-event-posting/ Tutorial on front-end event posting
* @uses wp_insert_post() 
*
* @param array $post_data array of data to be used by wp_insert_post.
* @param array $event_data array of event data
* @return int the post ID of the updated event
*/
	function eo_insert_event($post_data=array(),$event_data=array()){
		global $wpdb;

		if( !empty($event_data['venue'] ) ){
			$post_data['tax_input']['event-venue'] = $event_data['venue'];
			unset($event_data['venue']);
		}
		if( !empty($event_data['category'] ) ){
			$post_data['tax_input']['event-category'] = $event_data['category'];
			unset($event_data['category']);
		}

		//If schedule is 'once' and dates are included - set to 'custom':
		if( ( empty($event_data['schedule']) || 'once' == $event_data['schedule'] ) && !empty($event_data['include']) ){
			$event_data['schedule'] = 'custom';
		}

		$event_data = _eventorganiser_generate_occurrences($event_data);

		if( is_wp_error($event_data) )
			return $event_data;

		//Finally we create event (first create the post in WP)
		$post_input = array_merge(array('post_title'=>'untitled event'), $post_data, array('post_type'=>'event'));			
		$post_id = wp_insert_post($post_input, true);

		//Did the event insert correctly? 
		if ( is_wp_error( $post_id) ) 
				return $post_id;

		 _eventorganiser_insert_occurrences($post_id, $event_data);
			
		//Action used to break cache & trigger Pro actions (& by other plug-ins?)
		do_action('eventorganiser_save_event',$post_id);
		return $post_id;
	}

/**
* Deletes all occurrences for an event (removes them from the eo_events table).
* Triggers {@see `eventorganiser_delete_event`} (this action is used to break the caches).
 * @since 1.5
 *
 * @param int $post_id the event's (post) ID to be deleted
 */
function eo_delete_event_occurrences($post_id){
	global $wpdb;
	
	do_action('eventorganiser_delete_event', $post_id);
	$del = $wpdb->get_results($wpdb->prepare("DELETE FROM $wpdb->eo_events WHERE post_id=%d",$post_id));
}
add_action( 'delete_post', 'eo_delete_event_occurrences', 10 );

/**
* This is a private function - handles the insertion of dates into the database. Use eo_insert_event or eo_update_event instead.
* @access private
* @ignore
*
* @param int $post_id The post ID of the event
* @param array $event_data Array of event data, including schedule meta (saved as post meta), duration and occurrences
* @return int $post_id
*/
	function  _eventorganiser_insert_occurrences($post_id, $event_data){
		global $wpdb;
		extract($event_data);

		//Get duration
		$duration=false;
		if( function_exists('date_diff') ){
			$duration = date_diff($start,$end);

			/* Storing a DateInterval object can cause errors. Serialize it.
			http://www.harriswebsolutions.co.uk/event-organiser/forums/topic/error-when-saving-events/
			 Thanks to Mathieu Parisot, Mathias & Dave Page */
			$event_data['duration'] = maybe_serialize($duration);
		}

		//Work around for PHP < 5.3
		$seconds = round(abs($start->format('U') - $end->format('U')));
		$days = floor($seconds/86400);// 86400 = 60*60*24 seconds in a normal day
		$sec_diff = $seconds - $days*86400;
		$duration_str = '+'.$days.'days '.$sec_diff.' seconds';
		$event_data['duration_str'] =$duration_str;

		$occurrence_array = array();

		foreach( $occurrences as $counter=> $occurrence ):
			$occurrence_end = clone $occurrence;
			if( $duration ){
				$occurrence_end->add($duration);
			}else{
				$occurrence_end->modify($duration_str);
			}

			$occurrence_input =array(
				'post_id'=>$post_id,
				'StartDate'=>$occurrence->format('Y-m-d'),
				'StartTime'=>$occurrence->format('H:i:s'),
				'EndDate'=>$occurrence_end->format('Y-m-d'),
				'FinishTime'=>$end->format('H:i:s'),
				'event_occurrence' => $counter,
			);

			$wpdb->insert($wpdb->eo_events, $occurrence_input);
			$occurrence_array[$wpdb->insert_id] = $occurrence->format('Y-m-d H:i:s');

			//Add to occurrence cache: TODO use post meta
			$occurrence_cache[$wpdb->insert_id] = array(
				'start' =>$occurrence,
				'end' => new DateTime($occurrence_end->format('Y-m-d').' '.$end->format('H:i:s'), eo_get_blog_timezone())
			);
		endforeach;

		//Set occurrence cache
		wp_cache_set( 'eventorganiser_occurrences_'.$post_id, $occurrence_cache );

		unset($event_data['occurrences']);
		$event_data['_occurrences'] = $occurrence_array;
		
		if( !empty($include) )
			$event_data['include'] = array_map('eo_format_datetime', $include, array_fill(0, count($include), 'Y-m-d H:i:s') );
		if( !empty($exclude) )
			$event_data['exclude'] = array_map('eo_format_datetime', $exclude, array_fill(0, count($exclude), 'Y-m-d H:i:s') );

		unset($event_data['start']);
		unset($event_data['end']);
		unset($event_data['schedule_start']);
		unset($event_data['schedule_last']);

		update_post_meta( $post_id,'_eventorganiser_event_schedule', $event_data);
		update_post_meta( $post_id,'_eventorganiser_schedule_start_start', $start->format('Y-m-d H:i:s'));
		update_post_meta( $post_id,'_eventorganiser_schedule_start_finish', $end->format('Y-m-d H:i:s'));
		update_post_meta( $post_id,'_eventorganiser_schedule_last_start', $occurrence->format('Y-m-d H:i:s'));
		update_post_meta( $post_id,'_eventorganiser_schedule_last_finish', $occurrence_end->format('Y-m-d H:i:s'));
		return $post_id;
	}


/**
* Gets schedule meta from the database (post meta)
* Datetimes are converted to DateTime objects, in blog's currenty timezone
*
*  Event details include
*
* * `schedule` => (custom | once | daily | weekly | monthly | yearly)  -- specifies the reoccurrence pattern
* * `schedule_meta` =>
*   * For monthly schedules,
*      * (string) BYMONTHDAY=XX to repeat on XXth day of month, e.g. BYMONTHDAY=01 to repeat on the first of every month.
*      * (string) BYDAY=ND. N= 1|2|3|4|-1 (first, second, third, fourth, last). D is day of week SU|MO|TU|WE|TH|FR|SA. E.g. BYDAY=2TU (repeat on second tuesday)
*   * For weekly schedules,
*      * (array) Days to repeat on: (SU,MO,TU,WE,TH,FR,SA). e.g. set to array('SU','TU') to repeat on Tuesdays & Sundays. 
* * `occurs_by` - For use with monthly schedules: how the event reoccurs: BYDAY or BYMONTHDAY
* * `frequency` => (int) positive integer, sets frequency of reoccurrence (every 2 days, or every 3 days etc)
* * `all_day` => 1 if its an all day event, 0 if not
* * `start` =>  start date (of first occurrence)  as a datetime object
* * `end` => end date (of first occurrence)  as a datetime object
* * `schedule_last` =>  **START** date of last occurrence as a datetime object
* * `include` => array of datetime objects to include in the schedule
* * `exclude` => array of datetime objects to exclude in the schedule
*
* @param int $post_id -  The post ID of the event
* @return array event schedule details
*/
	function eo_get_event_schedule( $post_id=0 ){

		$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

		if( empty($post_id) ) 
			return false;

		$event_details = get_post_meta( $post_id,'_eventorganiser_event_schedule',true);
		$event_details = wp_parse_args($event_details, array(
			'schedule'=>'once',
			'schedule_meta'=>'',
			'frequency'=>1,
			'all_day'=>0,
			'duration_str'=>'',
			'include'=>array(),
			'exclude'=>array(),
			'_occurrences'=>array(),
		));

		$tz = eo_get_blog_timezone();
		$event_details['start'] = new DateTime(get_post_meta( $post_id,'_eventorganiser_schedule_start_start', true), $tz);
		$event_details['end'] = new DateTime(get_post_meta( $post_id,'_eventorganiser_schedule_start_finish', true), $tz);
		$event_details['schedule_start'] = clone $event_details['start'];
		$event_details['schedule_last'] = new DateTime(get_post_meta( $post_id,'_eventorganiser_schedule_last_start', true), $tz);
		$event_details['schedule_finish'] = new DateTime(get_post_meta( $post_id,'_eventorganiser_schedule_last_finish', true), $tz);

		if( !empty($event_details['_occurrences']) ){
			$event_details['_occurrences'] = array_map('eventorganiser_date_create', $event_details['_occurrences']);
		}

		if( !empty($event_details['include']) ){
			$event_details['include'] = array_map('eventorganiser_date_create', $event_details['include'] );
		}
		if( !empty($event_details['exclude']) ){
			$event_details['exclude'] = array_map('eventorganiser_date_create',$event_details['exclude'] );
		}

		if($event_details['schedule'] == 'weekly'){
			$event_details['occurs_by'] = '';
		}elseif($event_details['schedule'] == 'monthly'){
			$bymonthday = preg_match('/BYMONTHDAY=/',$event_details['schedule_meta']);
			$event_details['occurs_by'] = ($bymonthday ? 'BYMONTHDAY' : 'BYDAY');
		}else{
			$event_details['occurs_by'] ='';
		}

		return $event_details;
	}


/**
* This is a private function - handles the generation of occurrence dates from the schedule data
* @access private
* @ignore
*
* @param array $event_data - Array containing the event's schedule data
* @return array $event_data - Array containing the event's schedule data including 'occurrences', an array of DateTimes
*/
	function _eventorganiser_generate_occurrences( $event_data=array() ){

		$event_defaults = array(
			'start'=>'',
			'end'=>'',
			'all_day'=>0,
			'schedule'=>'once',
			'schedule_meta'=>'',
			'frequency'=>1,
			'schedule_last'=>'',
			'exclude'=>array(),
			'include'=>array(),
		);
		extract(wp_parse_args($event_data, $event_defaults));
		
		$occurrences =array(); //occurrences array	

		//Make sure the same doesn't appear in both $include/$exclude. Is this really needed?
		$exclude = array_udiff($exclude, $include, '_eventorganiser_compare_dates');
		$include = array_udiff($include, $exclude, '_eventorganiser_compare_dates');

		//Check dates are supplied and are valid
		if( !($start instanceof DateTime) )
			return new WP_Error('eo_error',__('Start date not provided.','eventorganiser'));

		if( !($end instanceof DateTime) )
			$end = clone $start;

		if( !($schedule_last instanceof DateTime) )
			$schedule_last = clone $start;

		//Check dates are in chronological order
		if($end < $start)
			return new WP_Error('eo_error',__('Start date occurs after end date.','eventorganiser'));
		
		if($schedule_last < $start)
			return new WP_Error('eo_error',__('Schedule end date is before is before the start date.','eventorganiser'));

		//Now set timezones
		$timezone = eo_get_blog_timezone();
		$start->setTimezone($timezone);
		$end->setTimezone($timezone);
		$schedule_last->setTimezone($timezone);
		$H = intval($start->format('H'));
		$i = intval($start->format('i'));

		//White list schedule
		if( !in_array($schedule, array('once','daily','weekly','monthly','yearly','custom')) )
			return new WP_Error('eo_error',__('Schedule not recognised.','eventorganiser'));

		//Ensure event frequency is a positive integer. Else set to 1.
		$frequency = max(absint($frequency),1);
		$all_day = (int) $all_day ;

		$start_days =array();
		$workaround='';
		$icaldays = array('SU','MO','TU','WE','TH','FR','SA');
		$weekdays = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'); 
		$ical2day = array('SU'=>'Sunday','MO'=>'Monday','TU'=>'Tuesday',
			'WE'=>'Wednesday','TH'=>'Thursday','FR'=>'Friday','SA'=>'Saturday',);

		//Set up schedule
		switch( $schedule ) :
			case 'once':
			case 'custom':
				$frequency =1;
				$schedule_meta ='';
				$schedule_start = clone $start;
				$schedule_last = clone $start;
				$start_days[] = clone $start;
				$workaround = 'once';//Not strictly a workaround.
				break;

			case 'daily':
				$interval = "+".$frequency."day";
				$start_days[] = clone $start;
				break;	

			case 'weekly':
				$schedule_meta = array_filter($schedule_meta);
				if( !empty($schedule_meta) && is_array($schedule_meta) ):
					foreach ($schedule_meta as $day):
						$start_day = clone $start;
						$start_day->modify($ical2day[$day]);
						$start_days[] = $start_day;
					endforeach;
				else:
					$start_days[] = clone $start;
				endif;

				$interval = "+".$frequency."week";
				break;

			case 'monthly':
				$start_days[] = clone $start;
				$rule_value = explode('=',$schedule_meta,2);
				$rule =$rule_value[0];
				$values = explode(',',$rule_value[1]);//Should only be one value, but may support more in future
				$values =  array_filter($values);
				
				if( $rule=='BYMONTHDAY' ):
					$date = (int) $start_days[0]->format('d');
					$interval = "+".$frequency."month";
					
					if($date >= 29)
						$workaround = 'short months';	//This case deals with 29/30/31 of month

					$schedule_meta = 'BYMONTHDAY='.$date;

				else:
					if( empty($values) ){
						$date = (int) $start_days[0]->format('d');
						$n = ceil($date/7); // nth weekday of month.
						$day_num = intval($start_days[0]->format('w')); //0 (Sun) - 6(Sat)

					}else{
						//expect e.g. array( 2MO )
						preg_match('/^(-?\d{1,2})([a-zA-Z]{2})/' ,$values[0],$matches);
						$n=(int) $matches[1];
						$day_num = array_search($matches[2],$icaldays);//(Sun) - 6(Sat)
					}

					if($n==5) $n= -1; //If 5th, interpret it as last.
					$ordinal = array('1'=>"first",'2'=>"second",'3'=>"third",'4'=>"fourth",'-1'=>"last");

					if( !isset($ordinal[$n]) )
						return new WP_Error('eo_error',__('Invalid monthly schedule (invalid ordinal)','eventorganiser'));

					$ical_day = $icaldays[$day_num];  //ical day from day_num (SU - SA)
					$day = $weekdays[$day_num];//Full day name from day_num (Sunday -Monday)
					$schedule_meta = 'BYDAY='.$n.$ical_day; //E.g. BYDAY=2MO
					$interval = $ordinal[$n].' '.$day.' of +'.$frequency.' month'; //E.g. second monday of +1 month
					
					//Work around for PHP <5.3
					if(!function_exists('date_diff')){
						$workaround = 'php5.2';
					}
				endif;
				break;
	
			case 'yearly':
				$start_days[] = clone $start;
				if( '29-02' == $start_days[0]->format('d-m') )
					$workaround = 'leap year';
				
				$interval = "+".$frequency."year";
				break;
		endswitch; //End $schedule switch


		//Now we have setup and validated the schedules - loop through and generate occurrences
		foreach($start_days as $index => $start_day):
			$current = clone $start_day;
			
			switch($workaround):
				//Not really a workaround. Just add the occurrence and finish.
				case 'once':
					$current->setTime($H,$i );
					$occurrences[] = clone $current;
					break;
				
				//Loops for monthly events that require php5.3 functionality
				case 'php5.2':
					while( $current <= $schedule_last ):
						$current->setTime($H,$i );
						$occurrences[] = clone $current;	
						$current = _eventorganiser_php52_modify($current,$interval);
					endwhile; 
					break;

				//Loops for monthly events on the 29th/30th/31st
				case 'short months':
					 $day_int =intval($start_day->format('d'));
	
					//Set the first month
					$current_month= clone $start_day;
					$current_month = date_create($current_month->format('Y-m-1'));
				
					while( $current_month<=$schedule_last ):
						$month_int = intval($current_month->format('m'));		
						$year_int = intval($current_month->format('Y'));		

						if( checkdate($month_int , $day_int , $year_int) ){
							$current = new DateTime($day_int.'-'.$month_int.'-'.$year_int, $timezone);
							$current->setTime($H,$i );
							$occurrences[] = 	clone $current;
						}
						$current_month->modify($interval);
					endwhile;	
					break;

				//To be used for yearly events occuring on Feb 29
				case 'leap year':
					$current_year = clone $current;
					$current_year->modify('-1 day');

					while($current_year<=$schedule_last):	
						$is_leap_year = (int) $current_year->format('L');

						if( $is_leap_year ){
							$current = clone $current_year;
							$current->modify('+1 day');
							$current->setTime($H,$i );
							$occurrences[] = clone $current;
						}

						$current_year->modify($interval);
					endwhile;
					break;
			
				default:
					while($current <= $schedule_last):
						$current->setTime($H,$i );
						$occurrences[] = clone $current;	
						$current->modify($interval);
					endwhile;
					break;

				endswitch;//End 'workaround' switch;
		endforeach;

		//Now schedule meta is set up and occurrences are generated.

		//Add inclusions, removes exceptions and duplicates
		$occurrences = array_merge($occurrences, $include); 
		$occurrences = array_udiff($occurrences, $exclude, '_eventorganiser_compare_dates');
		$occurrences = _eventorganiser_remove_duplicates($occurrences);

		//Sort occurrences
		sort($occurrences);
		$schedule_start = clone $occurrences[0];
		$schedule_last = clone end($occurrences);

		return array(
			'start'=>$start,
			'end'=>$end,
			'all_day'=>$all_day,
			'schedule'=>$schedule,
			'schedule_meta'=>$schedule_meta,
			'frequency'=>$frequency,
			'schedule_start'=>$schedule_start,
			'schedule_last'=>$schedule_last,
			'exclude'=>$exclude,
			'include'=>$include,
			'occurrences'=>$occurrences
		);
	}

/**
 * Generates the ICS RRULE fromthe event schedule data. 
 * @access private
 * @ignore
 * @since 1.0.0
 *
 * @param int $post_id The event (post) ID. Uses current event if empty.
 * @return string The RRULE to be used in an ICS calendar
 */
function eventorganiser_generate_ics_rrule($post_id=0){

		$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

		$rrule = eo_get_event_schedule($post_id);
		if( !$rrule )
			return false;

		extract($rrule);
		
		$format = ( $all_day ? 'Ymd' : 'Ymd\THis\Z' );

		$schedule_last->setTimezone(new DateTimeZone('UTC'));
		$schedule_last = $schedule_last->format($format);

		switch($schedule):
			case 'once':
				return false;

			case 'yearly':
				return "FREQ=YEARLY;INTERVAL=".$frequency.";UNTIL=".$schedule_last;

			case 'monthly':
				$reoccurrence_rule = "FREQ=MONTHLY;INTERVAL=".$frequency.";";
				$reoccurrence_rule.=$schedule_meta.";";
				$reoccurrence_rule.= "UNTIL=".$schedule_last;
				return $reoccurrence_rule;
	
			case 'weekly':
				return "FREQ=WEEKLY;INTERVAL=".$frequency.";BYDAY=".implode(',',$schedule_meta).";UNTIL=".$schedule_last;

			case 'daily':
				return "FREQ=DAILY;INTERVAL=".$frequency.";UNTIL=".$schedule_last;

			default:
		endswitch;
		return false;
}

/**
 * Removes a single occurrence and adds it to the event's 'excluded' dates.
 * @access private
 * @ignore
 * @since 1.5
 *
 * @param int $post_id The event (post) ID
 * @param int $event_id The event occurrence ID
 * @return bool|WP_Error True on success, WP_Error object on failure
 */
	function _eventorganiser_remove_occurrence($post_id=0, $event_id=0){
		global $wpdb;

		$remove = $wpdb->get_row($wpdb->prepare(
			"SELECT {$wpdb->eo_events}.StartDate, {$wpdb->eo_events}.StartTime  
			FROM {$wpdb->eo_events} 
			WHERE post_id=%d AND event_id=%d",$post_id,$event_id));

		if( !$remove )
			return new WP_Error('eo_notice', '<strong>'.__("Occurrence note deleted. Occurrence not found",'eventorganiser').'</strong>');

		$date = trim($remove->StartDate).' '.trim($remove->StartTime);

		$event_details = get_post_meta( $post_id,'_eventorganiser_event_schedule',true);

		if( ($key = array_search($date,$event_details['include'])) === false){
			//If the date was not manually included, add it to the 'exclude' array
			$event_details['exclude'][] = $date;
		}else{
			//If the date was manually included, just remove it from the included dates
			unset($event_details['include'][$key]);
		}

		//Remove the date from the occurrences
		if( isset($event_details['_occurrences'][$event_id]) ){
			unset($event_details['_occurrences'][$event_id]);
		}

		//Update post meta and delete date from events table
		update_post_meta( $post_id,'_eventorganiser_event_schedule',$event_details);					
		$del = $wpdb->get_results($wpdb->prepare("DELETE FROM {$wpdb->eo_events} WHERE post_id=%d AND event_id=%d",$post_id,$event_id));

		//Clear cache
		_eventorganiser_delete_calendar_cache();

		return true;
	}
?>
