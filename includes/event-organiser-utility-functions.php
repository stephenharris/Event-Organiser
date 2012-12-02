<?php
/**
* Utility functions
*/

/**
 * Formats a datetime object into a specified format and handles translations.
 * Used by eo_get_the_start/end/schedule_start etc. 
 * The constant DATETIMEOBJ can be passed to them to get datetime objects 
 * Applies eventorganiser_format_datetime filter
 *
 * @since 1.2.0
 *
 * @param dateTime $datetime The datetime to format
 * @param string|constant $format How to format the date, see http://php.net/manual/en/function.date.php  or DATETIMEOBJ constant to return the datetime object.
 * @return string|dateTime The formatted date
*/
function eo_format_datetime($datetime,$format='d-m-Y'){
	global  $wp_locale;

	if( DATETIMEOBJ == $format )
		return $datetime;

	if ( ( !empty( $wp_locale->month ) ) && ( !empty( $wp_locale->weekday ) ) ) :
			//Translate
			$datemonth = $wp_locale->get_month($datetime->format('m'));
			$datemonth_abbrev = $wp_locale->get_month_abbrev($datemonth);
			$dateweekday = $wp_locale->get_weekday($datetime->format('w'));
			$dateweekday_abbrev = $wp_locale->get_weekday_abbrev( $dateweekday );
			$datemeridiem =  trim($wp_locale->get_meridiem($datetime->format('a')));
			$datemeridiem_capital =$wp_locale->get_meridiem($datetime->format('A'));

			$datemeridiem = (empty($datemeridiem) ? $datetime->format('a')  : $datemeridiem);
	
			$dateformatstring = ' '.$format;
			$dateformatstring = preg_replace( "/([^\\\])D/", "\\1" . backslashit( $dateweekday_abbrev ), $dateformatstring );
			$dateformatstring = preg_replace( "/([^\\\])F/", "\\1" . backslashit( $datemonth ), $dateformatstring );
			$dateformatstring = preg_replace( "/([^\\\])l/", "\\1" . backslashit( $dateweekday ), $dateformatstring );
			$dateformatstring = preg_replace( "/([^\\\])M/", "\\1" . backslashit( $datemonth_abbrev ), $dateformatstring );
			$dateformatstring = preg_replace( "/([^\\\])a/", "\\1" . backslashit( $datemeridiem ), $dateformatstring );
			$dateformatstring = preg_replace( "/([^\\\])A/", "\\1" . backslashit( $datemeridiem_capital ), $dateformatstring );
			$dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
	 endif;	

	return apply_filters('eventorganiser_format_datetime', $datetime->format($dateformatstring), $format, $datetime);
}

/**
* Formats a date string in the format 'YYYY-MM-DD H:i:s' format or even
 * relative strings like 'today' into a specified format.
 *
 * @uses eo_format_datetime
 * @since 1.2.0
 *
 * @param string $dateString The date as a string
 * @param string $format How to format the date. DATETIMEOBJ for datetime object.
 * @return string|dateTime The formatted date
*/
function eo_format_date($dateString='',$format='d-m-Y'){

	if($format!=''&& $dateString!=''){
		$datetime = new DateTime($dateString, eo_get_blog_timezone());
		$formated =  eo_format_datetime($datetime,$format);
		return $formated;
	}
	return false;
}

/**
 *  Returns the blog timezone
 *
 * Gets timezone settings from the db. If a timezone identifier is used just turns
 * it into a DateTimeZone. If an offset is usd, it tries to find a suitable timezone.
 * If all else fails it uses UTC.
 *
 * @since 1.3.0
 *
 * @return DateTimeZone The blog timezone
*/
function eo_get_blog_timezone(){
	$tzstring = wp_cache_get( 'eventorganiser_timezone' );

	if ( false === $tzstring ) {

		$tzstring =get_option('timezone_string');
		$offset = get_option('gmt_offset');

		// Remove old Etc mappings.  Fallback to gmt_offset.
		if ( !empty($tz_string) && false !== strpos($tzstring,'Etc/GMT') )
			$tzstring = '';

		if( empty($tzstring) && $offset!=0 ):
			//use offset		
			$offset *= 3600; // convert hour offset to seconds
			$allowed_zones = timezone_abbreviations_list();

			foreach ($allowed_zones as $abbr):
				foreach ($abbr as $city):
					if ($city['offset'] == $offset){
						$tzstring=$city['timezone_id'];
						break 2;
					}
				endforeach;
			endforeach;
		endif;

		//Issue with the timezone selected, set to 'UTC'
		if( empty($tzstring) ):
			$tzstring = 'UTC';
		endif;

		//Cache timezone string not timezone object
		//Thanks to Ben Huson http://wordpress.org/support/topic/plugin-event-organiser-getting-500-is-error-when-w3-total-cache-is-on
		wp_cache_set( 'eventorganiser_timezone', $tzstring );
	} 

	if( $tzstring instanceof DateTimeZone)
		return $tzstring;

	$timezone = new DateTimeZone($tzstring);
	return $timezone; 
}


/**
 * Calculates and formats an interval between two days, passed in any order.
 * It's a PHP 5.2 workaround for http://www.php.net/manual/en/dateinterval.format.php
 * @since 1.5
 *
 * @param dateTime $_date1 One date to compare
 * @param dateTime $_date2 Second date to compare
 * @param string $format Used to format the interval. See http://www.php.net/manual/en/dateinterval.format.php
 * @return string Formatted interval.
*/
function eo_date_interval($_date1,$_date2, $format){

	//Calculate R values (signs)
	$R = ($_date1 <= $_date2 ? '+' : '-');
	$r = ($_date1 <= $_date2 ? '' : '-');

	//Make sure $date1 is ealier
	$date1 = ($_date1 <= $_date2 ? $_date1 : $_date2);
	$date2 = ($_date1 <= $_date2 ? $_date2 : $_date1);

	//Calculate total days difference
	$total_days = round(abs($date1->format('U') - $date2->format('U'))/86400);

	//A leap year work around - consistent with DateInterval
	$leap_year = ( $date1->format('m-d') == '02-29' ? true : false);
	if( $leap_year ){
		//This will only effect counting the number of days - and is corrected later.
		//Otherwise incrementing $date1 by a year will overflow to March
		$date1->modify('-1 day');
	}

	$periods = array( 'years'=>-1,'months'=>-1,'days'=>-1,'hours'=>-1);

	foreach ($periods as $period => &$i ){

		if($period == 'days' && $leap_year )
			$date1->modify('+1 day');//Corrects earlier adjustment

		while( $date1 <= $date2 ){
			$date1->modify('+1 '.$period);
			$i++;
		}

		//Reset date and record increments
		$date1->modify('-1 '.$period);
	}
	extract($periods);

	//Minutes, seconds
	$seconds = round(abs($date1->format('U') - $date2->format('U')));
	$minutes = floor($seconds/60);
	$seconds = $seconds - $minutes*60;
		
	$replace = array(
		'/%y/' => $years,
		'/%Y/' => zeroise($years,2),
		'/%m/' => $months,
		'/%M/' => zeroise($months,2),
		'/%d/' => $days,
		'/%D/' => zeroise($days,2),
		'/%a/' => zeroise($total_days,2),
		'/%h/' => $hours,
		'/%H/' => zeroise($hours,2),
		'/%i/' => $minutes,
		'/%I/' =>zeroise($minutes,2),
		'/%s/' => $seconds,
		'/%S/' => zeroise($seconds,2),
		'/%r/' => $r,
		'/%R/' => $R
	);

	return preg_replace(array_keys($replace), array_values($replace), $format);
}	 

/**
* Very basic class to convert php date format into xdate date format used for javascript.
*
* Takes a php date format and converts it to xdate format (see http://arshaw.com/xdate/#Formatting) so
* that it can b used in javascript (notably the fullCalendar).
*
* Doesn't support
*L Whether it's a leap year
* N ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)
* w Numeric representation of the day of the week (0=sun,...)
*  z The day of the year (starting from 0)
*  t Number of days in the given month
* B Swatch Internet time
* u microseconds
*
* e 	Timezone identifier (added in PHP 5.1.0) 	Examples: UTC, GMT, Atlantic/Azores
*  I (capital i) 	Whether or not the date is in daylight saving time 	1 if Daylight Saving Time, 0 otherwise.
*  O  Difference to Greenwich time (GMT) in hours 	Example: +0200
*  T  Timezone abbreviation 	Examples: EST, MDT ...
*  Z  Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.

*  c  ISO 8601 date (added in PHP 5) 	2004-02-12T15:19:21+00:00
*  r  RFC 2822 formatted date 	Example: Thu, 21 Dec 2000 16:01:07 +0200
*  U Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) 	See also time()
*
* @since 1.4
*
*@param string $phpformat Format according to http://php.net/manual/en/function.date.php
*@return string The format translated to xdate format: http://arshaw.com/xdate/#Formatting
*/
function eventorganiser_php2xdate($phpformat=""){
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
* A utilty function intended for removing duplicate DateTime objects from array
* @since 1.5
 *@access private
* @ignore
* @ignore
*
* @param array $array Array of DateTime objects (though can work for other objects)
* @return array A sub-array of the passed array, containing only unique elements.
*/
function _eventorganiser_remove_duplicates( $array=array() ){
	//Do we need to worry about the times of the date-time objects?

	if( empty($array) )
		return $array;

	$unique = array();
	foreach ($array as $key=>$object){
		if( !in_array($object, $unique) )
			$unique[$key] = $object;
        }

        return $unique;
} 


/**
 * Utility function Compares two DateTime object, 
 * Returns +1 if the first date is after, -1 if its before or 0 if they're the same
 * Note: does not compare *times*. 
 * Used to filter occurrances with udiff
 *
 * @access private
*  @ignore
 * @since 1.0.0
 *
 * @param dateTime $date1 The first date to compare
 * @param dateTime $date2 The second date to compare
 * @return int 1 | 0 | -1
 */
function _eventorganiser_compare_dates($date1,$date2){
	//Don't wish to compare times
	if($date1->format('Ymd') == $date2->format('Ymd'))
		return 0;

	 return ($date1 > $date2)? 1:-1;
}



/**
 * A workaround which handles "[ordinal] [day] of +(n) month" statements when
 * using php 5.2.
 *
 * @access private
 * @ignore
 * @since 1.3
 *
 * @param dateTime $date dateTime object to modify
 * @param string $modify How to modify the date: e.g. 'last Sunday of +2 month'
 * @return dateTime the modified dateTime object. DateTime is set to blog tz.
 */
function _eventorganiser_php52_modify($date='',$modify=''){
	
	//Expect e.g. 'Second Monday of +2 month
	$pattern = '/([a-zA-Z]+)\s([a-zA-Z]+) of \+(\d+) month/';
	preg_match($pattern, $modify, $matches);

	$ordinal = array_search(strtolower($matches[1]), array('last','first','second','third','fourth') ); //0-4
	$day = array_search(strtolower($matches[2]), array('sunday','monday','tuesday','wednesday','thursday','friday','saturday') ); //0-6
	$freq = intval($matches[3]);

	//set to first day of month
	$date = date_create($date->format('Y-m-1'));

	//first day of +$freq months
	$date->modify('+'.$freq.' month');

	//Calculate offset to day of week	
	if($ordinal >0):
		$offset = ($day-intval($date->format('w')) +7)%7;//Offset to first weekday of that month (e.g. first Monday)
		$d =($offset) +7*($ordinal-1) +1; //If wanting to get second, third or fourth monday add multiples of 7.

	else:
		//Ordinal is 'Last'
		$date = date_create($date->format('Y-m-t'));//Last day
		$offset = intval(($date->format('w')-$day+7)%7);//Find offset from last day to the last weekday of month (e.g. last Sunday)
		$d = intval($date->format('t'))-$offset;
	endif;

	$date = date_create($date->format('Y-m-'.$d), eo_get_blog_timezone());

	return $date;
}

/**
* Generates excerpt from contant if needed, and trims to a given length
*
* Is very simliar to wp_trim_excerpt. Doesn't apply excerpt_more filter
 * Applies eventorganiser_trim_excerpt filter
 * Must be used inside the loop
 *
 * @since 1.5
 * @param string $text Optional. The excerpt. If set to empty, an excerpt is generated.
 * @param int $excerpt_length The excerpt length if generated from content.
 * @return string The excerpt.
*/
function eventorganiser_trim_excerpt($text = '', $excerpt_length=55) {
	$raw_excerpt = $text;
	if ( '' == $text ) {
		$text = get_the_content('');
		$text = strip_shortcodes( $text );
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = wp_trim_words( $text, $excerpt_length, '...' );
	}
	return apply_filters('eventorganiser_trim_excerpt', $text, $raw_excerpt);
}


/**
 * A helper function, creates a DateTime object from a date string and sets the timezone to the blog's current timezone
 *
 *@since 1.3
 *@uses date_create
 *@param string $datetime_string A date-time in string format
 *@return datetime The corresponding DateTime object.
*/
function eventorganiser_date_create($datetime_string){
	$tz = eo_get_blog_timezone();
	return date_create($datetime_string,$tz);
}


/**
 * (Private) Utility function checks a date-time string is formatted correctly (according to the options) 
 * @access private
 * @ignore
 * @since 1.0.0

 * @param datetime_string - a datetime string 
 * @param (bool) $ymd_formated - whether the date is formated in the format YYYY-MM-DD or 
 * @return int DateTime| false - the parsed datetime string as a DateTime object or false on error (incorrectly formatted for example)
 */
function _eventorganiser_check_datetime($datetime_string='',$ymd_formated=false){

	$formatString = eventorganiser_get_option('dateformat');

	//Get regulgar expression.
	if( $ymd_formated ){
		$reg_exp = "/(?P<year>\d{4})[-.\/](?P<month>\d{1,})[-.\/](?P<day>\d{1,}) (?P<hour>\d{2}):(?P<minute>\d{2})/";

	}elseif($formatString =='dd-mm' ){
		$reg_exp = "/(?P<day>\d{1,})[-.\/](?P<month>\d{1,})[-.\/](?P<year>\d{4}) (?P<hour>\d{2}):(?P<minute>\d{2})/";

	}else{
		$reg_exp = "/(?P<month>\d{1,})[-.\/](?P<day>\d{1,})[-.\/](?P<year>\d{4}) (?P<hour>\d{2}):(?P<minute>\d{2})/";
	}

	if( !preg_match($reg_exp, $datetime_string,$matches) ) 
		return false;

	extract(array_map('intval',$matches));

	if ( !checkdate($month, $day, $year) || $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 )
		return false;

	$datetime = new DateTime(null, eo_get_blog_timezone());
	$datetime->setDate($year, $month, $day);
	$datetime->setTime($hour, $minute);
	return $datetime;
}

?>
