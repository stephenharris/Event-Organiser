<?php

/**
 * This file contains deprecated functions and provides the alternative function you should use 
 * Functions with _deprecated_function inside should not be used as these could be removed at any time.
 */



/* Returns an array of DateTime objects for each start date of occurrence
*
* @param id - Optional, the event (post) ID, 
* @return array|false - Array of DateTime objects of the start date-times of occurences. False if none exist.
 * @since 1.0.0
 * @deprecated use eo_get_the_occurrences_of() instead
 */
function eo_get_the_occurrences($post_id=''){
	_deprecated_function( __FUNCTION__, '1.5', 'eo_get_the_occurrences_of()' );
	$occurrences = eo_get_the_occurrences_of($post_id);
	return wp_list_pluck($occurrences, 'start');
}

/**
* Return true is the event is an all day event.
*
* @param id - Optional, the event series (post) ID, 
* @return bol - True if event runs all day, or false otherwise
 * @since 1.2
Depreciated in favour of eo_is_all_day().
 */
function eo_is_allday($id=''){
	_deprecated_function( __FUNCTION__, '1.5', 'eo_is_all_day()' );
	return eo_is_all_day($id);
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
 * @deprecated use eo_get_schedule_last
 */
function eo_get_schedule_end($format='d-m-Y',$post_id=''){
	return eo_get_schedule_last($format,$post_id);
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
 * @deprecated use eo_schedule_last
 */
function  eo_schedule_end($format='d-m-Y',$post_id=''){
	echo eo_get_schedule_last($format,$post_id);
}


/**
* Returns an array with details of the event's reoccurences
*
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 * @deprecated use eo_get_event_schedule();
 */
function eo_get_reoccurrence($post_id=''){
	return eo_get_reoccurence($post_id);
}


/**
* Returns an array with details of the event's reoccurences
*
* @param id - Optional, the event (post) ID, 
 * @since 1.0.0
 * @deprecated use eo_get_event_schedule();
 */
function eo_get_reoccurence($post_id=''){
	_deprecated_function( __FUNCTION__, '1.5', 'eo_get_event_schedule()' );
	$post_id = (int) ( empty($post_id) ? get_the_ID() : $post_id);

	if( empty($post_id) || 'event' != get_post_type($post_id) ) 
		return false;
		
	$return = eo_get_event_schedule( $post_id );	

	if ( !$return )
		return false;

	$return['reoccurrence'] =$return['schedule'];
	$return['meta'] =	$return['schedule_meta'];
	$return['end'] = $return['schedule_last']; 
	return $return; 
}


