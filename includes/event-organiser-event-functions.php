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
	$defaults = array(
		'numberposts'=>-1,
		'orderby'=> 'eventstart',
		'order'=> 'ASC',
		'showrepeats'=>1,
		'group_events_by'=>'',
		'showpastevents'=>true); 	
	
	//Construct the query array	
	$query_array = array_merge($defaults,$args,$required);

	if(!empty($query_array['venue'])){	
		$query_array['event-venue'] = $query_array['venue'];
		unset($query_array['venue']);
	}
	
	//Ensure all date queries are yyyy-mm-dd format. Process relative strings ('today','tomorrow','+1 week')
	$dates = array('ondate','event_start_after','event_start_before','event_end_before','event_end_after');
	foreach($dates as $prop):
		if(!empty($query_array[$prop]))
			$query_array[$prop] = eo_format_date($query_array[$prop],'Y-m-d');
	endforeach;
	
	//Make sure 'false' is passed as integer 0
	if(strtolower($query_array['showpastevents'])==='false') $query_array['showpastevents']=0;

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
function eo_get_by_postid($postid,$occurrence=0, $occurrence_id=0){
	global $wpdb;

	if( !empty($occurrence_id) ){
		$column = 'event_id';
		$value = $occurrence_id;
	}else{
		//Backwards compatibility!
		$column = 'event_occurrence';
		$value = $occurrence;
	}

	$querystr = $wpdb->prepare("
		SELECT StartDate,EndDate,StartTime,FinishTime FROM  {$wpdb->eo_events} 
		WHERE {$wpdb->eo_events}.post_id=%d
		 AND ({$wpdb->eo_events}.{$column}=%d)
		LIMIT 1",$postid,$value);

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
function eo_get_the_start($format='d-m-Y',$post_id='',$occurrence=0, $occurrence_id=0){
	global $post;
	$event = $post;

	if( !empty($occurrence) ){
		_deprecated_argument( __FUNCTION__, '1.5.6', 'Third argument is depreciated. Please use a fourth argument - occurrence ID. Available from $post->event_id' );

		//Backwards compatiblity
		if( !empty($post_id) ) $event = eo_get_by_postid($post_id,$occurrence, $occurrence_id);	
	
		if(empty($event)) 
			return false;
	
		$date = trim($event->StartDate).' '.trim($event->StartTime);

		if(empty($date)||$date==" ")
			return false;

		return eo_format_date($date,$format);
	}

	$occurrence_id = (int) ( empty($occurrence_id) && isset($event->occurrence_id)  ? $event->occurrence_id : $occurrence_id);

	$occurrences = eo_get_the_occurrences_of($post_id);

	if( !$occurrences || !isset($occurrences[$occurrence_id]) )
		return false;

	$start = $occurrences[$occurrence_id]['start'];

	return eo_format_datetime($start,$format);
}


function eo_get_the_occurrence_start($format='d-m-Y',$occurrence_id){
	global $wpdb;

	$querystr = $wpdb->prepare("
		SELECT StartDate,StartTime FROM  {$wpdb->eo_events} 
		WHERE {$wpdb->eo_events}.event_id=%d
		LIMIT 1",$occurrence_id);

	$occurrence =  $wpdb->get_row($querystr);

	if( !$occurrence )
		return false;

	$date = trim($occurrence->StartDate).' '.trim($occurrence->StartTime);

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
function eo_the_start($format='d-m-Y',$id='',$occurrence=0,	$occurrence_id=0){
	echo eo_get_the_start($format,$id,$occurrence, $occurrence_id);
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
function eo_get_the_end($format='d-m-Y',$post_id='',$occurrence=0, $occurrence_id=0){
	global $post;
	$event = $post;

	if( !empty($occurrence) ){
		_deprecated_argument( __FUNCTION__, '1.5.6', 'Third argument is depreciated. Please use a fourth argument - occurrence ID. Available from $post->event_id' );

		//Backwards compatiblity
		if( !empty($post_id) ) $event = eo_get_by_postid($post_id,$occurrence, $occurrence_id);	
	
		if(empty($event)) 
			return false;
	
		$date = trim($event->EndDate).' '.trim($event->FinishTime);

		if(empty($date)||$date==" ")
			return false;

		return eo_format_date($date,$format);
	}
	$occurrence_id = (int) ( empty($occurrence_id) && isset($event->occurrence_id)  ? $event->occurrence_id : $occurrence_id);

	$occurrences = eo_get_the_occurrences_of($post_id);

	if( !$occurrences || !isset($occurrences[$occurrence_id]) )
		return false;

	$start = $occurrences[$occurrence_id]['end'];

	return eo_format_datetime($start,$format);
}

/**
* Echos the end date of occurence.
*
 * @since 1.0.0
 * @uses eo_get_the_end
 */
function eo_the_end($format='d-m-Y',$post_id='',$occurrence=0, $occurrence_id=0){
	echo eo_get_the_end($format,$post_id,$occurrence, $occurrence_id);
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
function eo_get_next_occurrence($format='d-m-Y',$post_id=''){
	$next_occurrence = eo_get_next_occurrence_of($post_id);

	if( !$next_occurrence )
		return false;

	return eo_format_datetime($next_occurrence['start'],$format);
}

function eo_get_next_occurrence_of($post_id=''){
	global $wpdb;
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);
	
	//Retrieve the blog's local time and create the date part
	$blog_now = new DateTIme(null, eo_get_blog_timezone() );
	$now_date =$blog_now->format('Y-m-d');
	$now_time =$blog_now->format('H:i:s');
	
	$nextoccurrence  = $wpdb->get_row($wpdb->prepare("
		SELECT StartDate, StartTime, EndDate, FinishTime
		FROM  {$wpdb->eo_events}
		WHERE {$wpdb->eo_events}.post_id=%d
		AND ( 
			({$wpdb->eo_events}.StartDate > %s) OR
			({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime >= %s))
		ORDER BY {$wpdb->eo_events}.StartDate ASC
		LIMIT 1",$post_id,$now_date,$now_date,$now_time));

	if( ! $nextoccurrence )
		return false;

	$start = new DateTime($nextoccurrence->StartDate.' '.$nextoccurrence->StartTime);
	$end = new DateTime($nextoccurrence->EndDate.' '.$nextoccurrence->FinishTime);

	return compact('start','end');
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
* Return true is the event is an all day event.
*
* @param id - Optional, the event series (post) ID, 
* @return bol - True if event runs all day, or false otherwise
 * @since 1.2
 */
function eo_is_all_day($post_id=''){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if( empty($post_id) ) 
		return false;

	$schedule = eo_get_event_schedule($post_id);

	return (bool) $schedule['all_day'];
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
function  eo_get_schedule_start($format='d-m-Y',$post_id=0){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);
	$schedule = eo_get_event_schedule($post_id);
	return eo_format_datetime($schedule['schedule_start'],$format);
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
function eo_get_schedule_last($format='d-m-Y',$post_id=''){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);
	$schedule = eo_get_event_schedule($post_id);
	return eo_format_datetime($schedule['schedule_last'],$format);
}


/**
* Echos the formated date of the last occurrence
*
* @param string - the format to use, using PHP Date format
* @param id - Optional, the event (post) ID, 
*
* @uses eo_get_schedule_last
*
 * @since 1.0.0
 */
function  eo_schedule_last($format='d-m-Y',$post_id=''){
	echo eo_get_schedule_last($format,$post_id);
}


/**
* Returns true if event reoccurs or false if it is a one time event.
* @param integer - event (post) ID
* @return boolean - true if event a reoccurring event
 * @since 1.0.0
 */
function eo_reoccurs($post_id=''){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if( empty($post_id) ) 
		return false;

	$schedule = eo_get_event_schedule($post_id);
	
	return ($schedule['schedule'] != 'once');
}


/**
* Returns a summary of the events schedule.
*
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 */
function eo_get_schedule_summary($id=0){
	global $post,$wp_locale;

	$ical2day = array('SU'=>	$wp_locale->weekday[0],'MO'=>$wp_locale->weekday[1],'TU'=>$wp_locale->weekday[2], 'WE'=>$wp_locale->weekday[3], 
						'TH'=>$wp_locale->weekday[4],'FR'=>$wp_locale->weekday[5],'SA'=>$wp_locale->weekday[6]);

	$nth= array(__('last','eventorganiser'),'',__('first','eventorganiser'),__('second','eventorganiser'),__('third','eventorganiser'),__('fourth','eventorganiser'));

	$reoccur = eo_get_event_schedule($id);

	if(empty($reoccur))
		return false;

	$return='';

	if($reoccur['schedule']=='once'){
		$return = __('one time only','eventorganiser');

	}elseif($reoccur['schedule']=='custom'){
		$return = __('custom reoccurrence','eventorganiser');

	}else{
		switch($reoccur['schedule']):

			case 'daily':
				if($reoccur['frequency']==1):
					$return .=__('every day','eventorganiser');
				else:
					$return .=sprintf(__('every %d days','eventorganiser'),$reoccur['frequency']);
				endif;
				break;

			case 'weekly':
				if($reoccur['frequency']==1):
					$return .=__('every week on','eventorganiser');
				else:
					$return .=sprintf(__('every %d weeks on','eventorganiser'),$reoccur['frequency']);
				endif;

				foreach( $reoccur['schedule_meta'] as $ical_day){
					$days[] =  $ical2day[$ical_day];
					}
				$return .=' '.implode(', ',$days);
				break;

			case 'monthly':
				if($reoccur['frequency']==1):
					$return .=__('every month on the','eventorganiser');
				else:
					$return .=sprintf(__('every %d months on the','eventorganiser'),$reoccur['frequency']);
				endif;
				$return .= ' ';
				$bymonthday =preg_match('/^BYMONTHDAY=(\d{1,2})/' ,$reoccur['schedule_meta'],$matches);

				if( $bymonthday  ){
					$d = intval($matches[1]);
					$m =intval($reoccur['schedule_start']->format('n'));
					$y =intval($reoccur['schedule_start']->format('Y'));
					$reoccur['start']->setDate($y,$m,$d);
					$return .= $reoccur['schedule_start']->format('jS');

				}elseif($reoccur['schedule_meta']=='date'){
					$return .= $reoccur['schedule_start']->format('jS');

				}else{
					$byday = preg_match('/^BYDAY=(-?\d{1,2})([a-zA-Z]{2})/' ,$reoccur['schedule_meta'],$matches);
					if($byday):
						$n=intval($matches[1])+1;
						$return .=$nth[$n].' '.$ical2day[$matches[2]];
					else:
						var_dump($reoccur['schedule_meta']);
						$bydayOLD = preg_match('/^(-?\d{1,2})([a-zA-Z]{2})/' ,$reoccur['schedule_meta'],$matchesOLD);
						$n=intval($matchesOLD[1])+1;
						$return .=$nth[$n].' '.$ical2day[$matchesOLD[2]];
					endif;
				}
				break;
			case 'yearly':
				if($reoccur['frequency']==1):
					$return .=__('every year','eventorganiser');
				else:
					$return .=sprintf(__('every %d years','eventorganiser'),$reoccur['frequency']);
				endif;
				break;

		endswitch;
				
		$return .= ' '.__('until','eventorganiser').' '. eo_format_datetime($reoccur['schedule_last'],'M, jS Y');
	}
	
	return $return; 
}

/**
* Echos the summary of the events schedule.
*
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 */
function eo_display_reoccurence($post_id=''){
	echo eo_get_schedule_summary($post_id);
}


/* Returns an array of occurrences. Each occurrence is an array with 'start' and 'end' key. 
 *  Both of these hold a DateTime object (for the start and end of that occurrence respecitvely).
*
* @param id - Optional, the event (post) ID, 
* @return array|false - Array of arrays of DateTime objects of the start and end date-times of occurences. False if none exist.
 * @since 1.5
 */
function eo_get_the_occurrences_of($post_id=''){
	global $wpdb;

	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id)) 
		return false;

	$occurrences = wp_cache_get( 'eventorganiser_occurrences_'.$post_id );
	if( !$occurrences ){

		$results = $wpdb->get_results($wpdb->prepare("
			SELECT event_id, StartDate,StartTime,EndDate,FinishTime FROM {$wpdb->eo_events} 
			WHERE {$wpdb->eo_events}.post_id=%d ORDER BY StartDate ASC",$post_id));
	
		if( !$results )
			return false;

		$occurrences=array();
		foreach($results as $row):
			$occurrences[$row->event_id] = array(
				'start' => new DateTime($row->StartDate.' '.$row->StartTime, eo_get_blog_timezone()),
				'end' => new DateTime($row->EndDate.' '.$row->FinishTime, eo_get_blog_timezone())
			);
		endforeach;
		wp_cache_set( 'eventorganiser_occurrences_'.$post_id, $occurrences );
	}

	return $occurrences;
}

function eo_get_category_color($term){
	return eo_get_category_meta($term,'color');
}

/**
* Returns the colour of a category associated with the event
*
 * @since 1.6
* @param int $post_id - the event (post) ID. Leave blank to use in loop.
* @return string The colour of the category in HEX format
 */
function eo_get_event_color($post_id=0){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if( empty($post_id) )
		return false;

	$color = false;

	$terms = get_the_terms($post_id, 'event-category');
	if( $terms && !is_wp_error($terms) ){
		foreach ($terms as $term):	
			if( ! empty($term->color) ){
				$color_code = ltrim($term->color, '#');
				if ( ctype_xdigit($color_code) && (strlen($color_code) == 6 || strlen($color_code) == 3)){
					$color = '#'.$color_code;
					break;
                       		}
			}
		endforeach;
	}

	return apply_filters('eventorganiser_event_color',$color,$post_id);
}

function eo_get_event_classes($post_id=0, $occurrence_id=0){
	global $post;

	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id );
	$occurrence_id = (int) ( empty($occurrence_id) && isset($post->occurrence_id)  ? $post->occurrence_id : $occurrence_id );

	$event_classes = array();
			
	//Add venue class
	if( eo_get_venue_slug() )
		$event_classes[] = 'eo-event-venue-'.eo_get_venue_slug();

	//Add category classes
	$cats= get_the_terms(get_the_ID(), 'event-category');
	if( $cats && !is_wp_error($cats) ){	
		foreach ($cats as $cat)
			$event_classes[] = 'eo-event-cat-'.$cat->slug;
	}

	//Add 'time' class
	$start = eo_get_the_start(DATETIMEOBJ, $post_id, null, $occurrence_id);
	$end= eo_get_the_end(DATETIMEOBJ, $post_id, null, $occurrence_id);
	$now = new DateTime('now',eo_get_blog_timezone());
	if( $start > $now ){
		$event_classes[] = 'eo-event-future';
	}elseif( $end < $now ){
		$event_classes[] = 'eo-event-past';
	}else{
		$event_classes[] = 'eo-event-running';
	}

	return  apply_filters('eventorganiser_event_classes', array_unique($event_classes), $post_id, $occurrence_id);
}

function eo_is_event_taxonomy(){
	return (is_tax(array('event-category','event-tag','event-venue')));
}


function eo_has_event_started($id='',$occurrence=0){
	$tz = eo_get_blog_timezone();
	$start = new DateTime(eo_get_the_start('d-m-Y H:i',$id,$occurrence), $tz);
	$now = new DateTime('now', $tz);

	return ($start <= $now );
}

function eo_has_event_finished($id='',$occurrence=0){
	$tz = eo_get_blog_timezone();
	$end = new DateTime(eo_get_the_end('d-m-Y H:i',$id,$occurrence), $tz);
	$now = new DateTime('now', $tz);

	return ($end <= $now );
}


/**
* Returns a the url which adds a particular occurrence of an event to
* a google calendar.
* Must be used inside the loop
*
 * @since 1.2.0
 */
function eo_get_the_GoogleLink(){
	global $post;
	setup_postdata($post);

	if(empty($post)|| get_post_type($post )!='event'){ 
		wp_reset_postdata();
		return false;
	}

	$start = eo_get_the_start(DATETIMEOBJ); 
	$end = eo_get_the_start(DATETIMEOBJ); 

	if(eo_is_all_day()):
		$end->modify('+1 second');
		$format = 'Ymd';
	else:		
		$format = 'Ymd\THis\Z';
		$start->setTimezone( new DateTimeZone('UTC') );
		$end->setTimezone( new DateTimeZone('UTC') );
	endif;

	$excerpt = apply_filters('the_excerpt_rss', get_the_excerpt());

	$url = add_query_arg(array(
		'text'=>get_the_title(), 
		'dates'=>$start->format($format).'/'.$end->format($format),
		'trp'=>false,
		'details'=> esc_html($excerpt),
		'sprop'=>get_bloginfo('name')
	),'http://www.google.com/calendar/event?action=TEMPLATE');

	$venue_id = eo_get_venue();
	if($venue_id):
		$venue =eo_get_venue_name($venue_id).", ".implode(', ',eo_get_venue_address($venue_id));
		$url = add_query_arg('location',$venue, $url);
	endif;

	wp_reset_postdata();
	return $url;
}

function eo_get_events_feed(){
	return get_feed_link('eo-events');
}


function eo_event_category_dropdown( $args = '' ) {
	$defaults = array(
		'show_option_all' => '', 
		'echo' => 1,
		'selected' => 0, 
		'name' => 'event-category', 
		'id' => '',
		'class' => 'postform event-organiser event-category-dropdown event-dropdown', 
		'tab_index' => 0, 
	);

	$defaults['selected'] =  (is_tax('event-category') ? get_query_var('event-category') : 0);
	$r = wp_parse_args( $args, $defaults );
	$r['taxonomy']='event-category';
	extract( $r );

	$tab_index_attribute = '';
	if ( (int) $tab_index > 0 )
		$tab_index_attribute = " tabindex=\"$tab_index\"";

	$categories = get_terms($taxonomy, $r ); 
	$name = esc_attr( $name );
	$class = esc_attr( $class );
	$id = $id ? esc_attr( $id ) : $name;

	$output = "<select style='width:150px' name='$name' id='$id' class='$class' $tab_index_attribute>\n";
	
	if ( $show_option_all ) {
		$output .= '<option '.selected($selected,0,false).' value="0">'.$show_option_all.'</option>';
	}

	if ( ! empty( $categories ) ) {
		foreach ($categories as $term):
			$output .= '<option value="'.$term->slug.'"'.selected($selected,$term->slug,false).'>'.$term->name.'</option>';
		endforeach; 
	}
	$output .= "</select>\n";

	if ( $echo )
		echo $output;

	return $output;
}
?>
