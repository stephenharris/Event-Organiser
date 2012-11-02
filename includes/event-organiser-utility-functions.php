<?php
/**
* Utility functions
*/

/**
* Formats a datetime object into a specified format and handles translations.
*
 * @since 1.2.0
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
* Formats a date string in format 'YYYY-MM-DD' format into a specified format
*
 * @since 1.0.0
 * @since 1.2.2 allows relative date strings: 'today', 'tomorrow' etc
 */
function eo_format_date($dateString='',$format='d-m-Y'){

	if($format!=''&& $dateString!=''){
		$datetime = new DateTime($dateString, eo_get_blog_timezone());
		$formated =  eo_format_datetime($datetime,$format);
		return $formated;
	}
	return false;
}


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


	function eo_date_interval($_date1,$_date2, $format){

		//Make sure $date1 is ealier
		$date1 = ($_date1 <= $_date2 ? $_date1 : $_date2);
		$date2 = ($_date1 <= $_date2 ? $_date2 : $_date1);

		//Calculate R values
		$R = ($_date1 <= $_date2 ? '+' : '-');
		$r = ($_date1 <= $_date2 ? '' : '-');

		//Calculate total days
		$total_days = round(abs($date1->format('U') - $date2->format('U'))/86400);

		//A leap year work around - consistent with DateInterval
		$leap_year = ( $date1->format('m-d') == '02-29' ? true : false);
		if( $leap_year ){
			$date1->modify('-1 day');
		}

		$periods = array( 'years'=>-1,'months'=>-1,'days'=>-1,'hours'=>-1);

		foreach ($periods as $period => &$i ){

			if($period == 'days' && $leap_year )
				$date1->modify('+1 day');

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

/*
* Very basic class to convert php date format into xdate date format used for javascript.
*
* Doesn't support
* ** L Whether it's a leap year
* ** N ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)
* ** w Numeric representation of the day of the week (0=sun,...)
* ** z The day of the year (starting from 0)
* ** t Number of days in the given month
* **B Swatch Internet time
* **u microseconds

* ** e 	Timezone identifier (added in PHP 5.1.0) 	Examples: UTC, GMT, Atlantic/Azores
* ** I (capital i) 	Whether or not the date is in daylight saving time 	1 if Daylight Saving Time, 0 otherwise.
* ** O 	Difference to Greenwich time (GMT) in hours 	Example: +0200
* ** T 	Timezone abbreviation 	Examples: EST, MDT ...
* ** Z 	Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.

* ** c 	ISO 8601 date (added in PHP 5) 	2004-02-12T15:19:21+00:00
* ** r 	» RFC 2822 formatted date 	Example: Thu, 21 Dec 2000 16:01:07 +0200
* ** U 	Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) 	See also time()
*/
	function eventorganiser_php2xdate($phpformat=""){
		$php2xdate = array(
				'Y'=>'yyyy','y'=>'yy','L'=>''/*NS*/,'o'=>'I',
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
* @param array of DateTime objects (though can work for other objects)
* @return array a sub-array of the passed array, containing only unique elements.
*/
	function _eventorganiser_remove_duplicates( $array=array() ){
		//Do we need to worry about the times of the date-time objects?
		if( empty($array) )
			return $array;

		$unique = array();
		foreach ($array as $key=>$object){
			if (!in_array($object, $unique))
				$unique[$key] = $object;
	        }

	        return $unique;
	} 

/**
 * Utility function Compares two DateTime object, 
 * returns +1, 0 -1 if the first date is after, the same or before the second
 * used to filter occurrances with udiff
 * @since 1.0.0
 * @return int 1 | 0 | -1
 */
function _eventorganiser_compare_dates($date1,$date2){
	//Don't wish to compare times
	if($date1->format('Ymd') == $date2->format('Ymd'))
		return 0;

	 return ($date1 > $date2)? 1:-1;
}


	function _eventorganiser_php52_modify($date='',$modify=''){
		//Expect e.g. 'Second Monday of +2 month
		$pattern = '/([a-zA-Z]+)\s([a-zA-Z]+) of \+(\d+) month/';
		preg_match($pattern, $modify, $matches);

		$ordinal = array_search(strtolower($matches[1]), array('last','first','second','third','fourth') ); 
		$day = array_search(strtolower($matches[2]), array('sunday','monday','tuesday','wednesday','thursday','friday','saturday') ); 
		$freq = intval($matches[3]);

		//set to first day of month
		$date = date_create($date->format('Y-m-1'));

		//add months
		$date->modify('+'.$freq.' month');

		//Calculate offset to day of week	
		if($ordinal >0):
			$offset = ($day-intval($date->format('w')) +7)%7;
			$d =($offset) +7*($ordinal-1) +1;

		else:
			$date = date_create($date->format('Y-m-t'));
			$offset = intval(($date->format('w')-$day+7)%7);
			$d = intval($date->format('t'))-$offset;
		endif;

		$date = date_create($date->format('Y-m-'.$d), eo_get_blog_timezone());

		return $date;
	}


function eventorganiser_trim_excerpt($text = '', $excerpt_length=55) {
	$raw_excerpt = $text;
	if ( '' == $text ) {
		$text = get_the_content('');
		$text = strip_shortcodes( $text );
		$text = apply_filters('the_content', $text);
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = wp_trim_words( $text, $excerpt_length, ' ' . '[...]' );
	}
	return apply_filters('eventorganiser_trim_excerpt', $text, $raw_excerpt);
}


/**
 * A helper function, creates a DateTime object from a date string and sets the timezone to the blog's current timezone
 *
 *@uses date_create
 *@param string $datetime_string A date-time in string format
 *@param datetime The corresponding DateTime object.
*/
	function eventorganiser_date_create($datetime_string){
		$tz = eo_get_blog_timezone();
		return date_create($datetime_string,$tz);
	}


/**
 * (Private) Utility function checks a date-time string is formatted correctly (according to the options)
* @access private
 * @since 1.0.0

 * @param datetime_string - a datetime string 
 * @param (bool) $ymd_formated - whether the date is formated in the format YYYY-MM-DD or 
 * @return int DateTime| false - the parsed datetime string as a DateTime object or false on error (incorrectly formatted for example)
 */
function _eventorganiser_check_datetime($datetime_string='',$ymd_formated=false){

	$eo_settings_array= get_option('eventorganiser_options');
	$formatString = $eo_settings_array['dateformat'];

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
