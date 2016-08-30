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
* * `schedule` => (custom | once | daily | weekly | monthly | yearly)  -- specifies the recurrence pattern
* * `schedule_meta` =>
*   * For monthly schedules,
*      * (string) BYMONTHDAY=XX to repeat on XXth day of month, e.g. BYMONTHDAY=01 to repeat on the first of every month.
*      * (string) BYDAY=ND. N= 1|2|3|4|-1 (first, second, third, fourth, last). D is day of week SU|MO|TU|WE|TH|FR|SA. E.g. BYDAY=2TU (repeat on second tuesday)
*   * For weekly schedules,
*      * (array) Days to repeat on: (SU,MO,TU,WE,TH,FR,SA). e.g. set to array('SU','TU') to repeat on Tuesdays & Sundays. 
*      * Can be left blank to repeat weekly from the start date.
* * `frequency` => (int) positive integer, sets frequency of recurrence (every 2 days, or every 3 days etc)
* * `all_day` => 1 if its an all day event, 0 if not
* * `start` =>  start date (of first occurrence)  as a datetime object
* * `end` => end date (of first occurrence)  as a datetime object
* * `until` =>  **START** date of last occurrence (or upper-bound thereof) as a datetime object
* * `schedule_last` =>  Alias of until. Deprecated 2.13.0, use until.
* * `number_occurrences` => Instead of specifying `until` you can specify the number of occurrence a recurring event should have. 
* This is only used if `until` is not, and for daily, weekly, monthly or yearly recurring events.
* * `include` => array of datetime objects to include in the schedule
* * `exclude` => array of datetime objects to exclude in the schedule
*
* @since 1.5
* @uses wp_insert_post()
*
* @param int $post_id - the event (post) ID for the event you want to update
* @param array $event_data - array of event data
* @param array $post_data - array of data to be used by wp_update_post.
* @return int $post_id - the post ID of the updated event
*/
function eo_update_event( $post_id, $event_data = array(), $post_data = array() ){

	$post_id = (int) $post_id;
	
	$input = array_merge( $post_data, $event_data );
	
	//Backwards compat:
	if( !empty( $input['venue'] ) ){
		$input['tax_input']['event-venue'] = $input['venue'];
	}
	if( !empty( $input['category'] ) ){
		$input['tax_input']['event-category'] = $input['category'];
	}
	
	$event_keys = array_flip( array( 
		'start', 'end', 'schedule', 'schedule_meta', 'frequency', 
		'all_day', 'until', 'schedule_last', 'include', 'exclude', 'occurs_by', 'number_occurrences',
	) );
	
	$post_keys = array_flip( array(
		'post_title','post_content','post_status', 'post_type','post_author','ping_status','post_parent','menu_order', 
		'to_ping', 'pinged', 'post_password', 'guid', 'post_content_filtered', 'post_excerpt', 'import_id', 'tax_input',
		'comment_status', 'context', 'post_date', 'post_date_gmt',
	) );
	
	$event_data = array_intersect_key( $input, $event_keys );
	$post_data = array_intersect_key( $input, $post_keys ) + $post_data;
	 
	if( empty( $post_id ) ){
		return new WP_Error( 'eo_error', 'Empty post ID.' );
	}
		
	/**
	 *@ignore
	 */
	$event_data = apply_filters( 'eventorganiser_update_event_event_data', $event_data, $post_id, $post_data, $event_data );
	/**
	 *@ignore
	 */
	$post_data = apply_filters( 'eventorganiser_update_event_post_data', $post_data, $post_id, $post_data, $event_data );

	if( !empty($post_data) ){
		$post_data['ID'] = $post_id;		
		wp_update_post( $post_data );
	}

	/**
	 * Backwards compatability.
	 * See https://github.com/stephenharris/Event-Organiser/issues/259
	 */
	if( isset( $event_data['schedule_last'] ) ){
		if( !isset( $event_data['until'] ) ){
			$event_data['until'] = $event_data['schedule_last'];
		}
		unset( $event_data['schedule_last'] );
	}
	
	//Get previous data, parse with data to be updated
	$prev = eo_get_event_schedule( $post_id );
	$event_data = wp_parse_args( $event_data, $prev );

	//If schedule is 'once' and dates are included - set to 'custom':
	if( ( empty($event_data['schedule']) || 'once' == $event_data['schedule'] ) && !empty($event_data['include']) ){
		$event_data['schedule'] = 'custom';
	}
	
	$event_data = _eventorganiser_generate_occurrences( $event_data );

	if( is_wp_error( $event_data ) ){
		return $event_data;
	}

	//Insert new dates, remove old dates and update meta
	$re = _eventorganiser_insert_occurrences( $post_id, $event_data );

	/**
	 * Triggered after an event has been updated.
	 *
	 * @param int $post_id The ID of the event
	 */
	do_action( 'eventorganiser_save_event', $post_id );

	/**
	 * Fires after an event has been updated.
	 *
	 * @param int $post_id The ID of the event.
	 */
	do_action( 'eventorganiser_updated_event', $post_id );

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
* * `schedule` => (custom | once | daily | weekly | monthly | yearly)  -- specifies the recurrence pattern
* * `schedule_meta` =>
*   * For monthly schedules,
*      * (string) BYMONTHDAY=XX to repeat on XXth day of month, e.g. BYMONTHDAY=01 to repeat on the first of every month.
*      * (string) BYDAY=ND. N= 1|2|3|4|-1 (first, second, third, fourth, last). D is day of week SU|MO|TU|WE|TH|FR|SA. E.g. BYDAY=2TU (repeat on second tuesday)
*   * For weekly schedules,
*      * (array) Days to repeat on: (SU,MO,TU,WE,TH,FR,SA). e.g. set to array('SU','TU') to repeat on Tuesdays & Sundays. 
*      * Can be left blank to repeat weekly from the start date.
* * `frequency` => (int) positive integer, sets frequency of recurrence (every 2 days, or every 3 days etc)
* * `all_day` => 1 if its an all day event, 0 if not
* * `start` =>  start date (of first occurrence)  as a datetime object
* * `end` => end date (of first occurrence)  as a datetime object
* * `until` =>  **START** date of last occurrence (or upper-bound thereof) as a datetime object
* * `schedule_last` =>  Alias of until. Deprecated 2.13.0, use until.
* * `number_occurrences` => Instead of specifying `until` you can specify the number of occurrence a recurring event should have. 
* This is only used if `until` is not, and for daily, weekly, monthly or yearly recurring events.
* * `include` => array of datetime objects to include in the schedule
* * `exclude` => array of datetime objects to exclude in the schedule
*
* ### Example
* The following example creates an event which starts on the 3rd December 2012 15:00 and ends on the 4th December 15:00 and repeats every 4 days until the 25th December (So the last occurrence actually ends on the 23rd).
* <code>
*     $event_data = array(
*	     'start'     => new DateTime('2012-12-03 15:00', eo_get_blog_timezone() ),
*	     'end'       => new DateTime('2012-12-04 15:00', eo_get_blog_timezone() ),
*	     'until'     => new DateTime('2012-12-25 15:00', eo_get_blog_timezone() ),
*	     'frequency' => 4,
*	     'all_day'   => 0,
*	     'schedule'  => 'daily',
*    );
*     $post_data = array(
*	     'post_title'=>'The Event Title',
*	     'post_content'=>'My event content',
*    );
*
*    $e = eo_insert_event($post_data,$event_data);
* </code>
* 
* ### Tutorial
* See this <a href="http://www.stephenharris.info/2012/front-end-event-posting/">tutorial</a> or <a href="https://gist.github.com/3867194">this Gist</a> on front-end event posting.
*
* @since 1.5
* @link http://www.stephenharris.info/2012/front-end-event-posting/ Tutorial on front-end event posting
* @uses wp_insert_post() 
*
* @param array $post_data array of data to be used by wp_insert_post.
* @param array $event_data array of event data
* @return int the post ID of the updated event
*/
function eo_insert_event( $post_data = array(), $event_data = array() ){
	global $wpdb;

	$input = array_merge( $post_data, $event_data );
	
	//Backwards compat:
	if( !empty( $input['venue'] ) ){
		$input['tax_input']['event-venue'] = $input['venue'];
	}
	if( !empty( $input['category'] ) ){
		$input['tax_input']['event-category'] = $input['category'];
	}
	
	$event_keys = array_flip( array(
		'start', 'end', 'schedule', 'schedule_meta', 'frequency', 'all_day', 
		'until', 'schedule_last', 'include', 'exclude', 'occurs_by', 'number_occurrences', 
	) );
	
	$post_keys = array_flip( array(
		'post_title','post_content','post_status', 'post_type','post_author','ping_status','post_parent','menu_order', 
		'to_ping', 'pinged', 'post_password', 'guid', 'post_content_filtered', 'post_excerpt', 'import_id', 'tax_input',
		'comment_status', 'context',  'post_date', 'post_date_gmt',
	) );
	
	$event_data = array_intersect_key( $input, $event_keys ) + $event_data;
	$post_data = array_intersect_key( $input, $post_keys );
		
	//If schedule is 'once' and dates are included - set to 'custom':
	if( ( empty($event_data['schedule']) || 'once' == $event_data['schedule'] ) && !empty($event_data['include']) ){
		$event_data['schedule'] = 'custom';
	}
	
	if( !empty( $event_data['schedule_last'] ) ){
		if( !isset( $event_data['until'] ) ){
			$event_data['until'] = clone $event_data['schedule_last'];
		}
		unset( $event_data['schedule_last'] );
	}

	$event_data = _eventorganiser_generate_occurrences( $event_data );
		
	if( is_wp_error( $event_data ) ){
		return $event_data;
	}

	/**
	 *@ignore
	 */
	$event_data = apply_filters( 'eventorganiser_insert_event_event_data', $event_data, $post_data, $event_data );
	
	/**
	 *@ignore
	 */
	$post_data = apply_filters( 'eventorganiser_insert_event_post_data', $post_data, $post_data, $event_data );
		
	//Finally we create event (first create the post in WP)
	$post_input = array_merge(array('post_title'=>'untitled event'), $post_data, array('post_type'=>'event'));			
	$post_id = wp_insert_post($post_input, true);

	//Did the event insert correctly? 
	if ( is_wp_error( $post_id) ) 
			return $post_id;

	_eventorganiser_insert_occurrences($post_id, $event_data);
			
	//Action used to break cache & trigger Pro actions (& by other plug-ins?)
	/**
	 * Triggered after an event has been updated.
	 * 
	 * @param int $post_id The ID of the event 
	 */
	do_action( 'eventorganiser_save_event', $post_id );

	/**
	 * Fires after an event has been created.
	 *
	 * @param int $post_id The ID of the event.
	 */
	do_action( 'eventorganiser_created_event', $post_id );

	return $post_id;
}


function _eventorganiser_maybe_duplicate_post( $new_post_id, $old_post ){

	if( 'event' == get_post_type( $new_post_id ) ){
		eo_update_event( $new_post_id, eo_get_event_schedule( $old_post->ID ) );
	}

}
add_action( 'dp_duplicate_post', '_eventorganiser_maybe_duplicate_post', 50, 2 );

/**
 * Deletes all occurrences for an event (removes them from the eo_events table).
 * Triggers {@see `eventorganiser_delete_event`} (this action is used to break the caches).
 *
 * This function does not update any of the event schedule details.
 * **Don't call this unless you know what you're doing**.
 * 
 * @since 1.5
 * @access private
 * @param int $post_id the event's (post) ID to be deleted
 * @param int|array $occurrence_ids Occurrence ID (or array of IDs) for specificaly occurrences to delete. If empty/false, deletes all.
 * 
 */
function eo_delete_event_occurrences( $event_id, $occurrence_ids = false ){
	global $wpdb;
	//TODO use this in break/remove occurrence
	
	//Let's just ensure empty is cast as false
	$occurrence_ids = ( empty( $occurrence_ids ) ? false : $occurrence_ids );
	
	if( $occurrence_ids !== false ){
		$occurrence_ids = (array) $occurrence_ids;
		$occurrence_ids = array_map( 'absint', $occurrence_ids );
		$occurrence_ids_in = implode( ', ', $occurrence_ids );
		
		$raw_sql = "DELETE FROM $wpdb->eo_events WHERE post_id=%d AND event_id IN( $occurrence_ids_in )";

	}else{
		$raw_sql = "DELETE FROM $wpdb->eo_events WHERE post_id=%d";
	}
	
	/**
	 * @ignore
	 */
	do_action( 'eventorganiser_delete_event', $event_id, $occurrence_ids ); //Deprecated - do not use!
	
	/**
	 * Triggers just before the specified occurrences for the event are deleted.
	 * 
	 * @param int $event_id The (post) ID of the event of which we're deleting occurrences.
	 * @param array|false $occurrence_ids An array of occurrences to be delete. If `false`, all occurrences are to be removed.
	 */
	do_action( 'eventorganiser_delete_event_occurrences', $event_id, $occurrence_ids );
	
	$del = $wpdb->get_results( $wpdb->prepare(  $raw_sql, $event_id ) );
	
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
function _eventorganiser_insert_occurrences( $post_id, $event_data ) {

	global $wpdb;

	$tz = eo_get_blog_timezone();

	$start       = $event_data['start'];
	$end         = $event_data['end'];
	$occurrences = $event_data['occurrences'];

	//Don't use date_diff (requires php 5.3+)
	//Also see https://github.com/stephenharris/Event-Organiser/issues/205
	//And https://github.com/stephenharris/Event-Organiser/issues/224
	$duration_str = eo_date_interval( $start, $end, '+%y year +%m month +%d days +%h hours +%i minutes +%s seconds' );

	$event_data['duration_str'] = $duration_str;

	$schedule_last_end = clone $event_data['schedule_last'];
	$schedule_last_end->modify( $duration_str );

	//Get dates to be deleted / added
	$current_occurrences = eo_get_the_occurrences( $post_id );
	$current_occurrences = $current_occurrences ? $current_occurrences : array();

	$delete   = array_udiff( $current_occurrences, $occurrences, '_eventorganiser_compare_dates' );
	$insert   = array_udiff( $occurrences, $current_occurrences, '_eventorganiser_compare_dates' );
	$update   = array_uintersect( $occurrences, $current_occurrences, '_eventorganiser_compare_dates' );
	$update_2 = array_uintersect( $current_occurrences, $update, '_eventorganiser_compare_dates' );
	$keys     = array_keys( $update_2 );

	if ( $delete ) {
		$delete_occurrence_ids = array_keys( $delete );
		eo_delete_event_occurrences( $post_id, $delete_occurrence_ids );
	}

	$occurrence_cache = array();
	$occurrence_array = array();

	if ( $update ) {
		$update = array_combine( $keys, $update );

		foreach ( $update as $occurrence_id => $occurrence ) {

			$occurrence_end = clone $occurrence;
			$occurrence_end->modify( $duration_str );

			$occurrence_input = array(
				'StartDate'        => $occurrence->format( 'Y-m-d' ),
				'StartTime'        => $occurrence->format( 'H:i:s' ),
				'EndDate'          => $occurrence_end->format( 'Y-m-d' ),
				'FinishTime'       => $occurrence_end->format( 'H:i:s' ),
			);

			$wpdb->update(
				$wpdb->eo_events,
				$occurrence_input,
				array( 'event_id' => $occurrence_id )
			);

			$occurrence_array[$occurrence_id] = $occurrence->format( 'Y-m-d H:i:s' );
			$occurrence_cache[$occurrence_id] = array(
				'start' => $occurrence,
				'end'   => new DateTime( $occurrence_end->format( 'Y-m-d' ) . ' ' . $end->format( 'H:i:s' ), eo_get_blog_timezone() ),
			);
		}
	}

	if ( $insert ) {
		foreach ( $insert as $counter => $occurrence ) :
			$occurrence_end = clone $occurrence;
			$occurrence_end->modify( $duration_str );

			$occurrence_input = array(
				'post_id'          => $post_id,
				'StartDate'        => $occurrence->format( 'Y-m-d' ),
				'StartTime'        => $occurrence->format( 'H:i:s' ),
				'EndDate'          => $occurrence_end->format( 'Y-m-d' ),
				'FinishTime'       => $end->format( 'H:i:s' ),
				'event_occurrence' => $counter,
			);

			$wpdb->insert( $wpdb->eo_events, $occurrence_input );

			$occurrence_array[$wpdb->insert_id] = $occurrence->format( 'Y-m-d H:i:s' );
			$occurrence_cache[$wpdb->insert_id] = array(
				'start' => $occurrence,
				'end'   => new DateTime( $occurrence_end->format( 'Y-m-d' ) . ' ' . $end->format( 'H:i:s' ), $tz ),
			);
		endforeach;
	}

	//Set occurrence cache
	wp_cache_set( 'eventorganiser_occurrences_'.$post_id, $occurrence_cache );
	wp_cache_set( 'eventorganiser_all_occurrences_'.$post_id, $occurrence_cache );

	unset( $event_data['occurrences'] );

	if ( ! empty( $event_data['include'] ) ) {
		$event_data['include'] = array_map( 'eo_format_datetime', $event_data['include'], array_fill( 0, count( $event_data['include'] ), 'Y-m-d H:i:s' ) );
	}

	if ( ! empty( $event_data['exclude'] ) ) {
		$event_data['exclude'] = array_map( 'eo_format_datetime', $event_data['exclude'], array_fill( 0, count( $event_data['exclude'] ), 'Y-m-d H:i:s' ) );
	}

	update_post_meta( $post_id, '_eventorganiser_schedule_start_start', $start->format( 'Y-m-d H:i:s' ) );
	update_post_meta( $post_id, '_eventorganiser_schedule_start_finish', $end->format( 'Y-m-d H:i:s' ) );
	update_post_meta( $post_id, '_eventorganiser_schedule_until', $event_data['until']->format( 'Y-m-d H:i:s' ) );
	update_post_meta( $post_id, '_eventorganiser_schedule_last_start', $event_data['schedule_last']->format( 'Y-m-d H:i:s' ) );
	update_post_meta( $post_id, '_eventorganiser_schedule_last_finish', $schedule_last_end->format( 'Y-m-d H:i:s' ) );

	unset( $event_data['start'] );
	unset( $event_data['end'] );
	unset( $event_data['schedule_start'] );
	unset( $event_data['schedule_last'] );
	unset( $event_data['until'] );

	update_post_meta( $post_id, '_eventorganiser_event_schedule', $event_data );

	return $post_id;
}


/**
* Gets schedule meta from the database (post meta)
* Datetimes are converted to DateTime objects, in blog's currenty timezone
*
*  Event details include
*
* * `schedule` => (custom | once | daily | weekly | monthly | yearly)  -- specifies the recurrence pattern
* * `schedule_meta` =>
*   * For monthly schedules,
*      * (string) BYMONTHDAY=XX to repeat on XXth day of month, e.g. BYMONTHDAY=01 to repeat on the first of every month.
*      * (string) BYDAY=ND. N= 1|2|3|4|-1 (first, second, third, fourth, last). D is day of week SU|MO|TU|WE|TH|FR|SA. E.g. BYDAY=2TU (repeat on second tuesday)
*   * For weekly schedules,
*      * (array) Days to repeat on: (SU,MO,TU,WE,TH,FR,SA). e.g. set to array('SU','TU') to repeat on Tuesdays & Sundays. 
* * `occurs_by` - For use with monthly schedules: how the event recurs: BYDAY or BYMONTHDAY
* * `frequency` => (int) positive integer, sets frequency of recurrence (every 2 days, or every 3 days etc)
* * `all_day` => 1 if its an all day event, 0 if not
* * `start` =>  start date (of first occurrence)  as a datetime object
* * `end` => end date (of first occurrence)  as a datetime object
* * `until` => For recurring events, the date they repeat until. Note that this may not be equal to `schedule_last` if
*              dates are included/excluded. 
* * `schedule_last` =>  **START** date of last occurrence as a datetime object
* * `include` => array of datetime objects to include in the schedule
* * `exclude` => array of datetime objects to exclude in the schedule
*
* @param int $post_id -  The post ID of the event
* @return array event schedule details
*/
function eo_get_event_schedule( $post_id = 0 ){

	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if( empty( $post_id ) ){ 
		return false;
	}

	$event_details = get_post_meta( $post_id,'_eventorganiser_event_schedule', true );
	$event_details = wp_parse_args($event_details, array(
		'schedule'           => 'once',
		'schedule_meta'      => '',
		'number_occurrences' => 0, //Number occurrences according to recurrence rule. Not necessarily the #occurrences (after includes/excludes)
		'frequency'          => 1,
		'all_day'            => 0,
		'duration_str'       => '',
		'include'            => array(),
		'exclude'            => array(),
		'_occurrences'       => array(),
	));

	$tz = eo_get_blog_timezone();

	// Get start time
	if ( $start_datetime = get_post_meta( $post_id,'_eventorganiser_schedule_start_start', true ) ) {
		$event_details['start'] = new DateTime( $start_datetime, $tz );

	} else {
		// No start time, so set a default start time to next half-hour
		$now = new DateTime( 'now', $tz );
		
		$minute = $now->format( 'i' ) > 30 ? 0 : 30;
		
		$now->setTime( $now->format( 'G' ), $minute );
		
		if( 0 === $minute ){
			$now->modify( '+1 hour' );
		}
		
		$event_details['start'] = $now; 
	}

	// Get end time
	if ( $end_datetime = get_post_meta( $post_id,'_eventorganiser_schedule_start_finish', true ) ) {
		$event_details['end'] = new DateTime( $end_datetime, $tz );

	} else {
		// No end time, so set a default end time
		$event_details['end'] = clone $event_details['start'];
		$event_details['end']->modify( '+1 hour' );
	}

	$event_details['schedule_start']  = clone $event_details['start'];
	$event_details['schedule_last']   = new DateTime( get_post_meta( $post_id,'_eventorganiser_schedule_last_start', true ), $tz );
	$event_details['schedule_finish'] = new DateTime( get_post_meta( $post_id,'_eventorganiser_schedule_last_finish', true ), $tz );

	if ( get_post_meta( $post_id,'_eventorganiser_schedule_until', true ) ) {
		$event_details['until'] = new DateTime( get_post_meta( $post_id,'_eventorganiser_schedule_until', true ), $tz );
	} else {
		$event_details['until'] = clone $event_details['schedule_last'];
		update_post_meta( $post_id, '_eventorganiser_schedule_until', $event_details['until']->format( 'Y-m-d H:i:s' ) );
	}
	
	if ( ! empty( $event_details['include'] ) ) {
		$event_details['include'] = array_map( 'eventorganiser_date_create', $event_details['include'] );
	}
	if( ! empty($event_details['exclude'] ) ){
		$event_details['exclude'] = array_map( 'eventorganiser_date_create', $event_details['exclude'] );
	}

	if ( 'weekly' == $event_details['schedule'] ) {
		$event_details['occurs_by'] = '';
	} elseif ( 'monthly' == $event_details['schedule'] ) {
		$bymonthday = preg_match( '/BYMONTHDAY=/', $event_details['schedule_meta'] );
		$event_details['occurs_by'] = ( $bymonthday ? 'BYMONTHDAY' : 'BYDAY' );
	} else {
		$event_details['occurs_by'] ='';
	}

	/**
	 * Filters the schedule metadata for an event (as returned by `eo_get_event_schedule()`.
	 * 
	 * See documentation on `eo_get_event_schedule()` for more details.
	 *
	 * @param array $event_details Details of the event's dates and recurrence pattern
	 * @param int $post_id The ID of the event
	 */
	$event_details = apply_filters( 'eventorganiser_get_event_schedule', $event_details, $post_id );
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
function _eventorganiser_generate_occurrences( $schedule ) {

	$event_defaults = array(
		'start' => '', 'end' => '', 'all_day' => 0,
		'schedule' => 'once', 'schedule_meta' => '', 'frequency' => 1, 'schedule_last' => '',
		'until' => '', 'number_occurrences' => 0, 'exclude' => array(), 'include' => array(),
	);

	$schedule = wp_parse_args( $schedule, $event_defaults );
	$start    = $schedule['start'];
	$end      = $schedule['end'];
	$until    = $schedule['until'];
	$schedule_meta = $schedule['schedule_meta'];

	$occurrences = array(); //occurrences array

	$exclude = array_filter( (array) $schedule['exclude'] );
	$include = array_filter( (array) $schedule['include'] );

	$exclude = array_udiff( $exclude, $include, '_eventorganiser_compare_datetime' );
	$include = array_udiff( $include, $exclude, '_eventorganiser_compare_datetime' );

	//White list schedule
	if ( ! in_array( $schedule['schedule'], array( 'once', 'daily', 'weekly', 'monthly', 'yearly', 'custom' ) ) ) {
		return new WP_Error( 'eo_error', __( 'Schedule not recognised.', 'eventorganiser' ) );
	}

	//Ensure event frequency is a positive integer. Else set to 1.
	$frequency          = max( absint( $schedule['frequency'] ), 1 );
	$all_day            = (int) $schedule['all_day'];
	$number_occurrences = absint( $schedule['number_occurrences'] );

	//Check dates are supplied and are valid
	if ( ! ( $start instanceof DateTime ) ) {
		return new WP_Error( 'eo_error', __( 'Start date not provided.', 'eventorganiser' ) );
	}

	if ( ! ( $end instanceof DateTime ) ) {
		$end = clone $start;
	}

	//If use 'number_occurrences' to limit recurring event, set dummy 'schedule_last' date.
	if ( ! ( $until instanceof DateTime ) && $number_occurrences && in_array( $schedule['schedule'], array( 'daily', 'weekly', 'monthly', 'yearly' ) ) ) {
		//Set dummy "last occurrance" date.
		$until = clone $start;
	} else {
		$number_occurrences = 0;
	}

	if ( 'once' == $schedule['schedule'] || ! ( $until instanceof DateTime ) ) {
		$until = clone $start;
	}

	//Check dates are in chronological order
	if ( $end < $start ) {
		return new WP_Error( 'eo_error', __( 'Start date occurs after end date.', 'eventorganiser' ) );
	}

	if ( $until < $start ) {
		return new WP_Error( 'eo_error', __( 'Schedule end date is before is before the start date.', 'eventorganiser' ) );
	}

	$event_timezone = $start->getTimezone();
	$hour = intval( $start->format( 'H' ) );
	$min  = intval( $start->format( 'i' ) );

	$start_days = array();
	$workaround = '';
	$icaldays = array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' );
	$weekdays = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	$ical2day = array( 'SU' => 'Sunday', 'MO' => 'Monday', 'TU' => 'Tuesday', 'WE' => 'Wednesday', 'TH' => 'Thursday', 'FR' => 'Friday', 'SA' => 'Saturday' );

	//Set up schedule
	switch ( $schedule['schedule'] ) :
		case 'once':
		case 'custom':
			$frequency = 1;
			$schedule_meta = '';
			$until = clone $start;
			$start_days[] = clone $start;
			$workaround = 'once';//Not strictly a workaround.
			break;

		case 'daily':
			$interval = sprintf( '+%d day', $frequency );
			$start_days[] = clone $start;
			break;

		case 'weekly':
			$schedule_meta = ( $schedule_meta ? array_filter( $schedule_meta ) : array() );
			if ( ! empty( $schedule_meta ) && is_array( $schedule_meta ) ) :
				foreach ( $schedule_meta as $day ) :
					$start_day = clone $start;
					$start_day->modify( $ical2day[$day] );
					$start_days[] = $start_day;
				endforeach;
			else :
				$schedule_meta = array( $icaldays[ $start->format( 'w' ) ] );
				$start_days[] = clone $start;
			endif;

			$interval = sprintf( '+%d week', $frequency );
			break;

		case 'monthly':
			$start_days[] = clone $start;
			$rule_value = explode( '=', $schedule_meta, 2 );
			$rule   = $rule_value[0];
			$values = ! empty( $rule_value[1] ) ? explode( ',', $rule_value[1] ) : array();//Should only be one value, but may support more in future
			$values = array_filter( $values );

			if ( 'BYMONTHDAY' == $rule ) :
				$date     = (int) $start_days[0]->format( 'd' );
				$interval = sprintf( '+%d month', $frequency );

				if ( $date >= 29 ) {
					$workaround = 'short months';    //This case deals with 29/30/31 of month
				}

				$schedule_meta = 'BYMONTHDAY='.$date;

			else :
				if ( empty( $values ) ) {
					$date    = (int) $start_days[0]->format( 'd' );
					$n       = ceil( $date / 7 ); // nth weekday of month.
					$day_num = intval( $start_days[0]->format( 'w' ) ); //0 (Sun) - 6(Sat)

				} else {
					//expect e.g. array( 2MO )
					preg_match( '/^(-?\d{1,2})([a-zA-Z]{2})/', $values[0], $matches );
					$n = (int) $matches[1];
					$day_num = array_search( $matches[2], $icaldays );//(Sun) - 6(Sat)
				}

				if ( 5 == $n ) {
					$n = -1;//If 5th, interpret it as last.
				}
				$ordinal = array( '1' => 'first', '2' => 'second', '3' => 'third' , '4' => 'fourth', '-1' => 'last' );

				if ( ! isset( $ordinal[$n] ) ) {
					return new WP_Error( 'eo_error', __( 'Invalid monthly schedule (invalid ordinal)', 'eventorganiser' ) );
				}

				$ical_day = $icaldays[$day_num];  //ical day from day_num (SU - SA)
				$day = $weekdays[$day_num];//Full day name from day_num (Sunday -Monday)
				$schedule_meta = 'BYDAY='.$n.$ical_day; //E.g. BYDAY=2MO
				$interval = $ordinal[$n].' '.$day.' of +'.$frequency.' month'; //E.g. second monday of +1 month

				//Work around for PHP <5.3
				if ( ! function_exists( 'date_diff' ) ) {
					$workaround = 'php5.2';
				}
			endif;
			break;

		case 'yearly':
			$start_days[] = clone $start;
			if ( '29-02' == $start_days[0]->format( 'd-m' ) ) {
				$workaround = 'leap year';
			}
			$interval = sprintf( '+%d year', $frequency );
			break;
	endswitch; //End $schedule['schedule'] switch

	//Now we have setup and validated the schedules - loop through and generate occurrences
	foreach ( $start_days as $index => $start_day ) :
		$current = clone $start_day;
		$occurrence_n = 0;

		switch ( $workaround ) :
			//Not really a workaround. Just add the occurrence and finish.
			case 'once':
				$current->setTime( $hour, $min );
				$occurrences[] = clone $current;
				break;

			//Loops for monthly events that require php5.3 functionality
			case 'php5.2':
				while ( $current <= $until || $occurrence_n < $number_occurrences ) :
					$current->setTime( $hour, $min );
					$occurrences[] = clone $current;
					$current = _eventorganiser_php52_modify( $current, $interval );
					$occurrence_n++;
				endwhile;
				break;

			//Loops for monthly events on the 29th/30th/31st
			case 'short months':
				$day_int = intval( $start_day->format( 'd' ) );

				//Set the first month
				$current_month = clone $start_day;
				$current_month = date_create( $current_month->format( 'Y-m-1' ) );

				while ( $current_month <= $until || $occurrence_n < $number_occurrences ) :
					$month_int = intval( $current_month->format( 'm' ) );
					$year_int  = intval( $current_month->format( 'Y' ) );

					if ( checkdate( $month_int , $day_int , $year_int ) ) {
						$current = new DateTime( $day_int . '-' . $month_int . '-' . $year_int, $event_timezone );
						$current->setTime( $hour, $min );
						$occurrences[] = clone $current;
						$occurrence_n++;
					}
					$current_month->modify( $interval );
				endwhile;
				break;

			//To be used for yearly events occuring on Feb 29
			case 'leap year':
				$current_year = clone $current;
				$current_year->modify( '-1 day' );

				while ( $current_year <= $until || $occurrence_n < $number_occurrences  ) :
					$is_leap_year = (int) $current_year->format( 'L' );

					if ( $is_leap_year ) {
						$current = clone $current_year;
						$current->modify( '+1 day' );
						$current->setTime( $hour, $min );
						$occurrences[] = clone $current;
						$occurrence_n++;
					}

					$current_year->modify( $interval );
				endwhile;
				break;

			default:
				while ( $current <= $until || $occurrence_n < $number_occurrences  ) :
					$current->setTime( $hour, $min );
					$occurrences[] = clone $current;
					$current->modify( $interval );
					$occurrence_n++;
				endwhile;
				break;

		endswitch;//End 'workaround' switch;
	endforeach;

	$timezone = eo_get_blog_timezone();
	$start->setTimezone( $timezone );
	$end->setTimezone( $timezone );
	foreach ( $occurrences as $occurrence ) {
		$occurrence->setTimezone( $timezone );
	}

	//Now schedule meta is set up and occurrences are generated.
	if ( $number_occurrences > 0 ) {
		//If recurrence is limited by #occurrences. Do that here.
		sort( $occurrences );
		$occurrences = array_slice( $occurrences, 0, $number_occurrences );
		$until = end( $occurrences );
	}

	//Cast includes/exclude to timezone
	$tz = eo_get_blog_timezone();
	if ( $include ) {
		foreach ( $include as $included_date ) {
			$included_date->setTimezone( $tz );
		}
	}
	if ( $exclude ) {
		foreach ( $exclude as $excluded_date ) {
			$excluded_date->setTimezone( $tz );
		}
	}

	//Add inclusions, removes exceptions and duplicates
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		//Make sure 'included' dates doesn't appear in generate date
		$include = array_udiff( $include, $occurrences, '_eventorganiser_compare_datetime' );
	}
	$occurrences = array_merge( $occurrences, $include );
	$occurrences = array_udiff( $occurrences, $exclude, '_eventorganiser_compare_datetime' );
	$occurrences = _eventorganiser_remove_duplicates( $occurrences );

	//Sort occurrences
	sort( $occurrences );

	if ( empty( $occurrences ) || ! $occurrences[0] || ! ( $occurrences[0] instanceof DateTime ) ) {
		return new WP_Error( 'eo_error', __( 'Event does not contain any dates.', 'eventorganiser' ) );
	}

	$schedule_start = clone $occurrences[0];
	$schedule_last  = clone end( $occurrences );

	$_event_data = array(
		'start'          => $start,
		'end'            => $end,
		'all_day'        => $all_day,
		'schedule'       => $schedule['schedule'],
		'schedule_meta'  => $schedule_meta,
		'frequency'      => $frequency,
		'until'          => $until,
		'schedule_start' => $schedule_start,
		'schedule_last'  => $schedule_last,
		'exclude'        => $exclude,
		'include'        => $include,
		'occurrences'    => $occurrences,
	);

	/**
	 * Filters the event schedule after its dates has been generated by a given schedule.
	 *
	 * The filtered array is an array of occurrences generated from a
	 * schedule which may include:
	 *
	 * * **start** (DateTime) -  when the event starts
	 * * **end** (DateTime) - when the event ends
	 * * **all_day** (Bool) - If the event is all day or no
	 * * **all_day** (Bool) - If the event is all day or not
	 * * **schedule** (String) - One of once|weekl|daily|monthly|yearly|custom
	 * * **schedule_meta** (Array|String) - See documentation for `eo_insert_event()`
	 * * **frequency** (int) - The frequency of which the event repeats
	 * * **until** (DateTime) - date the schedule repeats until
	 * * **schedule_last** (DateTime) - date of last occurrence of event
	 * * **number_occurrences** (int) - number of times the event should repeat (if `until` is not specified).
	 * * **exclude** (array) - Array of DateTime objects  to exclude from the schedule
	 * * **include** (array) - Array of DateTime objects to include in the schedule
	 * * **occurrences** (array) - Array of DateTime objects generated from the above schedule.
	 *
	 * @param array $_event_data The event schedule with generated occurrences.
	 * @param array $event_data The original event schedule (without occurrences).
	 */
	$_event_data = apply_filters( 'eventorganiser_generate_occurrences', $_event_data, $schedule );
	return $_event_data;
}

/**
 * Generates the ICS RRULE fromthe event schedule data.
 * @access private
 * @ignore
 * @since 1.0.0
 * @package ical-functions
 *
 * @param int $post_id The event (post) ID. Uses current event if empty.
 * @return string The RRULE to be used in an ICS calendar
 */
function eventorganiser_generate_ics_rrule( $post_id = 0 ) {

	$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );

	$rrule = eo_get_event_schedule( $post_id );
	if ( ! $rrule ) {
		return false;
	}

	$utc = new DateTimeZone( 'UTC' );
	$rrule['schedule_last']->setTimezone( $utc );

	$rrule_array = array(
		'FREQ'       => strtoupper( $rrule['schedule'] ),
		'INTERVAL'   => (int) $rrule['frequency'],
		'BYDAY'      => null,
		'BYMONTHDAY' => null,
		'UNTIL'      => $rrule['schedule_last']->format( 'Ymd\THis\Z' ),
	);

	switch ( $rrule['schedule'] ) :

		case 'daily':
		case 'yearly':
			//Do nothing
			break;

		case 'monthly':
			//TODO Account for possible day shifts with timezone set to UTC
			$schedule_meta = explode( '=', $rrule['schedule_meta'] );//BYMONTHDAY=XXX or BYDAY=XXX
			$rrule_array[$schedule_meta[0]] = $schedule_meta[1];
			break;

		case 'weekly':
			if ( ! eo_is_all_day( $post_id ) ) {

				$timezone = ( get_option( 'timezone_string' ) ? eo_get_blog_timezone() : false );

				if ( ! $timezone ) {
					// We are using a UTC offset.
					// Start dates are converted to UTC (@see https://github.com/stephenharris/Event-Organiser/issues/293),
					// which may cause it to shift *day*. E.g. a 10pm Monday event in UTC-4 will a Tuesday event in UTC.
					// We may need to correct the BYDAY attribute to be valid for UTC.
					$days_of_week = array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' );

					//Get day shift upon timezone set to UTC
					$start     = eo_get_schedule_start( DATETIMEOBJ, $post_id );
					$local_day = (int) $start->format( 'w' );
					$start->setTimezone( $utc );
					$utc_day   = (int) $start->format( 'w' );
					$diff      = $utc_day - $local_day + 7; //ensure difference is positive (should be 0, +1 or +6).

					//If there is a shift correct BYDAY
					if ( $diff ) {
						$utc_days = array();

						foreach ( $rrule['schedule_meta'] as $day ) {
							$utc_day_index = ( array_search( $day, $days_of_week ) + $diff ) % 7;
							$utc_days[] = $days_of_week[$utc_day_index];
						}
						$rrule['schedule_meta'] = $utc_days;
					}
				}
			}
			$rrule_array['BYDAY'] = implode( ',', $rrule['schedule_meta'] );
			break;
		case 'once':
		case 'custom':
		default:
			return false;
	endswitch;

	$rrule_string = '';
	foreach ( $rrule_array as $key => $value ) {
		if ( ! is_null( $value ) ) {
			$rrule_string .= "$key=$value;";
		}
	}

	return rtrim( $rrule_string, ';' );
}

function eventorganiser_ical_vtimezone( $timezone, $from, $to ) {
	
	$vtimezone = "BEGIN:VTIMEZONE\r\n";
	$vtimezone .= sprintf( "TZID:%s\r\n", $timezone->getName() );
	
	//$timezone->getTransitions() doesn't accept any arguments in php 5.2, and would be ineffecient
	if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
		return '';
	}
	
	// get all transitions, and (as an estimate) an early one which we skip
	$transitions = $timezone->getTransitions( $from - YEAR_IN_SECONDS / 2, $to );
	
	if ( ! $transitions ) {
		return '';
	}

	foreach ( $transitions as $i => $trans ) {
		
		$pm      = $trans['offset'] >= 0 ? '+' : '-';
 		$hours   = floor( absint( $trans['offset'] ) / HOUR_IN_SECONDS ) % 24;
		$minutes = ( absint( $trans['offset'] ) - $hours * HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS;
		
		$tzto = $pm . str_pad( $hours, 2, '0', STR_PAD_LEFT ) . str_pad( $minutes, 2, '0', STR_PAD_LEFT );
	
		// skip the first entry, we just want it for the TZOFFSETFROM value of the next one
		if ( $i == 0 ) {
			$tzfrom = $tzto;
			if ( count( $transitions ) > 1 ) {
				continue;
			}
		}
		
		$type = $trans['isdst'] ? 'DAYLIGHT' : 'STANDARD';		
		$dt   = new DateTime( $trans['time'], $timezone );
			
		$vtimezone .= sprintf( "BEGIN:%s\r\n", $type );
		$vtimezone .= sprintf( "TZOFFSETFROM:%s\r\n", $tzfrom ); //needs formatting
		$vtimezone .= sprintf( "TZOFFSETTO:%s\r\n", $tzto ); //needs formatting
		$vtimezone .= sprintf( "DTSTART:%s\r\n",  $dt->format('Ymd\THis') );
		$vtimezone .= sprintf( "TZNAME:%s\r\n",  $trans['abbr'] ); 
		$vtimezone .= sprintf( "END:%s\r\n", $type );
		
		$tzfrom = $tzto;	
	}
	
	$vtimezone .= 'END:VTIMEZONE';
	
	return $vtimezone;
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
			return new WP_Error('eo_notice', '<strong>'.__("Occurrence not deleted. Occurrence not found.",'eventorganiser').'</strong>');

		$date = trim($remove->StartDate).' '.trim($remove->StartTime);

		$event_details = get_post_meta( $post_id,'_eventorganiser_event_schedule',true);

		if( ($key = array_search($date,$event_details['include'])) === false){
			//If the date was not manually included, add it to the 'exclude' array
			$event_details['exclude'][] = $date;
		}else{
			//If the date was manually included, just remove it from the included dates
			unset($event_details['include'][$key]);
		}

		//Update post meta and delete date from events table
		update_post_meta( $post_id,'_eventorganiser_event_schedule',$event_details);		
		eo_delete_event_occurrences( $post_id, $event_id );

		//Clear cache
		_eventorganiser_delete_calendar_cache();

		return true;
	}

	
/**
 * Updates a specific occurrence, and preserves the occurrence ID. 
 * 
 * Currently two occurrences cannot occupy the same date.
 * 
 * @ignore
 * @access private
 * @since 2.12.0
 * 
 * @param int $event_id      ID of the event whose occurrence we're moving
 * @param int $occurrence_id ID of the occurrence we're moving
 * @param DateTime $start    New start DateTime of the occurrence
 * @param DateTime $end      New end DateTime of the occurrence
 * @return bool|WP_Error True on success. WP_Error on failure.
 */
function eventorganiser_move_occurrence( $event_id, $occurrence_id, $start, $end ){

	global $wpdb;
		
	$old_start = eo_get_the_start( DATETIMEOBJ, $event_id, null, $occurrence_id );
	$schedule  = eo_get_event_schedule( $event_id );
	
	if( $start == $old_start ){
		return true;
	}
	
	$current_occurrences = eo_get_the_occurrences( $event_id );
	unset( $current_occurrences[$occurrence_id] );
	$current_occurrences = array_map( 'eo_format_datetime', $current_occurrences );
	
	if( in_array( $start->format( 'd-m-Y' ), $current_occurrences ) ){
		return new WP_Error( 'events-cannot-share-date', __( 'There is already an occurrence on this date', 'eventorganiser' ) );		
	}
	
	//We update the date directly in the DB first so the occurrence is not deleted and recreated,
	//but simply updated. 
	
	$wpdb->update(
		$wpdb->eo_events, 
		array(
			'StartDate'  => $start->format( 'Y-m-d' ),
			'StartTime'  => $start->format( 'H:i:s' ),
			'EndDate'    => $end->format( 'Y-m-d' ),
			'FinishTime' => $end->format( 'H:i:s' ),
		),				
		array( 'event_id' => $occurrence_id )
	);
	
	wp_cache_delete( 'eventorganiser_occurrences_'.$event_id );//Important: update DB clear cache
	wp_cache_delete( 'eventorganiser_all_occurrences_'.$event_id );//Important: update DB clear cache

	//Now update event schedule...
	
	//If date being removed was manually included remove it, 
	//otherwise add it to exclude. Then add new date as include.
	if( false === ( $index = array_search( $old_start, $schedule['include'] ) ) ){
		$schedule['exclude'][] = $old_start;
	}else{
		unset( $schedule['include'][$index] );
	}
	$schedule['include'][] = $start;

	$re = eo_update_event( $event_id, $schedule );
	
	if( $re && !is_wp_error( $re ) ){
		return true;
	}
	
	return $re;
}
?>
