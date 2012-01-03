<?php
/**
* Event related functions
*/

/**
* Retrieve list of event matching criteria.
* The defaults are as follows:
* 	'numberposts' - default is - 1 (all events)
*	'orderby' - default is 'eventstart'
*	'showpastevents' - default is set in Event Organiser settings
* Other defaults are set by WordPress
*
* The function sets the following parameters
* 	'post_type' - is set to 'event'
* 	'suppress_filters' - is set to false
*
* @since 1.0.0
* @uses WordPress' get_posts
* @param array $args Optional. Overrides defaults.
* @return array List of events.
*/
function eo_get_events($args=array()){

	//In case an empty string is passed
	if(empty($args))
		$args = array();

	//These are preset to ensure the plugin functions properly
	$required = array('post_type'=> 'event','suppress_filters'=>false);

	//These are the defaults
	$eo_settings_array= get_option('eventorganiser_options');

	$defaults = array(
		'numberposts'=>-1,
		'orderby'=> 'eventstart',
		'order'=> 'ASC',
		'showrepeats'=>1,
		'showpastevents'=>$eo_settings_array['showpast']); 	
	
	//Construct the query array	
	$query_array = array_merge($defaults,$args,$required);

	if($query_array){
		$events=get_posts($query_array);
		return $events;
	}

		return false;
}



/**
* Retrieve a row object of the event by ID of the event
*
* @since 1.0.0

* @arg id - Optional. ID of the event.
* @arg occurrence - Optional. Integer, the occurrence number.
*
* @return row object of event's row in Events table
*/
function eo_get_by_postid($postid,$occurrence=0){
	global $eventorganiser_events_table, $wpdb;

	$querystr = $wpdb->prepare("
		SELECT * FROM  $eventorganiser_events_table 
		WHERE {$eventorganiser_events_table}.post_id=%d
		 AND ({$eventorganiser_events_table}.event_occurrence=%d)
		LIMIT 1",$postid,$occurrence);

	return $wpdb->get_row($querystr);
}



/**
* Returns the start date of occurrence of event.
*
* If used inside the loop, with no id no set, returns start date of
* current event occurrence.
*
* @arg format - Optional. String of format as accepted by PHP date
* @arg id - Optional. ID of the event.
* @arg occurrence - Optional. Integer, the occurrence number.
* @returns String - the start date formated to given format
* 
 * @since 1.0.0
 */
function eo_get_the_start($format='d-m-Y',$id='',$occurrence=0){
	global $post;
	$event = $post;

	if(isset($id)&&$id!='') $event = eo_get_by_postid($id,$occurrence);

	$date = esc_html($event->StartDate).' '.esc_html($event->StartTime);

	if(empty($date)||$date==" ")
		return false;

	return eo_format_date($date,$format);
}

/**
* Echos the start date of occurence of event
*
 * @since 1.0.0
 * @uses eo_get_the_start
 */
function eo_the_start($format='d-m-Y',$id='',$occurrence=0){
	echo eo_get_the_start($format,$id,$occurrence);
}


/**
* Returns the end date of occurrence of event.
*
* If used inside the loop, with no id no set, returns end date of
* current event occurrence.
*
* @arg format - Optional. String of format as accepted by PHP date
* @arg id - Optional. ID of the event.
* @arg occurrence - Optional. Integer, the occurrence number.
* @returns String - the end date formated to given format
*
 * @since 1.0.0
 */
function eo_get_the_end($format='d-m-Y',$id='',$occurrence=0){
	global $post;
	$event = $post;

	if(isset($id)&&$id!='') $event = eo_get_by_postid($id);

	$date = esc_html($event->EndDate).' '.esc_html($event->FinishTime);

	if(empty($date)||$date==" ")
		return false;

	return eo_format_date($date,$format);
}

/**
* Echos the end date of occurence.
*
 * @since 1.0.0
 * @uses eo_get_the_end
 */
function eo_the_end($format='d-m-Y',$id=''){
	echo eo_get_the_end($format,$id);
}


/**
* Gets the formated date of next occurrence of an event
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
* @return string the formatted date or FALSE if no date exists
*
 * @since 1.0.0
 */
function eo_get_next_occurrence($format='d-m-Y',$id=''){
	global $post,$eventorganiser_events_table, $wpdb;

	if(!isset($id) || $id=='') $id = $post->ID;
	
	//Retrieve the blog's local time and create the date part
	$blog_now = new DateTIme(null,EO_Event::get_timezone());
	$now_date =$blog_now->format('Y-m-d');
	$now_time =$blog_now->format('H:i:s');
	
	$querystr =$wpdb->prepare("
		SELECT StartDate, StartTime
		FROM  $eventorganiser_events_table
		WHERE {$eventorganiser_events_table}.post_id=%d
		AND ( 
			({$eventorganiser_events_table}.StartDate > %s) OR
			({$eventorganiser_events_table}.StartDate = %s AND {$eventorganiser_events_table}.StartTime >= %s))
		ORDER BY {$eventorganiser_events_table}.StartDate ASC
		LIMIT 1",$id,$now_date,$now_date,$now_time);

	$nextoccurrence  = $wpdb->get_row($querystr);
	if($nextoccurrence !==null){
		$date = esc_html($nextoccurrence->StartDate).' '.esc_html($nextoccurrence->StartTime);
		if(empty($date)||$date==" ")
			return false;

		return eo_format_date($date,$format);
	}		
}

/**
* Echos the formated date of next occurrence
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 */
function eo_next_occurence($format='',$id=''){
	echo eo_get_next_occurence($format,$id);
}

/**
* Returns the formated date of first occurrence of an event
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
*
* @return string the formatted date 
*
 * @since 1.0.0
 */
function  eo_get_schedule_start($format='d-m-Y',$id=''){
	global $post;
	$event = $post;

	if(isset($id)&&$id!='') $event = eo_get_by_postid($id);

	$date = esc_html($event->reoccurrence_start);

	if(empty($date)||$date==" ")
		return false;

	return eo_format_date($date,$format);
}

/**
* Echos the formated date of the first occurrence
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
*
* @uses eo_get_schedule_start
*
 * @since 1.0.0
 */
function  eo_schedule_start($format='d-m-Y',$id=''){
	echo eo_get_schedule_start($format,$id);
}



/**
* Returns the formated date of the last occurrence of an event
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
*
* @return string the formatted date 
*
 * @since 1.0.0
 */
function eo_get_schedule_end($format='d-m-Y',$id=''){
	global $post;
	$event = $post;

	if(isset($id)&&$id!='') $event = eo_get_by_postid($id);

	$date = esc_html($event->reoccurrence_end);

	if(empty($date)||$date==" ")
		return false;

	return eo_format_date($date,$format);
}

/**
* Echos the formated date of the last occurrence
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
*
* @uses eo_get_schedule_start
*
 * @since 1.0.0
 */
function  eo_schedule_end($format='d-m-Y',$id=''){
	echo eo_get_schedule_end($format,$id);
}


/**
* Returns true if event reoccurs or false if it is a one time event.
*
 * @since 1.0.0
 */
function eo_reoccurs($id=''){
	global $post;
	$event = $post;

	if(isset($id)&&$id!='') $event = eo_get_by_postid($id);

	if(isset($event->event_schedule)&&$event->event_schedule=='once')
		return true;

	return false;
}



/**
* Formats a date string in format 'YYYY-MM-DD' format into a specified format
*
 * @since 1.0.0
 */
function eo_format_date($dateString='',$format='d-m-Y'){
	if($format!=''&& $dateString!=''){
		$datetime = new DateTime($dateString);
		return $datetime->format($format);
	}
	return false;
}


/**
* Returns an array with details of the event's reoccurences
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 */
function eo_get_reoccurence($format='',$id=''){
	global $post, $allowed_reoccurs;
	$event = $post;
	if(isset($id)&& $id!='') $event = eo_get_by_postid($id); 

	if($event->event_id){
		$return['frequency'] = absint($event->event_frequency);
		$return['reoccurrence'] =esc_html($event->event_schedule);
			if($return['reoccurrence']=='weekly'){
				$return['meta'] = array_map('esc_attr',unserialize($event->event_schedule_meta));
			}else{
				$return['meta'] = esc_attr($event->event_schedule_meta);
			}
		$return['start'] = new DateTIme(esc_html($event->reoccurrence_start).' '.esc_html($event->StartTime));
		$return['end'] = new DateTIme(esc_html($event->reoccurrence_end).' '.esc_html($event->FinishTime));

	}else{
		return;
	}
	
	return $return; 
}


/**
* Returns a summary of the events schedule.
*
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 */
function eo_get_schedule_summary($id=''){
	global $post;

	$ical2day = array(
		'SU'=>'Sunday',
		'MO'=>'Monday',
		'TU'=>'Tuesday',
		'WE'=>'Wednesday',
		'TH'=>'Thursday',
		'FR'=>'Friday',
		'SA'=>'Saturday',
		'SU'=>'Sunday',
	);

	$nth= array(
		'last','','first','second','third','fourth',
	);

	$reoccur = eo_get_reoccurence($id);
	if(empty($reoccur))
		return false;

	$return='';


	if($reoccur['reoccurrence']=='once'){
		$return = 'one time only';
	}elseif(isset($reoccur['frequency']) && isset($reoccur['reoccurrence'])){
		if($reoccur['frequency']>1){
			$return =$reoccur['frequency'].' '.EO_Event::$allowed_reoccurs[$reoccur['reoccurrence']].'s';
		}else{
			$return = EO_Event::$allowed_reoccurs[$reoccur['reoccurrence']];
		}

		switch($reoccur['reoccurrence']):
			case 'weekly':
				$weekdays = $reoccur['meta'];
				foreach($weekdays as $ical_day){
					$days[] =  $ical2day[$ical_day];
					}
				$return .=' on '.implode(', ',$days);
				break;

			case 'monthly':
				$return .= ' on the ';
				$bymonthday =preg_match('/^BYMONTHDAY=(\d{1,2})/' ,$reoccur['meta'],$matches);

				if($bymonthday ){
					$d = intval($matches[1]);
					$m =intval($reoccur['start']->format('n'));
					$y =intval($reoccur['start']->format('Y'));
					$return .= $reoccur['start']->setDate($y,$m,$d)->format('jS');

				}elseif($reoccur['meta']=='date'){
					$return .= $reoccur['start']->format('jS');

				}else{
					$byday = preg_match('/^BYDAY=(-?\d{1,2})([a-zA-Z]{2})/' ,$reoccur['meta'],$matches);
					if($byday):
						$n=intval($matches[1])+1;
						$return .=$nth[$n].' '.$ical2day[$matches[2]];
					else:
						$bydayOLD = preg_match('/^(-?\d{1,2})([a-zA-Z]{2})/' ,$reoccur['meta'],$matchesOLD);
						$n=intval($matchesOLD[1])+1;
						$return .=$nth[$n].' '.$ical2day[$matchesOLD[2]];
					endif;
				}
				break;

		endswitch;
				
		$return .= ' until '.$reoccur['end']->format('M, jS Y');
	}
	
	return $return; 
}

/**
* Echos the summary of the events schedule.
*
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 */
function eo_display_reoccurence($id=''){
	echo eo_get_schedule_summary($id);
}


