<?php
/**
 * Handles the query manipulation of events
 */

/**
 * Registers our custom query variables
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
 * Parse's the query, and sets date range and other event specific query variables.
 * If query is for 'event' post type - the posts_* filters are added.
 * @since 1.0.0
 */
add_action( 'pre_get_posts', 'eventorganiser_pre_get_posts' );
function eventorganiser_pre_get_posts( $query ) {

	//Deprecated, use event-venue instead.
	if( !empty($query->query_vars['venue']) ){
		$venue = $query->get('venue');
		$query->set('event-venue',$venue);
       }

	//If event-venue, event-tag or event-category is being queried, we must be after events
	if(isset($query->query_vars['event-venue'])  || isset($query->query_vars['event-category'] ) || isset($query->query_vars['event-tag']) || $query->is_feed('eo-events') ){
		$query->set('post_type', 'event');
	}

	//If querying for all events starting on given date, set the date parameters
	if( !empty($query->query_vars['ondate']) ) {
		$query->set('post_type', 'event');
		$query->set('event_start_before', $query->query_vars['ondate']);
		$query->set('event_end_after', $query->query_vars['ondate']);
	}

	//If not on event, stop here.
	if('event'!= $query->get('post_type') )
		return $query;

	$eo_settings_array= get_option('eventorganiser_options');
	$blog_now = new DateTIme(null, eo_get_blog_timezone());

	//Determine whether or not to show past events and each occurrence. //If not set, use options
	if( !is_admin() && !is_single() && !$query->is_feed('eo-events') && !isset($query->query_vars['showpastevents']) ){
		//If showpastevents is not set - use options (except for admin / single pages.
		$query->set('showpastevents',$eo_settings_array['showpast']);
	}

	//Deprecated: showrepeats - use group_events_by instead
	if( isset($query->query_vars['showrepeats']) && !isset($query->query_vars['group_events_by']) ){
		if( !$query->query_vars['showrepeats'] )
			$query->set('group_events_by','series');
	}

	//Determine how to group events: by series or show each occurrence
	if( !isset($query->query_vars['group_events_by']) ){

		//Group by isn't set - default depends on context:
		if( is_admin() || is_single() || $query->is_feed('eo-events') ){

			//If in admin or single page - we probably don't want to see duplicates of (recurrent) events - unless specified otherwise.
			$query->set('group_events_by','series');

		}elseif(!empty($eo_settings_array['group_events']) && $eo_settings_array['group_events']=='series'){
			//In other instances (archives, shortcode listing) if showrepeats option is false display only the next event.
			$query->set('group_events_by','series');
		}else{
			$query->set('group_events_by','occurrence');
		}
	}

	//Parse user input as date-time objects
	$date_objs = array('event_start_after'=>'','event_start_before'=>'','event_end_after'=>'','event_end_before'=>'');
	foreach($date_objs as $prop => $value):
		$date = $query->get($prop);
		$date_objs[$prop] = ( empty($date) ? false : new DateTime($date, eo_get_blog_timezone()) );
	endforeach;

	//If eo_interval is set, determine date ranges
	if( !empty($query->query_vars['eo_interval']) ){
		switch($query->get('eo_interval')):
			case 'expired':
				$meta_query = (array) $query->get('meta_query');
				$meta_query[] =array(
					'key' => '_eventorganiser_schedule_last_finish',
					'value' => $blog_now->format('Y-m-d H:i:s'),
					'compare' => '<='
				);
				$query->set('meta_query',$meta_query) ;
				break;

			case 'future':
				$meta_query = $query->get('meta_query');
				$meta_query = empty($meta_query) ? array() : $meta_query;
				$meta_query[] =array(
					'key' => '_eventorganiser_schedule_last_start',
					'value' => $blog_now->format('Y-m-d H:i:s'),
					'compare' => '>='
				);
				$query->set('meta_query',$meta_query) ;
				break;

			case 'P1D':
			case 'P1W':
			case 'P1M':
			case 'P6M':
			case 'P1Y':
				//I hate you php5.2
				$intervals = array('P1D'=>'+1 day', 'P1W'=>'+1 week','P1M'=>'+1 month','P6M'=>'+6 month','P1Y'=>'+1 Year');
				$cutoff = clone $blog_now;
				$cutoff->modify($intervals[$query->query_vars['eo_interval']]);

				if( is_admin() && 'series' == $query->get('group_events_by') ){
					//On admin we want to show the **first** occurrence of a recurring event which has an occurrence in the interval
					global $wpdb;
					$post_ids = $wpdb->get_results($wpdb->prepare(
						"SELECT DISTINCT post_id FROM {$wpdb->eo_events}
						WHERE {$wpdb->eo_events}.StartDate <= %s
						AND {$wpdb->eo_events}.EndDate >= %s",
						$cutoff->format('Y-m-d'),$blog_now->format('Y-m-d')));

					if($post_ids)
						$query->set('post__in',wp_list_pluck($post_ids, 'post_id'));

				}else{
					if( empty($date_objs['event_start_before']) || $cutoff < $date_objs['event_start_before'] ){
						$date_objs['event_start_before'] = $cutoff;
					}
					if( empty($date_objs['event_end_after']) || $blog_now > $date_objs['event_end_after'] ){
						$date_objs['event_end_after'] = $blog_now;
					}
				}
		endswitch;
	}//Endif interval set

	$running_event_is_past= (empty($eo_settings_array['runningisnotpast']) ? true : false);

	//Set date range according to whether we show past events
	if(isset($query->query_vars['showpastevents'])&& !$query->query_vars['showpastevents'] ){
		//Showing only future events

		//Running event is past - Get events which start in the future
		//A current event is not past - Get events which finish in the future
		$key = ( $running_event_is_past ? 'event_start_after' : 'event_end_after');

		//If current queried date is not set or before now, set the queried date to now
		$date_objs[$key]  = (empty($date_objs[$key]) || $blog_now > $date_objs[$key]) ? $blog_now : $date_objs[$key];
	}


	//Set event dates to 'Y-m-d H:i:s' format.
	foreach ($date_objs as $prop => $datetime ){
		if( !empty($datetime) )
			$query->set($prop, $datetime->format('Y-m-d H:i:s'));
	}

	if( $query->is_feed('eo-events') ){
		//Posts per page for feeds bug http://core.trac.wordpress.org/ticket/17853
		add_filter('post_limits','wp17853_eventorganiser_workaround');
		$query->set('posts_per_page',-1);
	}

	//Add the posts_* filters to modify the query
	add_filter('posts_fields', 'eventorganiser_event_fields',10,2);
	add_filter('posts_join', 'eventorganiser_join_tables',10,2);
	add_filter('posts_where','eventorganiser_events_where',10,2);
	add_filter('posts_orderby','eventorganiser_sort_events',10,2);
	add_filter('posts_groupby', 'eventorganiser_event_groupby',10,2);
}


/**
 * A work around for a bug that posts_per_page is over-ridden by the posts_per_rss option. nopaging is also set to 'false'.
 * For ics feeds nopaging should be true and post_per_page should be -1. We intercept the LIMIT part of the query and remove it.
 * We return '' so there is no LIMIT part to the query.
 *@see http://core.trac.wordpress.org/ticket/17853
 */
function wp17853_eventorganiser_workaround( $limit ){
	remove_filter(current_filter(),__FUNCTION__);
	return '';
}

/**
 * SELECT only date fields from events and venue table for events
 * All other fields deprecated and stored in post meta since 1.5
 *
 * @since 1.0.0
 */
function eventorganiser_event_fields( $select, $query ){
	global $wpdb;

	if('event'== $query->get('post_type') ) {
		$et =$wpdb->eo_events;
		/* Include 'event_occurrence' for backwards compatibility. Will eventually be removed. */
		/* Renaming event_id as occurrence id. Keep event_id for backwards compatibility */
		if( 'series'== $query->get('group_events_by') ) {
			//Work-around for group_events_by series.
			$select = "{$et}.event_id, {$et}.event_id AS occurrence_id, {$et}.StartTime, min({$et}.StartDate) as StartDate, min({$et}.EndDate) as EndDate, {$et}.FinishTime, {$et}.event_occurrence, ".$select;
		}else{
			$select = "{$et}.event_id, {$et}.event_id AS occurrence_id, {$et}.StartDate, {$et}.StartTime, {$et}.EndDate, {$et}.FinishTime, {$et}.event_occurrence, ".$select;
		}
	}
	return $select;
}

/**
* GROUP BY Event (occurrence) ID
* Event posts do not want to be grouped by post, but by occurrence - unless otherwise specified.
*
 * @since 1.0.0
 */
function eventorganiser_event_groupby( $groupby, $query ){
	global $wpdb;

	if(!empty($query->query_vars['group_events_by']) && $query->query_vars['group_events_by'] == 'series'){
		return "{$wpdb->eo_events}.post_id";
	}

	if('event'== $query->get('post_type') ) :
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
function eventorganiser_join_tables( $join, $query ){
	global $wpdb;

	if('event'== $query->get('post_type') ) {
			$join .=" LEFT JOIN $wpdb->eo_events ON $wpdb->posts.id = {$wpdb->eo_events}.post_id ";
	}
	return $join;
}


/**
 * Selects posts which satisfy custom WHERE statements
 *
 * @since 1.0.0
 */
function eventorganiser_events_where( $where, $query ){
	global $wpdb;

	//Only alter event queries
	if('event'== $query->get('post_type') ):

		//If we only want events (or occurrences of events) that belong to a particular 'event'
		if(isset($query->query_vars['event_series'])):
			$series_id =$query->query_vars['event_series'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.post_id =%d ",$series_id);
		endif;

		if(isset($query->query_vars['event_occurrence_id'])):
			$occurrence_id =$query->query_vars['event_occurrence_id'];
			$where .= $wpdb->prepare(" AND {$wpdb->eo_events}.event_id=%d ",$occurrence_id);
		endif;

		//Check date ranges were are interested in. 
		$date_queries = array(
			'event_start_after'=>array(
				'notstrict' =>" AND {$wpdb->eo_events}.StartDate >= %s ",
				'strict' => " AND ({$wpdb->eo_events}.StartDate > %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime > %s)) "
			),
			'event_start_before'=>array(
				'notstrict' =>" AND {$wpdb->eo_events}.StartDate <= %s ",
				'strict' => " AND ({$wpdb->eo_events}.StartDate < %s OR ({$wpdb->eo_events}.StartDate = %s AND {$wpdb->eo_events}.StartTime < %s)) "
			),
			'event_end_after'=>array(
				'notstrict' =>" AND {$wpdb->eo_events}.EndDate >= %s ",
				'strict' => " AND ({$wpdb->eo_events}.EndDate > %s OR ({$wpdb->eo_events}.EndDate = %s AND {$wpdb->eo_events}.FinishTime > %s)) "
			),
			'event_end_before'=>array(
				'notstrict' =>" AND {$wpdb->eo_events}.EndDate <= %s ",
				'strict' => " AND ({$wpdb->eo_events}.EndDate < %s OR ({$wpdb->eo_events}.EndDate = %s AND {$wpdb->eo_events}.FinishTime < %s)) "
			)
		);

		//Construct sql query.
		foreach ( $date_queries as $prop => $_sql ){
			$datetime = $query->get($prop);
			if( !empty( $datetime) ) {
				$date = eo_format_date($datetime,'Y-m-d');
				$time = eo_format_date($datetime,'H:i:s');
				if( $time == '00:00:00' ){
					$sql = $_sql['notstrict'];
					$where .= $wpdb->prepare($sql, $date);				
				}else{
					$sql = $_sql['strict'];
					$where .= $wpdb->prepare($sql, $date, $date, $time);				
				}
			}
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
function eventorganiser_sort_events( $orderby, $query ){
	global $wpdb;

	if( !empty($query->query_vars['orderby']) ){
		//If the query sets an orderby return what to do if it is one of our custom orderbys

		$order_crit= $query->query_vars['orderby'];
		$order_dir = $query->query_vars['order'];

		switch($order_crit):
			case 'eventstart':
				return  " {$wpdb->eo_events}.StartDate $order_dir, {$wpdb->eo_events}.StartTime $order_dir";
				break;

			case 'eventend':
				return  " {$wpdb->eo_events}.EndDate $order_dir, {$wpdb->eo_events}.FinishTime $order_dir";
				break;

			default:
				return $orderby;
		endswitch;

	}elseif('event'== $query->get('post_type') ) {
			//If no orderby is set, but we are querying events, return the default order for events;
			$orderby = " {$wpdb->eo_events}.StartDate ASC, {$wpdb->eo_events}.StartTime ASC";
	}
	return $orderby;
}



//These functions are useful for determining if venue or date is being queried
function eo_is_venue(){
	global $wp_query;
	return (isset($wp_query->query_vars['venue'] ) || is_tax('event-venue'));
}
?>
