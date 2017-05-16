<?php
/**
 * Event related functions
 *
 * @package event-functions
*/

/**
* Retrieve list of events matching criteria.
*
* This function is a wrapper for get_posts(). **As such parameters from {@see `get_posts()`} and {@link https://codex.wordpress.org/Class_Reference/WP_Query `WP_Query`} can also be used**.
* Their default values are as indicated by the relevant codex page unless specified below. 
* You can also use {@see `get_posts()`} and {@link https://codex.wordpress.org/Class_Reference/WP_Query `WP_Query`} instead to retrieve events.
* 
* The `$args` array can include the following.
*
* * **event_start_before** - default: `null` Events that start before the given date
* * **event_end_before** - default: `null` Events that end before the given date
* * **event_start_after** - default: `null` Events that start before the given date
* * **event_end_after** - default: `null`. Events that end after the given date. This argument, and those above expect dates in **Y-m-d** format or {@link http://docs.wp-event-organiser.com/querying-events/date-formats/ relative dates}.
* * **ondate** - Events that start on this specific date given as a string in YYYY-MM-DD format or {@link http://docs.wp-event-organiser.com/querying-events/date-formats/ relative format}. default: `null` 
* * **numberposts** - default is `-1` (all events)
* * **orderby** - default is `eventstart`. You can also have `eventend`.
* * **showpastevents** - default is `true` (it's recommended to use `event_start_after=today` or `event_end_after=today` instead)
* * **event-category** - the slug of an event category. Get events for this category
* * **event-venue** - the slug of an event venue. Get events for this venue
* * **event-tag** - the slug of an event venue. Get events for this tag
* * **group_events_by** - If set to 'series', only the first matching occurrence of a recurring event is returned.  
* * **bookee_id** - (int) ID of user to retrieve events for which the user is attending 
* *
*
* Additional values are also permitted for 'orderby' parameter
* * **eventstart** - Order by event start date.
* * **eventend** - Order by event end date.
*
*
* For more complex event/venue queries you can use tax_query or venue_query ( http://wp-event-organiser.com/pro-features/event-venue-queries/ ).
* 
* If you use `get_posts()` or `WP_Query` instead then you should ensure the following:
*
* * **post_type** - is set to 'event'
* * **suppress_filters** - is set to false
*
*
* ###Example
*
* <code>
*      <?php
*       $events = eo_get_events(array(
*               'numberposts'=>5,
*               'event_start_after'=>'today',
*               'showpastevents'=>true,//Will be deprecated, but set it to true to play it safe.
*          ));
*
*        if($events):
*           echo '<ul>'; 
*           foreach ($events as $event):
*                //Check if all day, set format accordingly
*                $format = ( eo_is_all_day($event->ID) ? get_option('date_format') : get_option('date_format').' '.get_option('time_format') );
*                printf(
*                   '<li><a href="%s"> %s </a> on %s </li>',
*                   get_permalink($event->ID),
*                   get_the_title($event->ID),
*                   eo_get_the_start( $format, $event->ID, $event->occurrence_id )
*                );                           
*           endforeach; 
*           echo '</ul>'; 
*        endif; 
*       ?>
* </code>
* 
* As previously noted, function uses WordPress' built-in get_posts and all arguments available to get_posts are available to this function. This allows for potentially complex queries.
* For instance, to get the next 5 events which aren't in the category with slug 'comedy':
* 
* <code>
* $events = eo_get_events(array(
*                     'numberposts'=>3,
*                     'tax_query'=>array( array(
*                          'taxonomy'=>'event-category',
*                          'operator' => 'NOT IN',
*                          'field'=>'slug',
*                          'terms'=>array('comedy')
*                          ))
* </code>
* @since 1.0.0
* @uses get_posts()
* @package event-query-functions
* @link https://gist.github.com/4165380 List up-coming events
 * @link https://gist.github.com/4190351 Adds up-coming events in the venue tooltip
 * @link http://docs.wp-event-organiser.com/querying-events/date-formats/ Using relative dates in event queries
 * @link http://wp-event-organiser.com/forums/topic/retrieving-events-using-wp_query/ Retrieving events with `WP_Query`
 * @link http://wp-event-organiser.com/pro-features/event-venue-queries/ Event-Venue queries
* @param array $args Event query arguments.
* @return array An array of event (post) objects. Like get_posts. In case of failure it returns null.
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
	
	//Ensure ondate query is yyyy-mm-dd format. Process relative strings ('today','tomorrow','+1 week')
	if( !empty( $query_array['ondate'] ) ){
		$query_array['ondate'] = eo_format_date( $query_array['ondate'], 'Y-m-d' );
	}
	
	//Make sure 'false' is passed as integer 0
	if(strtolower($query_array['showpastevents'])==='false') $query_array['showpastevents']=0;

	if($query_array){
		$events=get_posts($query_array);
		return $events;
	}

		return null;
}


/**
* Retrieve a row object from events table of the event by ID of the event
* @access private
*@ignore
* @since 1.0
*
* @param int $post_id Post ID of the event.
* @param int $deprecated The occurrence number. Deprecated use $occurrence_id
* @param int $occurrence_id The occurrence ID
* @return row object of event's row in Events table
*/
function eo_get_by_postid($post_id,$deprecated=0, $occurrence_id=0){
	global $wpdb;

	if( !empty($occurrence_id) ){
		$column = 'event_id';
		$value = $occurrence_id;
	}else{
		//Backwards compatibility!
		$column = 'event_occurrence';
		$value = $deprecated;
	}

	$querystr = $wpdb->prepare("
		SELECT StartDate,EndDate,StartTime,FinishTime FROM  {$wpdb->eo_events} 
		WHERE {$wpdb->eo_events}.post_id=%d
		 AND ({$wpdb->eo_events}.{$column}=%d)
		LIMIT 1",$post_id,$value);

	return $wpdb->get_row($querystr);
}


/**
 * Returns the start date of occurrence of event.
 *
 * If used inside the loop, with no id no set, returns start date of
 * current event occurrence.
 *
 * **3.0.0 Update:** This function used to accept 4 arguments (versions 1.5.6 - 2.11.1), with
 * the third (null) argument deprecated. While the old behaviour shall still work, it is recommended
 * that pass a maximum of three arguments, as shown below, with the third argument (not the fourth)
 * specifying the occurrence ID.
 *
 * ### Examples
 * Inside the loop, you can output the start date of event (occurrence)
 * <code>
 *       <?php echo eo_get_the_start( 'jS M Y' ); ?>
 * </code>
 * Get the start date of the event with id 7 and occurrence ID 3
 * <code>
 *       <?php $date = eo_get_the_start ( 'jS M Y', 7, 3 ); ?>
 * </code>
 * Print a list of upcoming events with their start and end date
 * <code>
 *     //Get upcoming events
 *     $events = eo_get_events(array(
 *          'numberposts'        => 5,
 *          'events_start_after' => 'today',
 *          'showpastevents'     => true,
 *       ));
 *
 *     if( $events ){
 *         echo '<ul>';
 *         foreach( $events as $event ){
 *           printf("<li><a href='%s' >%s</a> from %s to %s </li>",
 *                get_the_permalink( $post->ID ),
 *                get_the_title( $post->ID ),
 *                eo_get_the_start( 'jS F Y', $post->ID, $post->occurrence_id ),
 *                eo_get_the_end( 'jS F Y', $post->ID, $post->occurrence_id )
 *           );
 *          }
 *         echo '</ul>';
 *     }else{
 *         echo 'No Upcoming Events';
 *     }
 * </code>
 *
 * @since 3.0.0 Third argument repurposed for the occurrence ID. Fourth argument can still be used, but discouraged.
 * @since 1.5.6 Third argument deprecated, use fourth argument to pass occurrence ID.
 * @since 1.0.0
 * @package event-date-functions
 *
 * @param string $format String of format as accepted by PHP date or the constant DATETIMEOBJ to return a DateTime object
 * @param int $post_id Post ID of the event
 * @param int $occurrence_id  The occurrence ID
 * @param int $deprecated (Was) the occurrence id, supply this as the third argument
 * @return string|DateTime the start date formated to given format, as accepted by PHP date or a DateTime object if DATETIMEOBJ is given as format.
 */
function eo_get_the_start( $format = 'd-m-Y', $post_id = 0, $occurrence_id = 0, $deprecated = 0 ) {
	global $post;
	$event = $post;

	if( !empty( $deprecated ) && empty( $occurrence_id ) ){
		//_deprecated_argument( __FUNCTION__, '3.0.0', 'Fourth argument is depreciated. Please use the third argument, the occurrence ID, available from $post->occurrence_id' );
		$occurrence_id = $deprecated;
	}

	$post_id       = (int) ( empty($post_id) ? get_the_ID() : $post_id);
	$occurrence_id = (int) ( empty($occurrence_id) && isset($event->occurrence_id)  ? $event->occurrence_id : $occurrence_id);
	$occurrence    = eo_get_the_occurrence( $post_id, $occurrence_id );

	if ( ! $occurrence ) {
		return false;
	}

	$start = $occurrence['start'];

	/**
	 * Filters the value returned by `eo_get_the_start()`
	 *
	 * @param string|DateTime $formatted_start The DateTime object or formatted returned value (as determined by $format)
	 * @param DateTime $start The start date as a DateTime object
	 * @param string $format The format the start date should be returned in
	 * @param int $post_id Post ID of the event
	 * @param int $occurrence_id  The occurrence ID
	 */
	$formatted_date = apply_filters( 'eventorganiser_get_the_start', eo_format_datetime( $start, $format ), $start, $format, $post_id, $occurrence_id );
	return $formatted_date;
}

/**
 * Returns an array contining the start and end datetime of the specified occurrence.
 * 
 * The returned array contains two DateTime objects with the keys: 'start' and 'end',
 *
 * @since 3.0.0
 * @package event-date-functions
 * @param int $post_id
 * @param int $occurrence_id
 * @return boolean|array False if the occurrence was not found.
 */
function eo_get_the_occurrence( $post_id, $occurrence_id ) {

	global $wpdb;
	
	$occurrences = wp_cache_get( 'eventorganiser_occurrences_' . $post_id );
	
	if ( ! isset ( $occurrences[$occurrence_id] ) ) {
		
		$result = $wpdb->get_row($wpdb->prepare(
			"SELECT event_id, StartDate,StartTime,EndDate,FinishTime FROM {$wpdb->eo_events} 
			WHERE {$wpdb->eo_events}.post_id=%d AND {$wpdb->eo_events}.event_occurrence=%d ORDER BY StartDate ASC", 
			$post_id,
			$occurrence_id
		));
	
		if( ! $result ) {
			return false;
		}
	
		$occurrences[$occurrence_id] = array(
			'start' => new DateTime($result->StartDate.' '.$result->StartTime, eo_get_blog_timezone()),
			'end' => new DateTime($result->EndDate.' '.$result->FinishTime, eo_get_blog_timezone())
		);
		
		wp_cache_set( 'eventorganiser_occurrences_' . $post_id, $occurrences );
		
	}
	
	return $occurrences[$occurrence_id];
	
}

/**
* Returns the start date of occurrence of event an event, like {@see `eo_get_the_start()`}.
* The difference is that the occurrence ID *must* be supplied (event ID is not). 
* @since 1.6
* @ignore
*
* @param string $format String of format as accepted by PHP date
* @param int $occurrence_id  The occurrence ID
* @return string the start date formated to given format, as accepted by PHP date
 */
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
 * Echos the start date of occurrence of event
 *
 * @since 3.0.0 Third argument repurposed for the occurrence ID. Fourth argument can still be used, but discouraged.
 * @since 1.5.6 Third argument deprecated, use fourth argument to pass occurrence ID.
 * @since 1.0.0
 * @uses eo_get_the_start()
 * @package event-date-functions
 *
 * @param string $format String of format as accepted by PHP date
 * @param int $post_id Post ID of the event
 * @param int $occurrence_id The occurrence ID
 * @param int $deprecated Deprecated, use the third argument.
 */
function eo_the_start( $format = 'd-m-Y', $post_id = 0, $occurrence_id = 0, $deprecated = 0 ) {
	echo eo_get_the_start( $format, $post_id, $occurrence_id, $deprecated );
}


/**
 * Returns the end date of occurrence of event.
 *
 * If used inside the loop, with no id no set, returns end date of
 * current event occurrence.
 *
 * **3.0.0 Update:** This function used to accept 4 arguments (versions 1.5.6 - 2.11.1), with
 * the third (null) argument deprecated. While the old behaviour shall still work, it is deprecated
 * so you you should now pass a maximum of three arguments, as shown below, with the third argument
 * specifying the occurrence ID.
 *
 * ### Examples
 * Inside the loop, you can output the end date of event (occurrence)
 * <code>
 *       <?php echo eo_get_the_end( 'jS M Y' ); ?>
 * </code>
 * Get the end date of the event with id 7 and occurrence ID 3
 * <code>
 *       <?php $date = eo_get_the_end( 'jS M Y', 7, 3 ); ?>
 * </code>
 * Print a list of upcoming events with their start and end date
 * <code>
 *     //Get upcoming events
 *     $events = eo_get_events(array(
 *          'numberposts'        => 5,
 *          'events_start_after' => 'today',
 *          'showpastevents'     => true,
 *       ));
 *
 *     if( $events ){
 *         echo '<ul>';
 *         foreach( $events as $event ){
 *           printf("<li><a href='%s' >%s</a> from %s to %s </li>",
 *                get_the_permalink( $post->ID ),
 *                get_the_title( $post->ID ),
 *                eo_get_the_start( 'jS F Y', $post->ID, $post->occurrence_id ),
 *                eo_get_the_end( 'jS F Y', $post->ID, $post->occurrence_id )
 *           );
 *          }
 *         echo '</ul>';
 *     }else{
 *         echo 'No Upcoming Events';
 *     }
 * </code>
 *
 * @since 3.0.0 Third argument repurposed for the occurrence ID. Fourth argument can still be used, but discouraged.
 * @since 1.5.6 Third argument deprecated, use fourth argument to pass occurrence ID.
 * @since 1.0.0
 * @package event-date-functions
 * @param string $format String of format as accepted by PHP date
 * @param int $post_id The event (post) ID. Uses current event if empty.
 * @param int $occurrence_id  The occurrence ID
 * @param int $deprecated (Was) the occurrence id, supply this as the third argument
 * @return string|DateTime the end date formated to given format, as accepted by PHP date
 */
function eo_get_the_end( $format = 'd-m-Y', $post_id = 0, $occurrence_id = 0, $deprecated = 0  ) {
	global $post;
	$event = $post;

	if( !empty( $deprecated ) && empty( $occurrence_id ) ){
		//_deprecated_argument( __FUNCTION__, '3.0.0', 'Fourth argument is depreciated. Please use the third argument, the occurrence ID, available from $post->occurrence_id' );
		$occurrence_id = $deprecated;
	}
	
	$post_id       = (int) ( empty($post_id) ? get_the_ID() : $post_id);
	$occurrence_id = (int) ( empty($occurrence_id) && isset($event->occurrence_id)  ? $event->occurrence_id : $occurrence_id);
	$occurrence    = eo_get_the_occurrence( $post_id, $occurrence_id );

	if ( ! $occurrence ) {
		return false;
	}

	$end = $occurrence['end'];

	/**
	 * Filters the value returned by `eo_get_the_end()`
	 *
	 * @param string|DateTime $formatted_end The DateTime object or formatted returned value (as determined by $format)
	 * @param DateTime $end The end date as a DateTime object
	 * @param string $format The format the end date should be returned in
	 * @param int $post_id Post ID of the event
	 * @param int $occurrence_id  The occurrence ID
	 */
	$formatted_date = apply_filters( 'eventorganiser_get_the_end', eo_format_datetime( $end, $format ), $end, $format, $post_id, $occurrence_id );
	return $formatted_date;
}

/**
 * Echos the end date of occurrence of event
 * @since 3.0.0 Third argument repurposed for the occurrence ID. Fourth argument can still be used, but discouraged.
 * @since 1.5.6 Third argument deprecated, use fourth argument to pass occurrence ID.
 * @since 1.0.0
 * @uses eo_get_the_end()
 * @package event-date-functions
 *
 * @param string $format String of format as accepted by PHP date
 * @param int $post_id Post ID of the event
 * @param int $occurrence_id The occurrence ID
 * @param int $occurrence_id Deprecated, use the third argument.
 */
function eo_the_end( $format = 'd-m-Y', $post_id = 0, $occurrence_id = 0, $deprecated = 0 ) {
	echo eo_get_the_end( $format, $post_id, $occurrence_id, $deprecated );
}


/**
 * Gets the formated date of next occurrence of an event
 *
 * ### Examples
 * Inside the loop, you can output the start date of the next occurrence of the current event.
 * <code>
 * <?php $next = eo_get_next_occurrence( 'jS M Y' ); ?>
 * </code>
 *
 * Print the start date of the next occurrence of event with id 7
 * <code>
 * <?php echo eo_get_next_occurrence( 'jS M Y', 7 ); ?>
 * </code>
 *
 * @since 1.0.0
 * @package event-date-functions
 * @param string $format The format to use, using PHP Date format
 * @param int $post_id The event (post) ID,
 * @return string The formatted date or false if no date exists
 */
function eo_get_next_occurrence( $format = 'd-m-Y', $post_id = 0 ) {
	$next_occurrence = eo_get_next_occurrence_of( $post_id );

	if ( ! $next_occurrence ) {
		return false;
	}

	$next = $next_occurrence['start'];
	/**
	 * Filters the value returned by `eo_get_next_occurrence()`
	 *
	 * @param string|DateTime $formatted_date The DateTime object or formatted returned value (as determined by $format)
	 * @param DateTime $next The next date of this event as a DateTime object
	 * @param string $format The format the date should be returned in
	 * @param int $post_id Post ID of the event
	 */
	$formatted_date = apply_filters( 'eventorganiser_get_next_occurrence', eo_format_datetime( $next, $format ), $next, $format, $post_id );
	return $formatted_date;
}

/**
 * Returns an array of datetimes (start and end) corresponding to the next occurrence of an event
 * {@see eo_get_next_occurrence()} on the other hand returns a formated datetime of the start date.
 *
 * 'Next occurrence' means next occurrence that starts, and so will not be a currently running occurrence. To get
 * the currently running occurrence use {@see eo_get_current_occurrence_of()}
 *
 * @package event-date-functions
 * @since 1.6
 *
 * @param int $post_id The event (post) ID. Uses current event if empty.
 * @return bool|array Array with keys 'start', 'end' and 'occurrence_id', with corresponding datetime objects / occurrence ID or false if there are no future dates.
 */
function eo_get_next_occurrence_of( $post_id = 0 ) {
	global $wpdb;
	$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );

	//Retrieve the blog's local time and create the date part
	$tz = eo_get_blog_timezone();
	$blog_now = new DateTime( null, $tz );
	$now_date = $blog_now->format( 'Y-m-d' );
	$now_time = $blog_now->format( 'H:i:s' );

	$nextoccurrence  = $wpdb->get_row($wpdb->prepare("
		SELECT event_id as occurrence_id, StartDate, StartTime, EndDate, FinishTime
		FROM  {$wpdb->eo_events}
		WHERE {$wpdb->eo_events}.post_id=%d
		AND ( 
			({$wpdb->eo_events}.StartDate > %s) OR
			({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime >= %s))
		ORDER BY {$wpdb->eo_events}.StartDate ASC
		LIMIT 1", $post_id, $now_date, $now_date, $now_time ) );

	if ( ! $nextoccurrence ) {
		return false;
	}

	$start = new DateTime( $nextoccurrence->StartDate . ' ' . $nextoccurrence->StartTime, $tz );
	$end = new DateTime( $nextoccurrence->EndDate . ' ' . $nextoccurrence->FinishTime, $tz );
	$occurrence_id = (int) $nextoccurrence->occurrence_id;

	return compact( 'start', 'end', 'occurrence_id' );
}


/**
 * Prints the formated date of next occurrence of an event
 * @since 1.0.0
 * @uses eo_get_next_occurrence()
 * @package event-date-functions
 *
 * @param string $format The format to use, using PHP Date format
 * @param int $post_id The event (post) ID. Uses current event if empty.
 */
function eo_next_occurrence( $format = '', $event_id = 0 ) {
	echo eo_get_next_occurrence( $format, $event_id );
}

/**
 * Returns an array of datetimes (start and end) corresponding to the current occurrence of an event.
 * If the event has multiple overlapping occurrences currently running, returns the one with the latest start date.
 * To get the next occurrence{@see `eo_get_next_occurrence_of()`}
 *
 * @since 1.7
 * @package event-date-functions
 *
 * @param int $post_id The event (post) ID. Uses current event if empty.
 * @return array Array with keys 'start' and 'end', with corresponding datetime objects
 */
function eo_get_current_occurrence_of( $post_id = 0 ) {
	global $wpdb;

	$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );

	//Retrieve the blog's local time and create the date part
	$tz = eo_get_blog_timezone();
	$blog_now = new DateTime( null, $tz );
	$now_date = $blog_now->format( 'Y-m-d' );
	$now_time = $blog_now->format( 'H:i:s' );

	//Get the current occurrence. May be multiple (overlapping) occurrences. Pick the latest.
	$current_occurrence  = $wpdb->get_row( $wpdb->prepare("
		SELECT StartDate, StartTime, EndDate, FinishTime
		FROM  {$wpdb->eo_events}
		WHERE {$wpdb->eo_events}.post_id=%d
		AND (
			({$wpdb->eo_events}.StartDate < %s) OR
			({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime <= %s)
		)AND(
			({$wpdb->eo_events}.EndDate > %s) OR
			({$wpdb->eo_events}.EndDate = %s AND {$wpdb->eo_events}.FinishTime >= %s)
		)
		ORDER BY {$wpdb->eo_events}.StartDate DESC
		LIMIT 1", $post_id, $now_date, $now_date, $now_time, $now_date, $now_date, $now_time ) );

	if ( ! $current_occurrence ) {
		return false;
	}

	$start = new DateTime( $current_occurrence->StartDate . ' ' . $current_occurrence->StartTime, $tz );
	$end = new DateTime( $current_occurrence->EndDate . ' ' . $current_occurrence->FinishTime, $tz );

	return compact( 'start', 'end' );
}


/**
* Return true is the event is an all day event.
 * @since 1.2
*
* @param int $post_id The event (post) ID. Uses current event if empty.
* @return bool True if event runs all day, or false otherwise
 */
function eo_is_all_day($post_id=0){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if( empty($post_id) ) {
		return false;
	}
	//$schedule = get_post_meta( $post_id,'_eventorganiser_event_schedule', true );
	$schedule = eo_get_event_schedule($post_id);
	return (bool) $schedule['all_day'];
}

/**
* Returns the formated date of first occurrence of an event
* 
* ### Examples
* Inside the loop, you can output the first start date of event
* <code>
* <?php echo 'This event will first start on ' . eo_get_schedule_start( 'jS M Y' ); ?>
* </code> 
* 
* Print the first start date of the event with id 7
* <code>
* <?php echo eo_get_schedule_start( 'jS M Y', 7 ); ?>
* </code>
* 
* @since 1.0.0
* @package event-date-functions
* @param string $format the format to use, using PHP Date format
* @param int $post_id The event (post) ID. Uses current event if empty.
* @return string The formatted date
 */
function eo_get_schedule_start($format='d-m-Y',$post_id=0){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);
	$schedule = eo_get_event_schedule($post_id);
	$schedule_start = $schedule['schedule_start'];
	
	/**
	 * Filters the value returned by `eo_get_schedule_start()`
	 *
	 * @param string|DateTime $formatted_date The DateTime object or formatted returned value (as determined by $format)
	 * @param DateTime $end The start date of the first occurrence of this event as a DateTime object
	 * @param string $format The format the date should be returned in
	 * @param int $post_id Post ID of the event
	 */
	$formatted_date = apply_filters('eventorganiser_get_schedule_start', eo_format_datetime( $schedule_start, $format ), $schedule_start, $format, $post_id );
	return $formatted_date;
}

/**
* Prints the formated date of first occurrence of an event
* @since 1.0.0
* @uses eo_get_schedule_start()
* @package event-date-functions
*
* @param string $format The format to use, using PHP Date format
* @param int $post_id The event (post) ID. Uses current event if empty.
 */
function eo_schedule_start($format='d-m-Y',$post_id=0){
	echo eo_get_schedule_start($format,$post_id);
}


/**
 * Returns the formated date of the last occurrence of an event
 *
 * ### Examples
 * Inside the loop, you can output the last start date of event
 * <code>
 * <?php echo 'This event will run for the last time on ' . eo_get_schedule_last( 'jS M Y' ); ?>
 * </code>
 *
 * Print the last start date of the event with id 7
 * <code>
 * <?php echo eo_get_schedule_last( 'jS M Y', 7 ); ?>
 * </code>
 *
 * @since 1.4.0
 * @package event-date-functions
 *
 * @param string $format The format to use, using PHP Date format
 * @param int $post_id The event (post) ID. Uses current event if empty.
 * @return string The formatted date
 */
function eo_get_schedule_last( $format = 'd-m-Y', $post_id = 0 ) {
	$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );
	$schedule = eo_get_event_schedule( $post_id );
	$schedule_last = $schedule['schedule_last'];

	/**
	 * Filters the value returned by `eo_get_schedule_last()`
	 *
	 * @param string|DateTime $formatted_date The DateTime object or formatted returned value (as determined by $format)
	 * @param DateTime $end The **start** date of the last occurrence of this event as a DateTime object
	 * @param string $format The format the date should be returned in
	 * @param int $post_id Post ID of the event
	 */
	$formatted_date = apply_filters( 'eventorganiser_get_schedule_last', eo_format_datetime( $schedule_last, $format ), $schedule_last, $format, $post_id );
	return $formatted_date;
}

/**
* Prints the formated date of the last occurrence of an event
 * @since 1.4.0
* @uses eo_get_schedule_last()
* @package event-date-functions
*
* @param string $format The format to use, using PHP Date format
* @param int $post_id The event (post) ID. Uses current event if empty.
* @return string The formatted date 
 */
function eo_schedule_last($format='d-m-Y',$post_id=0){
	echo eo_get_schedule_last($format,$post_id);
}


/**
* Returns true if event recurs or false if it is a one time event.
* 
* ### Examples
* Display a different message depending on whether the event recurs or not, inside the loop.
* <code>
*      <?php if( eo_recurs() ){ 
*                  echo 'This event recurs'; 
*            }else{ 
*                  echo 'This event is a one-time event'; 
*            } 
*       ?>
* </code>
* Outside the loop, for event with ID 7:
* <code>
*      <?php if( eo_recurs(7) ){ 
*                  echo 'This event recurs'; 
*            }else{ 
*                  echo 'This event is a one-time event'; 
*            } 
*       ?>
* </code>
* @since 1.0.0
* @param int $post_id The event (post) ID. Uses current event if empty.
* @return bool true if event a recurring event
*/
function eo_recurs($post_id=0){
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if( empty($post_id) ) 
		return false;

	$schedule = eo_get_event_schedule($post_id);
	
	return ($schedule['schedule'] != 'once');
}

/**
* Returns a summary of the events schedule.
 * @since 1.0.0
 * @ignore
*
* @param int $post_id The event (post) ID. Uses current event if empty.
* @return string A summary of the events schedule.
 */
function eo_get_schedule_summary($post_id=0){
	global $post,$wp_locale;

	$ical2day = array('SU'=>	$wp_locale->weekday[0],'MO'=>$wp_locale->weekday[1],'TU'=>$wp_locale->weekday[2], 'WE'=>$wp_locale->weekday[3], 
						'TH'=>$wp_locale->weekday[4],'FR'=>$wp_locale->weekday[5],'SA'=>$wp_locale->weekday[6]);

	$nth= array(__('last','eventorganiser'),'',__('first','eventorganiser'),__('second','eventorganiser'),__('third','eventorganiser'),__('fourth','eventorganiser'));

	$recur = eo_get_event_schedule($post_id);

	if(empty($recur))
		return false;

	$return='';

	if($recur['schedule']=='once'){
		$return = __('one time only','eventorganiser');

	}elseif($recur['schedule']=='custom'){
		$return = __('custom recurrence','eventorganiser');

	}else{
		switch($recur['schedule']):

			case 'daily':
				if($recur['frequency']==1):
					$return .=__('every day','eventorganiser');
				else:
					$return .=sprintf(__('every %d days','eventorganiser'),$recur['frequency']);
				endif;
				break;

			case 'weekly':
				if($recur['frequency']==1):
					$return .=__('every week on','eventorganiser');
				else:
					$return .=sprintf(__('every %d weeks on','eventorganiser'),$recur['frequency']);
				endif;

				foreach( $recur['schedule_meta'] as $ical_day){
					$days[] =  $ical2day[$ical_day];
					}
				$return .=' '.implode(', ',$days);
				break;

			case 'monthly':
				if($recur['frequency']==1):
					$return .=__('every month on the','eventorganiser');
				else:
					$return .=sprintf(__('every %d months on the','eventorganiser'),$recur['frequency']);
				endif;
				$return .= ' ';
				$bymonthday =preg_match('/^BYMONTHDAY=(\d{1,2})/' ,$recur['schedule_meta'],$matches);

				if( $bymonthday  ){
					$d = intval($matches[1]);
					$m =intval($recur['schedule_start']->format('n'));
					$y =intval($recur['schedule_start']->format('Y'));
					$recur['start']->setDate($y,$m,$d);
					$return .= $recur['schedule_start']->format('jS');

				}elseif($recur['schedule_meta']=='date'){
					$return .= $recur['schedule_start']->format('jS');

				}else{
					$byday = preg_match('/^BYDAY=(-?\d{1,2})([a-zA-Z]{2})/' ,$recur['schedule_meta'],$matches);
					if($byday):
						$n=intval($matches[1])+1;
						$return .=$nth[$n].' '.$ical2day[$matches[2]];
					else:
						$bydayOLD = preg_match('/^(-?\d{1,2})([a-zA-Z]{2})/' ,$recur['schedule_meta'],$matchesOLD);
						$n=intval($matchesOLD[1])+1;
						$return .=$nth[$n].' '.$ical2day[$matchesOLD[2]];
					endif;
				}
				break;
			case 'yearly':
				if($recur['frequency']==1):
					$return .=__('every year','eventorganiser');
				else:
					$return .=sprintf(__('every %d years','eventorganiser'),$recur['frequency']);
				endif;
				break;

		endswitch;
		$return .= ' '.__('until','eventorganiser').' '. eo_format_datetime($recur['schedule_last'],'M, jS Y');
	}
	
	return $return; 
}

/** 
* Returns an array of occurrences. Each occurrence is an array with 'start' and 'end' key. 
*  Both of these hold a DateTime object (for the start and end of that occurrence respecitvely).
* @since 1.5
* @package event-date-functions
* @access private
* @ignore
*
* @param int $post_id The event (post) ID. Uses current event if empty.
* @return array Array of arrays of DateTime objects of the start and end date-times of occurrences. False if none exist.
 */
function eo_get_the_future_occurrences_of( $post_id=0 ){
	global $wpdb;

	$today = new DateTime('now',eo_get_blog_timezone());
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id)) 
		return false;

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT event_id, StartDate,StartTime,EndDate,FinishTime FROM {$wpdb->eo_events} 
			WHERE {$wpdb->eo_events}.post_id=%d AND ( StartDate > %s OR ( StartDate = %s AND StartTime >= %s) ) ORDER BY StartDate ASC",
			$post_id,
			$today->format('Y-m-d'),
			$today->format('Y-m-d'),
			$today->format('H:i:s')				
		)
	);
	
	if( !$results )
		return false;

	$occurrences=array();
	foreach($results as $row):
		$occurrences[$row->event_id] = array(
			'start' => new DateTime($row->StartDate.' '.$row->StartTime, eo_get_blog_timezone()),
			'end' => new DateTime($row->EndDate.' '.$row->FinishTime, eo_get_blog_timezone())
		);
	endforeach;
	
	/**
	 * Filters the value returned by `eo_get_the_future_occurrences_of()`
	 *
	 * The filtered value is an array of occurrences. Each occurrence is an array with 'start' and 'end' key. 
	 * Both of these hold a DateTime object (for the start and end of that occurrence respecitvely).
	 *
	 * @param array $occurrences Future occurrences of this event
	 * @param int $post_id Post ID of the event
	 */
	$occurrences = apply_filters( 'eventorganiser_get_the_future_occurrences_of', $occurrences, $post_id );
	return $occurrences;
}

/** 
* Returns an array of occurrences. Each occurrence is an array with 'start' and 'end' key. 
*  Both of these hold a DateTime object (for the start and end of that occurrence respecitvely).
*  
* ### Example
* List the start and end dates of a particular event.
* <code>
*     $occurrences = eo_get_the_occurrences_of( $post_id );
*     echo '<ul>';
*     foreach( $occurrences as $occurrence) {
*          $start = eo_format_datetime( $occurrence['start'] , 'jS F ga' );
*          $end = eo_format_datetime( $occurrence['end'] , 'jS F ga' );
*          printf( '<li> This event starts on the %s and ends on the %s </li>', $start, $end );
*          echo eo_format_datetime($include_date['start'],'c');
*     }
*     echo '</ul>';
* </code>   
*   
* @since 1.5
* @package event-date-functions
*
* @param int $post_id The event (post) ID. Uses current event if empty.
* @return array Array of arrays of DateTime objects of the start and end date-times of occurrences. False if none exist.
 */
function eo_get_the_occurrences_of($post_id=0){
	global $wpdb;

	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id)) 
		return false;

	 //Can't cache datetime objects before 5.3
	 //@see{https://wordpress.org/support/topic/warning-datetimeformat-functiondatetime-format?replies=7#post-3940247}
	if( version_compare(PHP_VERSION, '5.3.0') >= 0 ){
		$occurrences = wp_cache_get( 'eventorganiser_all_occurrences_'.$post_id );
	}else{
		$occurrences = false;
	}

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
		wp_cache_set( 'eventorganiser_all_occurrences_'.$post_id, $occurrences );
	}

	/**
	 * Filters the value returned by `eo_get_the_occurrences_of()`
	 *
	 * The filtered value is an array of occurrences. Each occurrence is an array with 'start' and 'end' key.
	 * Both of these hold a DateTime object (for the start and end of that occurrence respecitvely).
	 *
	 * @param array $occurrences All occurrences of this event
	 * @param int $post_id Post ID of the event
	 */
	$occurrences = apply_filters( 'eventorganiser_get_the_occurrences_of', $occurrences, $post_id );
	return $occurrences;
}

/**
 * Returns the colour of a category
 * @param int|object $term_id The term ID or term object to retrieve the colour of
 */
function eo_get_category_color( $term_id ) {
	$term = is_int( $term_id ) ? get_term_by( 'id', $term_id, 'event-category' ) : $term_id;
	return eo_get_category_meta( $term, 'color' );
}

/**
* Returns the colour of a category associated with the event.
* Applies the {@see `eventorganiser_event_color`} filter.
* @since 1.6
*
* @param int $post_id The event (post) ID. Uses current event if empty.
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

	/**
	 * Filters the colour associated with an event
	 * 
	 * @link https://wordpress.org/support/topic/plugin-event-organiser-color-code-for-venues-instead-of-categories
	 * @param string $color Event colour in HEX format
	 * @param int $post_id The event (post) ID
	*/
	$color = apply_filters('eventorganiser_event_color',$color,$post_id);
	
	return $color;
}

/**
* Accepts a color in RGB hex format and returns a string containing "#000000"
* (black) or "#ffffff" (white) depending on which would be more readable when
* overlaid on top of the supplied color.
*
* @param string $color A color in RGB hex format with or without a leading hash
* @return string A string containing either "#000000" or "#ffffff"
 */
function eo_get_event_textcolor( $color ) {
	// Remove the leading hash if present
	$color = ltrim( $color, '#' );

	// Calculate the luma (Y) of the color using the formula provided at
	// https://en.wikipedia.org/wiki/YIQ#Formulas. Full luma is 255000 when
	// the RGB color is white (#ffffff). Luma above the mid point, 127500,
	// should return "#000000", otherwise "#ffffff".
	$r = hexdec( substr( $color, 0, 2 ) ); 
	$g = hexdec( substr( $color, 2, 2 ) ); 
	$b = hexdec( substr( $color, 4, 2 ) ); 
	$y = ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 );
	return ( $y > 127500 ) ? '#000000' : '#ffffff';
}

/**
* Returns an array of classes associated with an event. Adds the following classes
* 
*  * `eo-event-venue-[venue slug]` - if the event has a venue
*  * `eo-event-cat-[category slug]` - for each event category the event belongs to. 
*  * `eo-event-[future|past|running]` - depending on occurrence
* 
* Applies filter {@see `eventorganiser_event_classes`} so you can add/remove classes.
* 
* @since 1.6
* @param int $post_id The event (post) ID. Uses current event if empty.
* @param int $occurrence_id The occurrence ID. Uses current event if empty.
* @return array Array of classes
 */
function eo_get_event_classes($post_id=0, $occurrence_id=0){
	global $post;

	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id );
	$occurrence_id = (int) ( empty($occurrence_id) && isset($post->occurrence_id)  ? $post->occurrence_id : $occurrence_id );
	
	$event_classes = array();
			
	//Add venue class
	if( $venue_slug = eo_get_venue_slug( $post_id ) )
		$event_classes[] = 'eo-event-venue-' . $venue_slug;

	//Add category classes
	$cats= get_the_terms( $post_id, 'event-category' );
	if( $cats && !is_wp_error($cats) ){	
		foreach ($cats as $cat)
			$event_classes[] = 'eo-event-cat-'.$cat->slug;
	}
	
	//Event tags
	if( eventorganiser_get_option('eventtag') ){
		$terms = get_the_terms( $post_id, 'event-tag' );
		if( $terms && !is_wp_error( $terms ) ){
			foreach ( $terms as $term ){
				$event_classes[] = 'eo-event-tag-'.$term->slug;
			}
		}
	}

	//Add 'time' class
	$start = eo_get_the_start( DATETIMEOBJ, $post_id, $occurrence_id );
	$end = eo_get_the_end( DATETIMEOBJ, $post_id, $occurrence_id );
	$now = new DateTime( 'now', eo_get_blog_timezone() );
	if( $start > $now ){
		$event_classes[] = 'eo-event-future';
	}elseif( $end < $now ){
		$event_classes[] = 'eo-event-past';
	}else{
		$event_classes[] = 'eo-event-running';
	}
	
	//Add class if event starts and ends on different days
	if( ( $start instanceof DateTime ) && ( $end instanceof DateTime ) ){
		if( $start->format('Y-m-d') != $end->format('Y-m-d') ){
			$event_classes[] = 'eo-multi-day';
		} 
	}
	
	if( eo_is_all_day( $post_id ) ){
		$event_classes[] = 'eo-all-day';
	}
	
	/**
	 * Filters an array of classes for specified event (occurrence) 
	 *
	 * @param array $event_classes An array of class pertaining to this occurrence
	 * @param int $post_id The ID of the event
	 * @param into $occurrence_id The ID of the occurrence
	 */
	$event_classes = apply_filters( 'eventorganiser_event_classes', $event_classes, $post_id, $occurrence_id );
	$event_classes = array_unique( $event_classes );
	$event_classes = array_map( 'sanitize_html_class', $event_classes );
	$event_classes = array_filter( $event_classes );
	return $event_classes;
}


/**
* Checks if the query is for an event taxonomy.
*  
* When no $query is passed, acts as a simple wrapper for `is_tax()`.
* More generally acts as a wrapper for `$query->is_tax()`.
* 
* @since 1.6
*
* @param $query - The query to check. If not passed, uses the global $wp_query;
* @return bool True if query is for any event taxonomy (e.g. 'event-venue', 'event-category', 'event-tag').
 */
function eo_is_event_taxonomy( $query = false ){
	$event_tax = get_object_taxonomies( 'event' );
	
	//Handle post tags
	if( in_array( 'post_tag', $event_tax ) ){
		if( ( !$query && is_tag() ) || ( $query && $query->is_tag() ) )
			return true;
	}
	
	//Handle categories
	if( in_array( 'category', $event_tax ) ){
		if( ( !$query && is_category() ) || ( $query && $query->is_category() ) )
			return true;
	}
	
	if( !$query ){
		return is_tax( $event_tax );
	}else{
		return $query->is_tax( $event_tax );
	}
}

/**
* Retrieves the permalink for the ICAL event feed. A simple wrapper for `get_feed_link()`.
*
* Retrieve the permalink for the events feed. The returned link is the url with which visitors can subscribe 
* to your events. Visiting the url directly will prompt a download an ICAL file of your events. The events feed 
* includes only **public** events (draft, private and trashed events are not included).
*
* @since 1.6
*
* @return string The link to the ICAL event feed..
 */
function eo_get_events_feed(){
	return get_feed_link('eo-events');
}

/**
 * Retrieves the permalink for the ICAL event feed for a category. A simple wrapper for `{@see get_term_feed_link()}`
 * 
 * If you pass an integer this is assumed to be the term ID of the category. If you pass a string it
 * assumed to be the slug.
 * 
 * @since 2.2
 * @param string|int $cat_slug_or_id Category ID as an **integer**, or slug as a **string** 
 * @return string The link to the ICAL event category feed.
 */
function eo_get_event_category_feed( $cat_slug_or_id ){
	
	if( is_int( $cat_slug_or_id ) )
		return get_term_feed_link( $cat_slug_or_id, 'event-category', 'eo-events' );
	
	$category = get_term_by( 'slug', $cat_slug_or_id, 'event-category' );
	
	if( !$category )
		return false;
	
	return get_term_feed_link( $category->term_id, 'event-category', 'eo-events' );
}

/**
 * Retrieves the permalink for the ICAL event feed for a venue. A simple wrapper for `{@see get_term_feed_link()}`.
 *
 * If you pass an integer this is assumed to be the term ID of the category. If you pass a string it
 * assumed to be the slug.
 *
 * @since 2.2
 * @param string|int $venue_slug_or_id Category ID as an **integer**, or slug as a **string**
 * @return string The link to the ICAL event category feed.
 */
function eo_get_event_venue_feed( $venue_slug_or_id ){

	$venue_id = eo_get_venue_id_by_slugorid( $venue_slug_or_id );
	
	if( !$venue_id )
		return false;

	return get_term_feed_link( $venue_id, 'event-venue', 'eo-events' );
}

/**
 * Returns a the url which adds a particular occurrence of an event to
 * a google calendar.
 *
 * Returns an url which adds a particular occurrence of an event to a Google calendar. This function can only be used inside the loop. 
 * An entire series cannot be added to a Google calendar - however users can subscribe to your events. Please note that, unlike 
 * subscribing to events, changes made to an event will not be reflected on an event added to the Google calendar.
 *
 * ### Examples
 * Add a 'add this event to Google' link:
 * <code>
 *    <?php 
 *      //Inside the loop 
 *      $url = eo_get_add_to_google_link();
 *      echo '<a href="'.esc_url($url).'"> Add to Google </a>'; 
 *      ?>
 * </code>
 * @since 2.3
 * @param int $post_id Post ID of the event.
 * @param int $occurrence_id The occurrence ID.
 * @return string Url which adds event to a google calendar
 */
function eo_get_add_to_google_link( $event_id = 0, $occurrence_id = 0 ){
	
	global $post;
	$event = $post;
	
	$event_id = (int) ( $event_id ? $event_id : get_the_ID() );
	$occurrence_id = (int) ( !$occurrence_id && isset( $event->occurrence_id )  ? $event->occurrence_id : $occurrence_id );

	$post = get_post( $event_id );
	
	if( !$occurrence_id || !$post || 'event' != get_post_type( $post ) ){
		wp_reset_postdata();
		return false;
	}
	
	setup_postdata( $post );	
	
	$start = clone eo_get_the_start( DATETIMEOBJ, $event_id, $occurrence_id );
	$end   = clone eo_get_the_end( DATETIMEOBJ, $event_id, $occurrence_id );
	
	if( eo_is_all_day() ):
		$end->modify( '+1 second' );
		$format = 'Ymd';
	else:
		$format = 'Ymd\THis\Z';
		$start->setTimezone( new DateTimeZone( 'UTC' ) );
		$end->setTimezone( new DateTimeZone( 'UTC' ) );
	endif;
	
	/**
	 * @ignore
	 */
	$excerpt = apply_filters( 'the_excerpt_rss', get_the_excerpt() );

	$venue    = false;
	$venue_id = eo_get_venue();
	if ( $venue_id ) {
		$venue = eo_get_venue_name( $venue_id ) . ", " . implode( ', ', eo_get_venue_address( $venue_id ) );
	}

	$url = add_query_arg( array(
			'text'     => get_the_title(),
			'dates'    => $start->format( $format ) . '/' . $end->format( $format ),
			'details'  => esc_html( $excerpt ),
			'sprop'    => get_bloginfo( 'name' ),
			'location' => $venue,
	), 'http://www.google.com/calendar/event?action=TEMPLATE' );

	wp_reset_postdata();
	return esc_url_raw( $url );
}


/**
 * @ignore
*/
function eo_has_event_started( $event_id = false, $occurrence_id = false ) {
	$start = eo_get_the_start( DATETIMEOBJ, $event_id, $occurrence_id );
	$now   = new DateTime( 'now', eo_get_blog_timezone() );
	return ($start <= $now );
}

/**
 * @ignore
*/
function eo_has_event_finished( $event_id = false, $occurrence_id = false ) {
	$end = eo_get_the_end( DATETIMEOBJ, $event_id, $occurrence_id );
	$now = new DateTime( 'now', eo_get_blog_timezone() );
	return ($end <= $now );
}


/**
 * Returns HTML mark-up for the fullCalendar
 *
 * It also (indirectly) triggers the enquing of the necessary scripts and styles. The `$args` array
 * accepts exactly the same arguments as the shortcode, they are
 *
 * * **headerleft** (string) What appears on the left of the calendar header. Default 'title'.
 * * **headercenter** (string) What appears on the left of the calendar header. Default ''.
 * * **headerright** (string) What appears on the left of the calendar header. Default 'prev next today'.
 * * **defaultview** (string) The view the calendar loads on. Default 'month',
 * * **event-category** (string|array) Restrict calendar to specified category(ues) (by slug). Default all categories.
 * * **event-venue** (string|array) Restrict calendar to specified venue(s) (by slug). Default all venues.
 * * **timeformat** (string) Time format for calendar. Default 'G:i'.
 * * **axisformat** (string) Axis time format (for day/week views). WP's time format option.
 * * **key** (bool) Whether to show a category key. Default false.
 * * **tooltip** (bool) Whether to show a tooltips. Default true. Content is filtered by [`eventorganiser_event_tooltip`](http://codex.wp-event-organiser.com/hook-eventorganiser_event_tooltip.html)
 * * **users_events** - (bool) True to show only eents for which the current user is attending
 * * **weekends** (bool) Whether to include weekends in the calendar. Default true.
 * * **mintime** (string) Earliest time to show on week/day views. Default '00:00',
 * * **maxtime** (string) Latest time to show on week/day views. Default '24:00',
 * * **slotDuration** (string) The frequency for displaying time slots in agenda views, default '00:30:00' (30 minutes)
 * * **nextDayThreshold** (string) If the event starts on a previous day, then the end time of the event must be after this time for it appear on this day on the calendar. Default '06:00:00' (6am)
 *   e.g. An event 17th October 11pm - 18th October 7am will appear on the 17th and 18th, but an event 17th October 11pm - 18th October 5am will appear only on the 17th.
 * * **alldayslot** (bool) Whether to include an all day slot (week / day views) in the calendar. Default true.
 * * **alldaytext** (string) Text to display in all day slot. Default 'All Day'.
 * * **titleformatmonth** (string) Date format (PHP) for title for month view. Default 'l, M j, Y'
 * * **titleformatweek** (string) Date format (PHP) for title for week view. Default 'M j Y'.
 * * **titleformatday** (string) Date format (PHP) for title for day view. Default 'F Y'
 * * **columnformatmonth** (string) Dateformat for month columns. Default 'D'.
 * * **columnformatweek** (string) Dateformat for month columns. Default 'D n/j'.
 * * **columnformatday** (string) Dateformat for month columns. Default 'l n/j',
 * * **year** The year the calendar should start on (e.g. 2013)
 * * **month** The month the calendar should start on (1=Jan, 12=Dec)
 * * **date** The calendar the date should start on
 *
 * @link http://arshaw.com/fullcalendar/ The fullCalendar (jQuery plug-in)
 * @link https://github.com/stephenharris/fullcalendar Event Organiser version of fullCalendar
 * @since 1.7
 * @param array $args An array of attributes for the calendar
 * @return string HTML mark-up.
*/
function eo_get_event_fullcalendar( $args = array() ) {

	global $wp_locale;
	$defaults = array(
		'headerleft' => 'title', 'headercenter' => '', 'headerright' => 'prev next today',
		'defaultview' => 'month', 'aspectratio' => false, 'compact' => false,
		'event-category' => '', 'event_category' => '', 'event-venue' => '', 'event_venue' => '', 'event-tag' => '',
		'author' => false, 'author_name' => false,
		'timeformat' => get_option( 'time_format' ), 'axisformat' => get_option( 'time_format' ),
		'key' => false, 'tooltip' => true,
		'weekends' => true, 'mintime' => '0', 'maxtime' => '24', 'showdays' => array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' ),
		'slotduration' => '00:30:00',
		'nextdaythreshold' => '06:00:00',
		'alldayslot' => true, 'alldaytext' => __( 'All day', 'eventorganiser' ),
		'columnformatmonth' => 'D', 'columnformatweek' => 'D n/j', 'columnformatday' => 'l n/j',
		'titleformatmonth' => 'F Y', 'titleformatweek' => 'M j, Y', 'titleformatday' => 'l, M j, Y',
		'year' => false, 'month' => false, 'date' => false, 'defaultdate' => false,	'users_events' => false,
		'event_series' => false, 'event_occurrence__in' => array(),
		'theme' => false, 'reset' => true, 'isrtl' => $wp_locale->is_rtl(),
		'responsive' => true, 'responsivebreakpoint' => 514,
	);

	//year/month/day
	if ( isset( $args['year'] ) ) {
		$args['month'] = isset( $args['month'] ) ? $args['month'] : '01';
		$args['date'] = isset( $args['date'] ) ? $args['date'] : '01';
	}
	if ( isset( $args['month'] ) ) {
		$args['year']  = isset( $args['year'] ) ? $args['year'] : date( 'Y' );
		$args['date']  = isset( $args['date'] ) ? $args['date'] : '01';
		$args['month'] = str_pad( $args['month'], 2, '0', STR_PAD_LEFT );
	}
	if ( isset( $args['date'] ) ) {
		$args['year']  = isset( $args['year'] ) ? $args['year'] : date( 'Y' );
		$args['month'] = isset( $args['month'] ) ? $args['month'] : date( 'M' );
		$args['date']  = str_pad( $args['date'], 2, '0', STR_PAD_LEFT );
	}

	if ( isset( $args['year'] ) ) {
		$args['defaultdate'] = $args['year'] . '-' . $args['month'] . '-' . $args['date'];
	}

	$args = shortcode_atts( $defaults, $args, 'eo_fullcalendar' );

	//Days to show
	$args['showdays'] = array_map( 'strtoupper', $args['showdays'] );
	$args['hiddendays'] = array_diff( array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' ), $args['showdays'] );
	$args['hiddendays'] = array_keys( $args['hiddendays'] );
	unset( $args['showdays'] );

	$key = $args['key'];
	unset( $args['key'] );

	//Support 'event-category' and 'event-venue'. Backwards compat with 'event_category'/'event_venue'
	$args['event-category'] = empty( $args['event_category'] ) ? $args['event-category'] : $args['event_category'];
	$args['event-venue'] = empty( $args['event_venue'] ) ? $args['event-venue'] : $args['event_venue'];

	//Convert event_category / event_venue to comma-delimitered strings
	$args['event_category'] = is_array( $args['event-category'] ) ? implode( ',', $args['event-category'] ) : $args['event-category'];
	$args['event_venue']    = is_array( $args['event-venue'] )    ? implode( ',', $args['event-venue'] )    : $args['event-venue'];
	$args['event_tag']      = is_array( $args['event-tag'] )      ? implode( ',', $args['event-tag'] )      : $args['event-tag'];

	//Get author ID from author/author_name
	$args['event_organiser'] = ( $args['author'] ? (int) $args['author'] : eo_get_user_id_by( 'slug', $args['author_name'] ) );

	//max/min time MUST be hh:mm format
	$times = array( 'mintime', 'maxtime' );
	foreach ( $times as $arg ) {
		$args[$arg] = explode( ':', $args[$arg] );
		if ( count( $args[$arg] ) < 2 ) {
			$args[$arg][] = '00';
		}
		$args[$arg] = implode( ':', $args[$arg] );
	}

	//Convert php time format into moment time format
	$date_attributes = array(
		'timeformat',
		'axisformat',
		'columnformatday',
		'columnformatweek',
		'columnformatmonth',
		'titleformatmonth',
		'titleformatday',
		'titleformatweek',
	);
	$args['timeformatphp'] = $args['timeformat'];
	foreach ( $date_attributes as $date_attribute ) {
		$args[$date_attribute] = str_replace( '((', '[', $args[$date_attribute] );
		$args[$date_attribute] = str_replace( '))', ']', $args[$date_attribute] );
		$args[$date_attribute.'php'] = $args[$date_attribute];
		$args[$date_attribute] = eo_php_to_moment( $args[$date_attribute] );
	}

	EventOrganiser_Shortcodes::$calendars[] = array_merge( $args );

	EventOrganiser_Shortcodes::$add_script = true;
	$id = count( EventOrganiser_Shortcodes::$calendars );

	$classes = array( 'eo-fullcalendar', 'eo-fullcalendar-shortcode' );

	if ( $args['reset'] ) {
		$classes[] = 'eo-fullcalendar-reset';
	}

	if ( $args['responsive'] ) {
		$classes[] = 'eo-fullcalendar-responsive';
	}

	if ( $args['compact'] ) {
		$classes[] = 'fc-oneline';
	}

	$html = sprintf( '<div id="eo_fullcalendar_%s_loading" class="eo-fullcalendar-loading" >', $id );
	$html .= sprintf(
		'<img src="%1$s" class="eo-fullcalendar-loading-icon" alt="%2$s" /> %2$s',
		esc_url( EVENT_ORGANISER_URL . 'css/images/loading-image.gif' ),
		esc_html__( 'Loading&#8230;', 'eventorganiser' )
	);
	$html .= '</div>';

	$html .= sprintf(
		'<div class="%s" id="eo_fullcalendar_%s"></div>',
		implode( ' ', $classes ),
		$id
	);

	if ( 'top' == strtolower( $key ) ) {
		$args = array( 'orderby' => 'name', 'show_count' => 0, 'hide_empty' => 0 );
		$html = eventorganiser_category_key( $args, $id ) . $html;
	} elseif ( $key ) {
		$html .= eventorganiser_category_key( $args, $id );
	}

	return $html;
}


/**
 * Returns HTML mark-up for a list of event meta information.
 *
 * Uses microformat.
 * @since 1.7
 * @ignore
 * @param int $post_id The event (post) ID. Uses current event if not supplied
 * @return string|bool HTML mark-up. False if an invalid $post_is provided.
*/
function eo_get_event_meta_list( $event_id = 0 ) {

	$event_id = (int) ( empty( $event_id ) ? get_the_ID() : $event_id);

	if ( empty( $event_id ) ) { 
		return false;
	}

	$html  = '<ul class="eo-event-meta">';
	$venue = get_taxonomy( 'event-venue' );

	if ( ( $venue_id = eo_get_venue( $event_id ) ) && $venue ) {
		$html .= sprintf(
			'<li><strong>%s:</strong> <a href="%s">
				<span itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">
					<span itemprop="name">%s</span>
					<span itemprop="geo" itemscope itemtype="http://data-vocabulary.org/Geo">
						<meta itemprop="latitude" content="%f" />
						<meta itemprop="longitude" content="%f" />
					</span>
				</span>
			</a></li>',
			$venue->labels->singular_name,
			eo_get_venue_link( $venue_id ),
			eo_get_venue_name( $venue_id ),
			eo_get_venue_lat( $venue_id ),
			eo_get_venue_lng( $venue_id )
		);
	}

	if ( get_the_terms( $event_id, 'event-category' ) ) {
		$html .= sprintf(
			'<li><strong>%s:</strong> %s</li>' . "\n",
			__( 'Categories', 'eventorganiser' ),
			get_the_term_list( $event_id, 'event-category', '', ', ', '' )
		);
	}

	if ( get_the_terms( $event_id, 'event-tag' ) && !is_wp_error( get_the_terms( $event_id, 'event-tag' ) ) ) {
		$html .= sprintf(
			'<li><strong>%s:</strong> %s</li>' . "\n",
			__( 'Tags', 'eventorganiser' ),
			get_the_term_list( $event_id, 'event-tag', '', ', ', '' )
		);
	}

	$html .= '</ul>';

	/**
	 * Filters mark-up for the event details list.
	 *
	 * The event details list is just a simple list containig details pertaining
	 * to the event (venue, categories, tags) etc.
	 *
	 * @param array $html The generated mark-up
	 * @param int $event_id Post ID of the event
	 */
	$html = apply_filters( 'eventorganiser_event_meta_list', $html, $event_id );
	return $html;
}

/**
 * Retrieves the occurrence ID of the 'current' (global) event ($post)
 * @since 2.13
 * @return int The current event's occurrence ID.
 */
function eo_get_the_occurrence_id(){
	global $post;
	return (int) $post->occurrence_id;
}

/**
 * A helper function which can replace get_permalinks() to be occurrence-aware
 * @since 2.13
 * @param int $event_id      The event ID. Uses current event if not provided.
 * @param int $occurrence_id The occurrence ID. Uses current event if not provided.
 * @return string The event permalink
 */
function eo_get_permalink( $event_id = false, $occurrence_id = false ){
	
	$event_id      = $event_id ? $event_id : get_the_ID();
	$occurrence_id = $occurrence_id ? $occurrence_id : eo_get_the_occurrence_id();
	
	$permalink = get_permalink( $event_id );
	
	$permalink = apply_filters( 'eventorganiser_get_permalink', $permalink, $event_id, $occurrence_id );
	
	return $permalink; 
	
}


/**
 * Returns an the link for the event archive.
 *
 * Optionally provide the year , or month or day to get an year/month/day archive link.
 * To get a month archive you should provide a year. To get a day archive you should
 * provide a year and month.
 *
 * You can also just pass one argument: a DateTime object, which used to get the archive
 * link for that specific date.
 *
 * ### Example
 * On a year archive page, show  a link to the following year.
 * <code>
 * if( eo_is_event_archive( 'year' ) ){
 *      $current_year = (int) eo_get_event_archive_date('Y');
 *      $next_year = $current_year + 1;
 *      print_r( 'You are viewing %s. <a href="%s"> Click here to view events in %s </a>',
 *           $current_year,
 *           eo_get_event_archive_link( $next_year ),
 *           $next_year
 *      );
 * }
 * </code>
 *@since 1.7.0
 *@package template-functions
 *@param int|DateTime $year Year in full format, e.g. 2018. Or a a DateTime object used to determine the year, month and day. Must be provide for date-based archive link.
 *@param int $month Numeric representation of month. 1 = Jan, 12 = Dec. Must be provide for month or day archive link
 *@param int $day Day of the month 1-31
 *@return string Link to the requested archive
 *
*/
function eo_get_event_archive_link( $year = false, $month = false, $day = false ) {
	global $wp_rewrite;

	if ( $year instanceof DateTime ) {
		$day = (int) $year->format( 'd' );
		$month = (int) $year->format( 'm' );
		$year = (int) $year->format( 'Y' );
	}

	$archive = get_post_type_archive_link( 'event' );

	if ( false == $year && false == $month && false == $day ) {
		return $archive;
	}

	$_year  = str_pad( $year, 4, '0', STR_PAD_LEFT );
	$_month = str_pad( $month, 2, '0', STR_PAD_LEFT );
	$_day   = str_pad( $day, 2, '0', STR_PAD_LEFT );

	if ( $day ) {
		$date = compact( '_year', '_month', '_day' );
	} elseif ( $month ) {
		$date = compact( '_year', '_month' );
	} else {
		$date = compact( '_year' );
	}

	if ( $archive && $wp_rewrite->using_mod_rewrite_permalinks() && $permastruct = $wp_rewrite->get_extra_permastruct( 'event_archive' ) ) {
		$archive = home_url( str_replace( '%event_ondate%', implode( '/', $date ), $permastruct ) );
	} else {
		$archive = add_query_arg( 'ondate', implode( '-', $date ), $archive );
	}

	return $archive;
}

/**
 * Break a specified occurrence from an event
 * 
 * @param int $post_id The event (post) ID
 * @param int $occurrence_id The occurrence ID
 * @return int|WP_Error The new event (post) ID or a WP_Error on error
 */
function eo_break_occurrence( $post_id, $occurrence_id ){

	global $post;
	$post = get_post( $post_id );
	setup_postdata( $post );

	/**
	 * Triggered before an occurrence is broken from an event.
	 *
	 * @param int $post_id The ID of the original parent event
	 * @param int $occurrence_id The ID of the occurrence being broken
	 */
	do_action( 'eventorganiser_pre_break_occurrence', $post_id, $occurrence_id );
	
	$tax_input = array();
	foreach ( array( 'event-category', 'event-tag', 'event-venue' ) as $tax ):
		$terms = get_the_terms( $post->ID, $tax );
		if ( $terms && !is_wp_error( $terms ) ){
			$tax_input[$tax] = array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) );
		}
	endforeach;

	//Post details
	$post_array = array(
		'post_title' => $post->post_title, 'post_name' => $post->post_name, 'post_author' => $post->post_author,
		'post_content' => $post->post_content, 'post_status' => $post->post_status, 'post_date' => $post->post_date,
		'post_date_gmt' => $post->post_date_gmt, 'post_excerpt' => $post->post_excerpt, 'post_password' => $post->post_password,
		'post_type' => 'event', 'tax_input' => $tax_input, 'comment_status' => $post->comment_status, 'ping_status' => $post->ping_status,
	);  

	//Event details
	$event_array = array(
		'start'     => eo_get_the_start( DATETIMEOBJ, $post_id, $occurrence_id ),
		'end'       => eo_get_the_end( DATETIMEOBJ, $post_id, $occurrence_id ),
		'all_day'   => ( eo_is_all_day( $post_id )  ? 1 : 0 ),
		'schedule'  => 'once',
		'frequency' => 1,
	);

	//Create new event with duplicated details (new event clears cache)
	$new_event_id = eo_insert_event( $post_array, $event_array );

	//delete occurrence, and copy post meta
	if ( $new_event_id && !is_wp_error( $new_event_id ) ){
		$response = _eventorganiser_remove_occurrence( $post_id, $occurrence_id );

		$post_custom = get_post_custom( $post_id );
		foreach ( $post_custom as $meta_key => $meta_values ) {

			//Don't copy these
			$ignore_meta = array( '_eventorganiser_uid', '_eo_tickets', '_edit_last', '_edit_last', '_edit_lock' ) ;

			/**
			 * Filters an array of keys which should be ignored when breaking an 
			 * occurrence.
			 * 
			 * When breaking an occurrence from an event a new event is made for 
			 * that occurrence. Meta data from the original event is copied across, 
			 * unless its meta key exists in the filtered array.  
			 * 
			 * @param array $ignore_meta Array of meta keys to be ignored
			 */
			$ignore_meta = apply_filters( 'eventorganiser_breaking_occurrence_exclude_meta', $ignore_meta );
			
			if( in_array( $meta_key, $ignore_meta ) )
				continue;
		
			//Don't copy event meta
			if( 0 == strncmp( $meta_key,  '_eventorganiser', 15 ) )
				continue;

			foreach ( $meta_values as $meta_value ) {
				//get_post_meta() without a key doesn't unserialize: 
				// @see{https://github.com/WordPress/WordPress/blob/3.5.1/wp-includes/meta.php#L289}
				$meta_value = maybe_unserialize( $meta_value );
				add_post_meta( $new_event_id, $meta_key, $meta_value );
			}
		}
	}
	_eventorganiser_delete_calendar_cache();

	/**
	 * Triggered after an occurrence has been broken from an event.
	 *
	 * @param int $post_id The ID of the original parent event
	 * @param int $occurrence_id The ID of the occurrence being broken
	 * @param int $new_event_id The ID of the newly created event
	 */
	do_action( 'eventorganiser_occurrence_broken', $post_id, $occurrence_id, $new_event_id );

	wp_reset_postdata();
	return $new_event_id;
}

/**
 * Returns a UID for an event
 * 
 * If the UID is not found it generates one based on event (post) ID, a timestamp, blog ID and server address.
 * 
 * @since 2.1
 * @param int $post_id The event (post) ID. If ommitted 'current event' is used
 * @return string The UID, or false if error.
 */
function eo_get_event_uid( $post_id = 0 ){
	
	$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );
	
	if( empty( $post_id ) )
		return false;
	
	$uid = get_post_meta( get_the_ID(), '_eventorganiser_uid', true );
	
	if( empty( $uid ) ){
		$now = new DateTime();
		$address = isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : 'unknown';
		$uid = implode( '-', array( $now->format('Ymd\THi\Z'), microtime(true), 'EO', get_the_ID(), get_current_blog_id() ) ).'@'.$address;
		add_post_meta( get_the_ID(), '_eventorganiser_uid', $uid );
	}
	
	return $uid;
}


/**
 * Helper function to generate a datetime format for an event's date.
 * 
 * If the event is an all day event, the date format is returned. If it 
 * is not an all-day event, the time format appended to the time format 
 * and that is returned.
 * 
 * If date/time formats are not provided, the site's settings are used.
 * Can be used inside the loop.
 *  
 * @param int    $event_id
 * @param string $date_format
 * @param string $time_format
 */
function eo_get_event_datetime_format( $event_id = false, $date_format = false, $time_format = false ){
	
	$event_id    = (int) ( empty( $event_id ) ? get_the_ID() : $event_id );
	
	$date_format = ( $date_format === false ? trim( get_option( 'date_format' ) ) : $date_format );
	$time_format = ( $time_format === false ? trim( get_option( 'time_format' ) ) : $time_format );

	$format = ( eo_is_all_day( $event_id ) ? $date_format :  $date_format . ' ' . $time_format );
	
	return $format;
}
?>
