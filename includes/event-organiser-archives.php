<?php 
/**
 * Registers our custom query variables
 *
 * @since 1.0.0
 */
add_filter('query_vars', 'eventorganiser_register_query_vars' );
function eventorganiser_register_query_vars( $qvars ){
	//Add these query variables
	$qvars[] = 'venue';
	$qvars[] = 'venue_id';
	$qvars[] = 'ondate';
	$qvars[] = 'showrepeats';
	$qvars[] = 'eo_interval';

	return $qvars;
}


/** 
* Sets post type to 'event's if required.
* If the query is for 'venue' then we want to return events (at that venue)
* set the post_type accordingly.
*
* TODO add similiar for dates
*
 * @since 1.0.0
 */
add_action( 'pre_get_posts', 'eventorganiser_pre_get_posts' );
function eventorganiser_pre_get_posts( $query ) {
	global $wp_query; 

	//If venue or event-category is being queried, we must be after events
	if( isset( $query->query_vars['venue'] ) || isset( $query->query_vars['venue_id'] )  || isset($query->query_vars['event-category'] ) || isset($query->query_vars['event-tag'] )) {
		$query->set('post_type', 'event');
	}

	//If Global venue is set, set global $EO_Venue
	if( isset( $wp_query->query_vars['venue'])) {
		global $EO_Venue;
		$EO_Venue = new EO_Venue($wp_query->query_vars['venue']);
	}

	//If querying for all events starting on given date, set the date parameters
	if( !empty( $wp_query->query_vars['ondate'])) {
		$wp_query->set('post_type', 'event');
		$wp_query->set('event_start_before', $wp_query->query_vars['ondate']);
		$wp_query->set('event_start_after', $wp_query->query_vars['ondate']);
	}

	//Determine whether or not to show past events and each occurrence
	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']){
		//If not set, use options
		if(!is_admin() && !is_single() &&!isset($query->query_vars['showpastevents'])){
			$eo_settings_array= get_option('eventorganiser_options');
			$query->set('showpastevents',$eo_settings_array['showpast']);
		}
		if(!isset($query->query_vars['showrepeats'])){
			if(is_admin() || is_single())
				$query->set('showrepeats',0);
			else
				$query->set('showrepeats',1);
		}
	}

	 return $query;	
}



/**
 * SELECT all fields from events and venue table for events
 *
 * @since 1.0.0
 */
add_filter('posts_fields', 'eventorganiser_event_fields',10,2);
function eventorganiser_event_fields( $selec, $query ){
	global $wpdb, $eventorganiser_events_table, $eventorganiser_venue_table;

	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']) {
		$selec = "{$eventorganiser_events_table}.*,".$selec; 
	}
	return $selec;
}

/**
* GROUP BY Event (occurrence) ID
* Event posts do not want to be grouped by post, but by occurrence
*
 * @since 1.0.0
 */
add_filter('posts_groupby', 'eventorganiser_event_groupby',10,2);
function eventorganiser_event_groupby( $groupby, $query ){
	global $eventorganiser_events_table;

	//if(!empty($query->query_vars['group_events_by']) && $query->query_vars['group_events_by'] == 'series')
		//return "{$eventorganiser_events_table}.post_id";

	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']):
		if(empty($groupby))
			return $groupby;

		return "{$eventorganiser_events_table}.event_id";
	endif;

	return $groupby;
}



/**
* LEFT JOIN all EVENTS. 
* Joins events table when querying for events
*
 * @since 1.0.0
 */
add_filter('posts_join', 'eventorganiser_join_tables',10,2);
function eventorganiser_join_tables( $join, $query ){
	global $wpdb, $eventorganiser_events_table, $eventorganiser_venue_table;

	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']) {
			$join .=" LEFT JOIN $eventorganiser_events_table ON $wpdb->posts.id = {$eventorganiser_events_table}.post_id ";
	}
	return $join;
}


/**
* Selects posts which satisfy custom WHERE statements
* This funciton allows us to choose events within a certain date range,
* or active on a particular day or at a venue.
*
* TODO allow an array of venues to be queried
*
 * @since 1.0.0
 */
add_filter('posts_where','eventorganiser_events_where',10,2);
function eventorganiser_events_where( $where, $query ){
	global $wpdb, $eventorganiser_events_table, $eventorganiser_venue_table;

	//Only alter event queries
	if (isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type']=='event'):

		//If in admin or single page - we probably don't want to see duplicates of (recurrent) events - unless specified otherwise.
		if((is_admin() || is_single())&&(!$query->query_vars['showrepeats'])):

			//Select the first event.
			$where .= " AND ({$eventorganiser_events_table}.event_occurrence =0 OR {$eventorganiser_events_table}.event_occurrence IS NULL)";

		//In other instances (archives, shortcode listing if showrepeats option is false display only the next event.
		elseif(!$query->query_vars['showrepeats']):
			$where .= " AND ({$eventorganiser_events_table}.event_occurrence =0 OR {$eventorganiser_events_table}.event_occurrence IS NULL)";

		endif;

		//If we only want events (or occurrences of events) that belong to a particular 'event'
		if(isset($query->query_vars['event_series'])):
			$series_id =$query->query_vars['event_series'];
			$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.post_id =%d ",$series_id);
		endif;


		//Retrieve blog's time and date
		$blog_now = new DateTIme(null,EO_Event::get_timezone());
		$now_date =$blog_now->format('Y-m-d');
		$now_time =$blog_now->format('H:i:s');

		$eo_settings_array= get_option('eventorganiser_options'); 
		$running_event_is_past= (empty($eo_settings_array['runningisnotpast']) ? true : false);


		//Query by interval
		if(isset($query->query_vars['eo_interval'])):

			switch($query->query_vars['eo_interval']):
				case 'future':
					$query->set('showpastevents',0);
					$running_event_is_past=true;
					break;
				case 'expired':
					$now_date =$blog_now->format('Y-m-d');
					$now_time =$blog_now->format('H:i:s');

					$where .= $wpdb->prepare(" 
						AND {$eventorganiser_events_table}.post_id NOT IN (
							SELECT post_id FROM {$eventorganiser_events_table} 
							WHERE ({$eventorganiser_events_table}.EndDate > %s)
							OR ({$eventorganiser_events_table}.EndDate=%s AND {$eventorganiser_events_table}.FinishTime >= %s)
						)",$now_date,$now_date,$now_time );
					break;
				case 'P1D':
				case 'P1W':
				case 'P1M':
				case 'P6M':
				case 'P1Y':
					if(!isset($cutoff)):
						$interval = new DateInterval($query->query_vars['eo_interval']);
						$cutoff = clone $blog_now;
						$cutoff->add($interval);
					endif;

					if(empty($query->query_vars['showrepeats'])):
						$where .= $wpdb->prepare(" 
							AND {$eventorganiser_events_table}.post_id IN (
								SELECT post_id FROM {$eventorganiser_events_table} 
								WHERE {$eventorganiser_events_table}.StartDate <= %s
								AND {$eventorganiser_events_table}.EndDate >= %s)",
							$cutoff->format('Y-m-d'),$blog_now->format('Y-m-d'));
					else:
						$where .= $wpdb->prepare(" 
							AND {$eventorganiser_events_table}.StartDate <=%s'
							AND {$eventorganiser_events_table}.EndDate >= %s",
							$cutoff->format('Y-m-d'),$blog_now->format('Y-m-d')
						);
					endif;
					break;
			endswitch;
		endif;


		/*
		* If requested, retrieve only future events. 
		* Single pages behave differently - WordPress sees them as displaying the first event
		* There could be options in the future to change this behaviour.
		* Currently we show single pages, even if they don't appear in archive listings.
		* There could be options in the future to change this behaviour too.
		*
		* 'Future events' only works if we are showing all reoccurrences, and not wanting just the first occurrence of an event.
		*/

		if(isset($query->query_vars['showpastevents'])&& !$query->query_vars['showpastevents'] ){

			//If quering for all occurrences, look at start/end date 
			if(!empty($query->query_vars['showrepeats'])):
				$query_date = $eventorganiser_events_table.'.'.($running_event_is_past ? 'StartDate' : 'EndDate');
				$query_time = $eventorganiser_events_table.'.'.($running_event_is_past ? 'StartTime' : 'FinishTime');	
			
				$where .= $wpdb->prepare(" AND ( 
					({$query_date} > %s) OR
					({$query_date} = %s AND {$query_time}>= %s))"
					,$now_date,$now_date,$now_time);

			//If querying for an 'event schedule': event is past if it all of its occurrences are 'past'.
			else:	
				if($running_event_is_past):
					//Check if each occurrence has started, i.e. just check reoccurrence_end
					$query_date = $eventorganiser_events_table.'.reoccurrence_end';
					$query_time = $eventorganiser_events_table.'.StartTime';

					$where .= $wpdb->prepare(" AND ( 
						({$query_date} > %s) OR
						({$query_date} = %s AND {$query_time}>= %s))"
						,$now_date,$now_date,$now_time);

				else:
					//Check each occurrence has finished, need to do a sub-query.
					$where .= $wpdb->prepare(" 
						AND {$eventorganiser_events_table}.post_id IN (
							SELECT post_id FROM {$eventorganiser_events_table} 
							WHERE ({$eventorganiser_events_table}.EndDate > %s)
							OR ({$eventorganiser_events_table}.EndDate=%s AND {$eventorganiser_events_table}.FinishTime >= %s)
							)",$now_date,$now_date,$now_time );
				endif;
			endif;
		}

		//If venue is specified, restrict events to that venue 
		if( isset( $query->query_vars['venue'] ) && $query->query_vars['venue']!='') {
			$venue = $query->query_vars['venue'];
			if(is_int($venue)){
				$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.Venue =%d ",intval($venue));
			}else{
				$venue = new EO_Venue($query->query_vars['venue']);
				if($venue->is_found()){
					$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.Venue =%d ",$venue->id);
				}else{
					$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.Venue =NULL");
				}
			}
		}elseif( !empty( $query->query_vars['venue_id'] )) {
			$venue = $query->query_vars['venue_id'];
			$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.Venue =%d ",$venue);
		}



		//Check date ranges were are interested in
		if( isset( $query->query_vars['event_start_before'] ) && $query->query_vars['event_start_before']!='') {
			$s_before = $query->query_vars['event_start_before'];
			$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.StartDate <= %s ",$s_before);
		}
		if( isset( $query->query_vars['event_start_after'] ) && $query->query_vars['event_start_after']!='') {
			$s_after = $query->query_vars['event_start_after'];
			$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.StartDate >= %s ",$s_after);
		}
		if( isset( $query->query_vars['event_end_before'] ) && $query->query_vars['event_end_before']!='') {
			$e_before = $query->query_vars['event_end_before'];
			$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.EndDate <= %s ",$e_before);
		}
		if( isset( $query->query_vars['event_end_after'] ) && $query->query_vars['event_end_after']!='') {
			$e_after = $query->query_vars['event_end_after'];
			$where .= $wpdb->prepare(" AND {$eventorganiser_events_table}.EndDate >= %s ",$e_after);
		}
	endif;

	return $where;
}

/**
* Alter the order of posts. 
* This function allows to sort our events by a custom order (venue, date etc)
*
 * @since 1.0.0
 */
add_filter('posts_orderby','sort_custom',10,2);
function sort_custom( $orderby, $query ){
	global $wpdb, $eventorganiser_events_table;

	//If the query sets an orderby return what to do if it is one of our custom orderbys
	if( !empty($query->query_vars['orderby'])):

		$order_crit= $query->query_vars['orderby'];
		$order_dir = $query->query_vars['order'];
		
		switch ($order_crit):
		case 'eventstart':
			return  " {$eventorganiser_events_table}.StartDate $order_dir, {$eventorganiser_events_table}.StartTime $order_dir";
			break;

		case 'eventend':
			return  " {$eventorganiser_events_table}.EndDate $order_dir, {$eventorganiser_events_table}.FinishTime $order_dir";
			break;

		default:
			return $orderby;
		endswitch;		

	//If no orderby is set, but we are querying events, return the default order for events;
	elseif(isset($query->query_vars['post_type']) && $query->query_vars['post_type']=='event'):
			$orderby = " {$eventorganiser_events_table}.StartDate ASC, {$eventorganiser_events_table}.StartTime ASC";

	endif;
	 //End if variables set

	return $orderby;
}


//These functions are useful for determining if venue or date is being queried
function is_venue(){
	global $wp_query;
	if( isset( $wp_query->query_vars['venue']) || isset( $wp_query->query_vars['venue_slug'])) {
		return true;
	}
	return false;
}

function is_ondate(){
	global $wp_query;
	if( isset( $wp_query->query_vars['ondate'] )) {
		return true;
	}
	return false;
}
?>
