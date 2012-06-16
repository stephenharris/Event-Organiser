<?php 
/**
 * Handles the query manipulation of events
 */

/**
 * Registers our custom query variables
 *
 * @since 1.0.0
 */
add_filter('query_vars', 'eventorganiser_register_query_vars' );
function eventorganiser_register_query_vars( $qvars ){
	//Add these query variables
	$qvars[] = 'venue';//Depreciated
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
 * @since 1.0.0
 */
add_action( 'pre_get_posts', 'eventorganiser_pre_get_posts' );
function eventorganiser_pre_get_posts( $query ) {
	global $wp_query; 

	//Deprecited, use event-venue instead.
	if(isset($query->query_vars['venue'] )){
		$venue = esc_attr($query->query_vars['venue']);
		$query->set('event-venue',$venue);
       }

	//If venue or event-category is being queried, we must be after events
	if(isset($query->query_vars['event-venue'])  || isset($query->query_vars['event-category'] ) || isset($query->query_vars['event-tag'] )) {
		$query->set('post_type', 'event');
	}

	//If querying for all events starting on given date, set the date parameters
	if( !empty( $query->query_vars['ondate'])) {
		$query->set('post_type', 'event');
		$query->set('event_start_before', $query->query_vars['ondate']);
		$query->set('event_end_after', $query->query_vars['ondate']);
	}

	//Determine whether or not to show past events and each occurrence
	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']){
		//If not set, use options
		$eo_settings_array= get_option('eventorganiser_options');

		if(!is_admin() && !is_single() &&!isset($query->query_vars['showpastevents'])){
			//Whether or not to include past events.
			$query->set('showpastevents',$eo_settings_array['showpast']);
		}


		//Depreciated; showrepeats - use group_events_by instead
		if( isset($query->query_vars['showrepeats']) && !isset($query->query_vars['group_events_by']) ){
			if( !$query->query_vars['showrepeats'] )
				$query->set('group_events_by','series');
		}
		
		if( !isset($query->query_vars['group_events_by']) ){
			if( is_admin() || is_single() ){
				//If in admin or single page - we probably don't want to see duplicates of (recurrent) events - unless specified otherwise.
				$query->set('group_events_by','series');
			}elseif(!empty($eo_settings_array['group_events']) && $eo_settings_array['group_events']=='series'){
				//In other instances (archives, shortcode listing if showrepeats option is false display only the next event.
				$query->set('group_events_by','series');
			}else{
				$query->set('group_events_by','occurrence');
			}
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
	global $wpdb;

	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']) {
		$selec = "{$wpdb->eo_events}.*,".$selec; 
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
	global $wpdb;

	if(!empty($query->query_vars['group_events_by']) && $query->query_vars['group_events_by'] == 'series')
		return "{$wpdb->eo_events}.post_id";

	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']):
		if(empty($groupby))
			return $groupby;

		return "{$wpdb->eo_events}.event_id";
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
	global $wpdb;

	if( isset( $query->query_vars['post_type'] ) && 'event'== $query->query_vars['post_type']) {
			$join .=" LEFT JOIN $wpdb->eo_events ON $wpdb->posts.id = {$wpdb->eo_events}.post_id ";
	}
	return $join;
}


/**
* Selects posts which satisfy custom WHERE statements
* This funciton allows us to choose events within a certain date range,
* or active on a particular day or at a venue.
*
 * @since 1.0.0
 */
add_filter('posts_where','eventorganiser_events_where',10,2);
function eventorganiser_events_where( $where, $query ){
	global $wpdb;

	//Only alter event queries
	if (isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type']=='event'):


		//Ensure all date queries are yyyy-mm-dd format. Process relative strings ('today','tomorrow','+1 week')
		$dates = array('ondate','event_start_after','event_start_before','event_end_after','event_end_before');
		foreach($dates as $prop):
			if(!empty($query->query_vars[$prop])):
				$date = $query->query_vars[$prop];
				$dateString = eo_format_date($date,'Y-m-d');
				$query->set($prop,$dateString);
			endif;
		endforeach;



		//If we only want events (or occurrences of events) that belong to a particular 'event'
		if(isset($query->query_vars['event_series'])):
			$series_id =$query->query_vars['event_series'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.post_id =%d ",$series_id);
		endif;

		if(isset($query->query_vars['event_occurrence_id'])):
			$occurrence_id =$query->query_vars['event_occurrence_id'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.event_id=%d ",$occurrence_id);
		endif;


		//Retrieve blog's time and date
		$blog_now = new DateTIme(null, eo_get_blog_timezone());
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
						AND {$wpdb->eo_events}.post_id NOT IN (
							SELECT post_id FROM {$wpdb->eo_events} 
							WHERE ({$wpdb->eo_events}.EndDate > %s)
							OR ({$wpdb->eo_events}.EndDate=%s AND {$wpdb->eo_events}.FinishTime >= %s)
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
							AND {$wpdb->eo_events}.post_id IN (
								SELECT post_id FROM {$wpdb->eo_events} 
								WHERE {$wpdb->eo_events}.StartDate <= %s
								AND {$wpdb->eo_events}.EndDate >= %s)",
							$cutoff->format('Y-m-d'),$blog_now->format('Y-m-d'));
					else:
						$where .= $wpdb->prepare(" 
							AND {$wpdb->eo_events}.StartDate <=%s'
							AND {$wpdb->eo_events}.EndDate >= %s",
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
			if( !$query->get('group_events_by') || $query->get('group_events_by')=='occurrence' ):
				$query_date = $wpdb->eo_events.'.'.($running_event_is_past ? 'StartDate' : 'EndDate');
				$query_time = $wpdb->eo_events.'.'.($running_event_is_past ? 'StartTime' : 'FinishTime');	
			
				$where .= $wpdb->prepare(" AND ( 
					({$query_date} > %s) OR
					({$query_date} = %s AND {$query_time}>= %s))"
					,$now_date,$now_date,$now_time);

			//If querying for an 'event schedule': event is past if it all of its occurrences are 'past'.
			else:	
				if($running_event_is_past):

					//Check if each occurrence has started, i.e. just check reoccurrence_end
					$query_date = $wpdb->eo_events.'.reoccurrence_end';
					$query_time = $wpdb->eo_events.'.StartTime';

					$where .= $wpdb->prepare(" AND ( 
						({$query_date} > %s) OR
						({$query_date} = %s AND {$query_time}>= %s))"
						,$now_date,$now_date,$now_time);

				else:
					//Check each occurrence has finished, need to do a sub-query.
					$where .= $wpdb->prepare(" 
						AND {$wpdb->eo_events}.post_id IN (
							SELECT post_id FROM {$wpdb->eo_events} 
							WHERE ({$wpdb->eo_events}.EndDate > %s)
							OR ({$wpdb->eo_events}.EndDate=%s AND {$wpdb->eo_events}.FinishTime >= %s)
							)",$now_date,$now_date,$now_time );
				endif;
			endif;
		}

		//Check date ranges were are interested in
		if( isset( $query->query_vars['event_start_before'] ) && $query->query_vars['event_start_before']!='') {
			$s_before = $query->query_vars['event_start_before'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.StartDate <= %s ",$s_before);
		}
		if( isset( $query->query_vars['event_start_after'] ) && $query->query_vars['event_start_after']!='') {
			$s_after = $query->query_vars['event_start_after'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.StartDate >= %s ",$s_after);
		}
		if( isset( $query->query_vars['event_end_before'] ) && $query->query_vars['event_end_before']!='') {
			$e_before = $query->query_vars['event_end_before'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.EndDate <= %s ",$e_before);
		}
		if( isset( $query->query_vars['event_end_after'] ) && $query->query_vars['event_end_after']!='') {
			$e_after = $query->query_vars['event_end_after'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.EndDate >= %s ",$e_after);
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
add_filter('posts_orderby','eventorganiser_sort_events',10,2);
function eventorganiser_sort_events( $orderby, $query ){
	global $wpdb;

	//If the query sets an orderby return what to do if it is one of our custom orderbys
	if( !empty($query->query_vars['orderby'])):

		$order_crit= $query->query_vars['orderby'];
		$order_dir = $query->query_vars['order'];
		
		switch ($order_crit):
		case 'eventstart':
			return  " {$wpdb->eo_events}.StartDate $order_dir, {$wpdb->eo_events}.StartTime $order_dir";
			break;

		case 'eventend':
			return  " {$wpdb->eo_events}.EndDate $order_dir, {$wpdb->eo_events}.FinishTime $order_dir";
			break;

		default:
			return $orderby;
		endswitch;		

	//If no orderby is set, but we are querying events, return the default order for events;
	elseif(isset($query->query_vars['post_type']) && $query->query_vars['post_type']=='event'):
			$orderby = " {$wpdb->eo_events}.StartDate ASC, {$wpdb->eo_events}.StartTime ASC";

	endif;
	 //End if variables set

	return $orderby;
}


//These functions are useful for determining if venue or date is being queried
function eo_is_venue(){
	global $wp_query;
	return (isset($wp_query->query_vars['venue'] ) || is_tax('event-venue'));
}
?>
