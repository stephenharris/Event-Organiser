<?php
/**
 *@package deprecated
 */

/**
 * Returns a the url which adds a particular occurrence of an event to
 * a google calendar. Must be used inside the loop.
 *
 * @since 1.2.0
 * @deprecated 2.3.0
 * @see eo_get_add_to_google_link()
 *
 * @param int $post_id Optional, the event (post) ID,
 * @return string Url which adds event to a google calendar
 */
function eo_get_the_GoogleLink(){
	_deprecated_function( __FUNCTION__, '2.3', 'eo_get_add_to_google_link()' );
	return eo_get_add_to_google_link();
}

/** 
* Returns an array of DateTime objects for each start date of occurrence
* @since 1.0.0
* @deprecated 1.5
* @see eo_get_the_occurrences_of()
*
* @param int $post_id Optional, the event (post) ID, 
* @return array|false Array of DateTime objects of the start date-times of occurences. False if none exist.
 */
function eo_get_the_occurrences($post_id=0){
	//_deprecated_function( __FUNCTION__, '1.5', 'eo_get_the_occurrences_of()' );
	$occurrences = eo_get_the_occurrences_of($post_id);
	if( $occurrences )
		return wp_list_pluck($occurrences, 'start');
	return false;
}

/**
* Return true is the event is an all day event.
* @since 1.2
* @deprecated 1.5
* @see eo_is_all_day()
*
* @param int $post_id Optional, the event series (post) ID, 
* @return bool True if event runs all day, or false otherwise
 */
function eo_is_allday($post_id=0){
	_deprecated_function( __FUNCTION__, '1.5', 'eo_is_all_day()' );
	return eo_is_all_day($post_id);
}

/**
* Returns the formated date of the last occurrence of an event
* @since 1.0.0
* @deprecated 1.5 use eo_get_schedule_last
* @see eo_get_schedule_last
*
* @param string $format the format to use, using PHP Date format
* @param int $post_id Optional, the event (post) ID, 
* @return string the formatted date 
 */
function eo_get_schedule_end($format='d-m-Y',$post_id=0){
	return eo_get_schedule_last($format,$post_id);
}

/**
* Prints the formated date of the last occurrence of an event
* @since 1.0.0
* @deprecated 1.5 use eo_schedule_last
* @see eo_schedule_last
*
* @param string $format the format to use, using PHP Date format
* @param int $post_id Optional, the event (post) ID, 
 */
function  eo_schedule_end($format='d-m-Y',$post_id=0){
	echo eo_get_schedule_last($format,$post_id);
}


/**
* Returns an array with details of the event's reoccurences
* @since 1.0.0
* @deprecated 1.6
* @see eo_get_event_schedule()
*
* @param int $post_id Optional, the event (post) ID, 
* @return array Schedule information
*/
function eo_get_reoccurrence($post_id=0){
	return eo_get_reoccurence($post_id);
}


/**
* Returns an array with details of the event's reoccurences. 
* Note this is is identical to eo_get_reoccurrence() which corrects a spelling error.
*
* @param int Optional, the event (post) ID, 
 * @since 1.0.0
 * @deprecated 1.6
 * @see eo_get_event_schedule()
*
* @param int $post_id Optional, the event (post) ID, 
* @return array Schedule information
 */
function eo_get_reoccurence($post_id=0){
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


/**
* Returns the colour of a category associated with the event
* @since 1.3.3
* @deprecated 1.6
* @see eo_get_event_color()
*
* @param int $post_id The event (post) ID
* @return string The colour of the category in HEX format
*/
function eo_event_color($post_id=0){
	_deprecated_function( __FUNCTION__, '1.6', 'eo_get_event_color()' );
	return eo_get_event_color($post_id);
}


/**
 * Retrieve array of venues. Acts as a wrapper for get_terms, except hide_empty defaults to false.
 * @since 1.0.0
 * @deprecated 1.6
 * @see eo_get_venues()
 *
 * @param string|array $args The values of what to search for when returning venues
 * @return array List of Term (venue) Objects
 */
function eo_get_the_venues($args=array()){
	_deprecated_function( __FUNCTION__, '1.6', 'eo_get_venues()' );
	return eo_get_venues($args);
}

/**
 * Deletes the event data associated with post. Should be called when an event is being deleted.
 * This does not delete the post.
 * @since 1.0.0
 * @deprecated 1.6
 * @see eo_delete_event_occurrences()
 *
* @param int $post_id The event (post) ID
 */
function eventorganiser_event_delete($post_id){
	eo_delete_event_occurrences($post_id);
}

/**
 * Very basic class to convert php date format into jQuery UI date format used for javascript.
 * @ignore
 * @deprecated 2.1.3 Use eo_php2jquerydate
 * @since 1.7
 */
function eventorganiser_php2jquerydate( $phpformat="" ){
	//_deprecated_function( __FUNCTION__, '2.1.3', 'eo_php2jquerydate()' );
	return eo_php2jquerydate( $phpformat );
}


/**
 * Very basic class to convert php date format into xdate date format used for javascript.
 *
 * Takes a php date format and converts it to {@link http://arshaw.com/xdate/#Formatting xdate format} so
 * that it can b used in javascript (notably the fullCalendar).
 *
 * Doesn't support
 *
 * * L Whether it's a leap year
 * * N ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)
 * * w Numeric representation of the day of the week (0=sun,...)
 * * z The day of the year (starting from 0)
 * * t Number of days in the given month
 * * B Swatch Internet time
 * * u microseconds
 * * e 	Timezone identifier (added in PHP 5.1.0) 	Examples: UTC, GMT, Atlantic/Azores
 * * I (capital i) 	Whether or not the date is in daylight saving time 	1 if Daylight Saving Time, 0 otherwise.
 * * O  Difference to Greenwich time (GMT) in hours 	Example: +0200
 * * T  Timezone abbreviation 	Examples: EST, MDT ...
 * * Z  Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.
 * * c  ISO 8601 date (added in PHP 5) 	2004-02-12T15:19:21+00:00
 * * r  RFC 2822 formatted date 	Example: Thu, 21 Dec 2000 16:01:07 +0200
 * * U Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) 	See also time()
 *
 * @since 2.1.3
 * @deprecated 3.0.0
 * @param string $phpformat Format according to https://php.net/manual/en/function.date.php
 * @return string The format translated to xdate format: http://arshaw.com/xdate/#Formatting
 */
function eo_php2xdate($phpformat=""){
	$php2xdate = array(
			'Y'=>'yyyy','y'=>'yy','L'=>''/*Not Supported*/,'o'=>'I',
			'j'=>'d','d'=>'dd','D'=>'ddd','l'=>'dddd','N'=>'', /*NS*/ 'S'=>'S',
			'w'=>'', /*NS*/ 'z'=>'',/*NS*/ 'W'=>'w',
			'F'=>'MMMM','m'=>'MM','M'=>'MMM','n'=>'M','t'=>'',/*NS*/
			'a'=>'tt','A'=>'TT',
			'B'=>'',/*NS*/'g'=>'h','G'=>'H','h'=>'hh','H'=>'HH','u'=>'fff',
			'i'=>'mm','s'=>'ss',
			'O'=>'zz ', 'P'=>'zzz',
			'c'=>'u',
	);

	$xdateformat="";

	for($i=0;  $i< strlen($phpformat); $i++){

		//Handle backslash excape
		if($phpformat[$i]=="\\"){
			$xdateformat .= "\\".$phpformat[$i+1];
			$i++;
			continue;
		}

		if(isset($php2xdate[$phpformat[$i]])){
			$xdateformat .= $php2xdate[$phpformat[$i]];
		}else{
			$xdateformat .= $phpformat[$i];
		}
	}
	return $xdateformat;
}

/**
 * Very basic class to convert php date format into xdate date format used for javascript.
 * @deprecated 2.1.3
 * @ignore
 * @since 1.4
 */
function eventorganiser_php2xdate( $phpformat = '' ){
	return eo_php2xdate( $phpformat );
}

/**
 * @ignore
 * @deprecated 2.13.2 Use eo_taxonomy_dropdown()
 */
function eo_event_category_dropdown( $args = '' ) {
	_deprecated_function( __FUNCTION__, '2.13.2', 'eo_taxonomy_dropdown()' );
	$args['taxonomy'] = 'event-category';
	$args['class'] = 'postform event-organiser event-category-dropdown event-dropdown';
	return eo_taxonomy_dropdown( $args );
}

/**
 * @ignore
 * @access private
 * @deprecated 2.13.2 Use eo_taxonomy_dropdown()
 */
function eo_event_venue_dropdown( $args = '' ) {
	_deprecated_function( __FUNCTION__, '2.13.2', 'eo_taxonomy_dropdown()' );
	$args['taxonomy'] = 'event-venue';
	$args['class'] = 'postform event-organiser event-venue-dropdown event-dropdown';
	return eo_taxonomy_dropdown( $args );

}


/**
 * Whether the blog's time settings indicates it uses 12 or 24 hour time
 * @deprecated 2.1.3 Use {@see `eo_blog_is_24()`} instead.
 * @see eo_blog_is_24()
 */
function eventorganiser_blog_is_24() {
	return eo_blog_is_24();
}

?>