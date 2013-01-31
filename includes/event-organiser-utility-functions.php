<?php
/**
 * Utility functions
 *
 * @package utility-functions
*/

/**
 * Formats a datetime object into a specified format and handles translations.
 * Used by 
 *
 * * {@see `eo_get_the_start()`} 
 * * {@see `eo_get_the_end()`}
 * * {@see `eo_get_schedule_start()`}
 * * {@see `eo_get_schedule_last()`}
 *
 * The constant DATETIMEOBJ can be passed to them to get datetime objects 
 * Applies {@see `eventorganiser_format_datetime`} filter
 *
 * @since 1.2.0
 * @link http://php.net/manual/en/function.date.php PHP Date
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
	$formatted_datetime = $datetime->format($dateformatstring);
	return apply_filters('eventorganiser_format_datetime', $formatted_datetime , $format, $datetime);
}

/**
* Formats a date string in the format 'YYYY-MM-DD H:i:s' format or even
 * relative strings like 'today' into a specified format.
 *
 * @uses eo_format_datetime()
 * @since 1.2.0
 * @link http://php.net/manual/en/function.date.php PHP Date
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
 * It's a PHP 5.2 workaround for {@link http://www.php.net/manual/en/dateinterval.format.php date interval format}
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
* Takes a php date format and converts it to {@link http://arshaw.com/xdate/#Formatting xdate format} so
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
 * Very basic class to convert php date format into jQuery UI date format used for javascript.
 *
 * Similar to {@see `eventorganiser_php2xdate()`} - but the format is slightly different for jQuery UI  
 * Takes a php date format and converts it to {@link http://docs.jquery.com/UI/Datepicker/formatDate} so
 * that it can b used in javascript (notably by the datepicker).
 * 
 * **Please note that this function does not convert time formats**
 *
 * @since 1.7
 *
 *@param string $phpformat Format according to http://php.net/manual/en/function.date.php
 *@return string The format translated to xdate format: http://docs.jquery.com/UI/Datepicker/formatDate
 */
function eventorganiser_php2jquerydate($phpformat=""){
	$php2jquerydate = array(
			'Y'=>'yy','y'=>'y','L'=>''/*Not Supported*/,'o'=>'',/*Not Supported*/
			'j'=>'d','d'=>'dd','D'=>'D','DD'=>'dddd','N'=>'',/*NS*/ 'S' => ''/*NS*/,
			'w'=>'', /*NS*/ 'z'=>'o',/*NS*/ 'W'=>'w',
			'F'=>'MM','m'=>'mm','M'=>'M','n'=>'m','t'=>'',/*NS*/
			'a'=>''/*NS*/,'A'=>''/*NS*/,
			'B'=>'',/*NS*/'g'=>''/*NS*/,'G'=>''/*NS*/,'h'=>''/*NS*/,'H'=>''/*NS*/,'u'=>'fff',
			'i'=>''/*NS*/,'s'=>''/*NS*/,
			'O'=>''/*NS*/, 'P'=>''/*NS*/,
	);

	$jqueryformat="";

	for($i=0;  $i< strlen($phpformat); $i++){

		//Handle backslash excape
		if($phpformat[$i]=="\\"){
			$jqueryformat .= "\\".$phpformat[$i+1];
			$i++;
			continue;
		}

		if(isset($php2jquerydate[$phpformat[$i]])){
			$jqueryformat .= $php2jquerydate[$phpformat[$i]];
		}else{
			$jqueryformat .= $phpformat[$i];
		}
	}
	return $jqueryformat;
}


/**
* A utilty function intended for removing duplicate DateTime objects from array
* @since 1.5
 *@access private
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
 *@uses date_create()
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

/**
 * Utility function for printing/returning radio boxes
 *
 * The $args array - excepts the following keys
 *
 * * **id** - The id of the radiobox (alias: label_for)
 * * **name** - The name of the radiobox
 * * **checked** - The the value to have checked
 * * **options** - Array of options in 'value'=>'Label' format
 * * **label** - The label for the radiobox field set
 * * **class** - Class to be added to the radiobox field set
 * * **echo** - Whether to print the mark-up or just return it
 * * **help** - Optional, help text to accompany the field.
 *
 * @access private
 * @param $args array The array of arguments
 */
function eventorganiser_radio_field( $args ){

	$args = wp_parse_args($args,array(
			'checked'=>'', 'help' => '', 'options'=>'', 'name'=>'', 'echo'=>1,
			'class'=>'', 'label' => '','label_for'=>''
			));	

	$id = ( !empty($args['id']) ? $args['id'] : $args['label_for']);
	$name = isset($args['name']) ?  $args['name'] : '';
	$checked = $args['checked'];
	$label = !empty($args['label']) ? '<legend><label>'.esc_html($args['label']).'</label></legend>' : '';
	$class =  !empty($args['class']) ? 'class="'.sanitize_html_class($args['class']).'"'  : '';

	$html = sprintf('<fieldset %s> %s', $class, $label);
	if( !empty($args['options']) ){
		foreach ($args['options'] as $value => $opt_label ){
			$html .= sprintf('<label for="%1$s"><input type="radio" id="%1$s" name="%3$s" value="%4$s" %2$s> <span> %5$s </span></label><br>',
				esc_attr($id.'_'.$value),
				checked($value, $checked, false),
				esc_attr($name),
				esc_attr($value),
				esc_html($opt_label));
		}
	}
	if(!empty($args['help'])){
		$html .= '<p class="description">'.esc_html($args['help']).'</p>';
	}
	$html .= '</fieldset>';

	if( $args['echo'] )
		echo $html;

	return $html;
}


/**
 * Utility function for printing/returning select field
 *
 * The $args array - excepts the following keys
 *
 * * **id** - The id of the select box (alias: label_for)
 * * **name** - The name of the select box
 * * **selected** - The the value to have selected
 * * **options** - Array of options in 'value'=>'Label' format
* * **multiselect** True or False for multi-select
 * * **label** - The label for the radiobox field set
 * * **class** - Class to be added to the radiobox field set
 * * **echo** - Whether to print the mark-up or just return it
 * * **help** - Optional, help text to accompany the field.
 *
 * @access private
 * @param $args array The array of arguments
 */
function eventorganiser_select_field($args){

	$args = wp_parse_args($args,array(
			'selected'=>'', 'help' => '', 'options'=>'', 'name'=>'', 'echo'=>1,
			'label_for'=>'','class'=>'','disabled'=>false,'multiselect'=>false,
		));	

	$id = ( !empty($args['id']) ? $args['id'] : $args['label_for']);
	$name = isset($args['name']) ?  $args['name'] : '';
	$selected = $args['selected'];
	$class = sanitize_html_class($args['class']);
	$multiselect = ($args['multiselect'] ? 'multiple' : '' );
	$disabled = ($args['disabled'] ? 'disabled="disabled"' : '' );

	$html = sprintf('<select %s name="%s" id="%s" %s>',
		!empty( $class ) ? 'class="'.$class.'"'  : '',
			esc_attr($name),
			esc_attr($id),
			$multiselect.' '.$disabled
		);
		if( !empty( $args['show_option_all'] ) ){
			$html .= sprintf('<option value="" %s> %s </option>',selected( empty($selected), true, false ), esc_html( $args['show_option_all'] ) );
		}

		if( !empty($args['options']) ){
			foreach ($args['options'] as $value => $label ){
				if( $args['multiselect'] && is_array($selected) )
					$_selected = selected( in_array($value, $selected), true, false);
				else
					$_selected =  selected($selected, $value, false);

				$html .= sprintf('<option value="%s" %s> %s </option>',esc_attr($value),$_selected, esc_html($label));
			}
		}
	$html .= '</select>';

	if(!empty($args['help'])){
		$html .= '<p class="description">'.esc_html($args['help']).'</p>';
	}

	if( $args['echo'] )
		echo $html;

	return $html;
}


/**
 * Utility function for printing/returning text field
 *
 * The $args array - excepts the following keys
 *
 * * **id** - The id of the select box (alias: label_for)
 * * **name** - The name of the select box
 * * **value** - The value of the text field
 * * **type** - The type  of the text field (e.g. 'text','hidden','password')
 * * **options** - Array of options in 'value'=>'Label' format
 * * **label** - The label for the radiobox field set
 * * **class** - Class to be added to the radiobox field set
 * * **echo** - Whether to print the mark-up or just return it
 * * **help** - Optional, help text to accompany the field.
 *
 * @access private
 * @param $args array The array of arguments
 */
function eventorganiser_text_field($args){

	$args = wp_parse_args($args,
		array(
		 	'type' => 'text', 'value'=>'', 'placeholder' => '','label_for'=>'',
			 'size'=>false, 'min' => false, 'max' => false, 'style'=>false, 'echo'=>true,
			)
		);		

	$id = ( !empty($args['id']) ? $args['id'] : $args['label_for']);
	$name = isset($args['name']) ?  $args['name'] : '';
	$value = $args['value'];
	$type = $args['type'];
	$class = isset($args['class']) ? esc_attr($args['class'])  : '';

	$min = (  $args['min'] !== false ?  sprintf('min="%d"', $args['min']) : '' );
	$max = (  $args['max'] !== false ?  sprintf('max="%d"', $args['max']) : '' );
	$size = (  !empty($args['size']) ?  sprintf('size="%d"', $args['size']) : '' );
	$style = (  !empty($args['style']) ?  sprintf('style="%s"', $args['style']) : '' );
	$placeholder = ( !empty($args['placeholder']) ? sprintf('placeholder="%s"', $args['placeholder']) : '');
	$disabled = ( !empty($args['disabled']) ? 'disabled="disabled"' : '' );
	$attributes = array_filter(array($min,$max,$size,$placeholder,$disabled, $style));

	$html = sprintf('<input type="%s" name="%s" class="%s regular-text ltr" id="%s" value="%s" autocomplete="off" %s />',
		esc_attr($type),
		esc_attr($name),
		sanitize_html_class($class),
		esc_attr($id),
		esc_attr($value),
		implode(' ', $attributes)
	);

	if( isset($args['help']) ){
		$html .= '<p class="description">'.$args['help'].'</p>';
	}

	if( $args['echo'] )
		echo $html;

	return $html;
}
	

/**
 * Utility function for printing/returning text field
 *
 * The $args array - excepts the following keys
 *
 * * **id** - The id of the checkbox (alias: label_for)
 * * **name** - The name of the select box
 * * **options** - Single or Array of options in 'value'=>'Label' format
 * * **values** - The values of the text field
 * * **type** - The type  of the text field (e.g. 'text','hidden','password')

 * * **label** - The label for the radiobox field set
 * * **class** - Class to be added to the radiobox field set
 * * **echo** - Whether to print the mark-up or just return it
 * * **help** - Optional, help text to accompany the field.
 *
 * @access private
 * @param $args array The array of arguments
 */
function eventorganiser_checkbox_field($args=array()){

	$args = wp_parse_args($args,array(
		 	 'help' => '','name'=>'', 'class'=>'',
			'checked'=>'', 'echo'=>true,'multiselect'=>false
		));

	$id = ( !empty($args['id']) ? $args['id'] : $args['label_for']);
	$name = isset($args['name']) ?  $args['name'] : '';
	$class = ( $args['class'] ? "class='".sanitize_html_class($args['class'])."'"  :"" );

	/* $options and $checked are either both arrays or they are both strings. */
	$options =  isset($args['options']) ? $args['options'] : false;
	$checked =  isset($args['checked']) ? $args['checked'] : 1;

	$html ='';
	if( is_array($options) ){
		foreach( $options as $value => $opt_label ){
			$html .= sprintf('<label for="%1$s">
								<input type="checkbox" name="%2$s" id="%1$s" value="%3$s" %4$s %5$s> 
								%6$s </br>
							</label>',
							esc_attr($id.'_'.$value),
							esc_attr(trim($name).'[]'),
							esc_attr($value),
							checked( in_array($value, $checked), true, false ),
							$class,
							 esc_attr($opt_label)
							);
		}
	}else{
		$html .= sprintf('<input type="checkbox" id="%1$s" name="%2$s" %3$s %4$s value="%5$s">',
							esc_attr($id),
							esc_attr($name),
							checked( $checked, $options, false ),
							$class,
							esc_attr($options)
							);
	}
	
	if(!empty($args['help'])){
		$html .= '<p class="description">'.$args['help'].'</p>';
	}

	if( $args['echo'] )
		echo $html;

	return $html;
}



/**
 * Utility function for printing/returning text area
 *
 * The $args array - excepts the following keys
 *
 * * **id** - The id of the checkbox (alias: label_for)
 * * **name** - The name of the select box
 * * **options** - Single or Array of options in 'value'=>'Label' format
 * * **tinymce** Whether to use the TinyMCE editor. The TinyMCE prints directly.
 * * **value** - The value of the text area
 * * **rows** - The number of rows. Default 5.
 * * **cols** - The number of columns. Default 50.
 * * **class** - Class to be added to the textarea
 * * **echo** - Whether to print the mark-up or just return it
 * * **help** - Optional, help text to accompany the field.
 *
 * @access private
 * @param $args array The array of arguments
 */
function eventorganiser_textarea_field($args){

	$args = wp_parse_args($args,array(
	 	'type' => 'text', 'value'=>'', 'tinymce' => '', 'help' => '',
		'class'=>'large-text', 'echo'=>true,'rows'=>5, 'cols'=>50
	));

	$id = ( !empty($args['id']) ? $args['id'] : $args['label_for']);
	$name = isset($args['name']) ?  $args['name'] : '';
	$value = $args['value'];
	$class = $args['class'];
	$html ='';

	if( $args['tinymce'] ){
		wp_editor( $value, esc_attr($id) ,array(
				'textarea_name'=>$name,
				'media_buttons'=>false,
			));
	}else{
		$html .= sprintf('<textarea cols="%s" rows="%d" name="%s" class="%s large-text" id="%s">%s</textarea>',
				intval($args['cols']),
				intval($args['rows']),
				esc_attr($name),
				sanitize_html_class($class),
				esc_attr($id),
				esc_textarea($value)
		);
	}

	if(!empty($args['help'])){
		$html .= '<p class="description">'.$args['help'].'</p>';
	}

	if( $args['echo'] )
		echo $html;

	return $html;
}


function eventorganiser_esc_printf($text){
	return str_replace('%','%%',$text);
}


function eventorganiser_cache_get( $key, $group ){

	$ns_key = wp_cache_get( 'eventorganiser_'.$group.'_key' );

	// if not set, initialize it
	if ( $ns_key === false )
		wp_cache_set( 'eventorganiser_'.$group.'_key', 1 );

	return wp_cache_get( "eo_".$ns_key."_".$key, $group );
}


function eventorganiser_clear_cache( $group, $key = false ){

	if( $key == false ){
		//If a key is not specified clear entire group by incrementing name space
		return wp_cache_incr( 'eventorganiser_'.$group.'_key' );
		
	}elseif( $ns_key = wp_cache_get( 'eventorganiser_'.$group.'_key' ) ){
		//If key is specified - clear particular key from the group
		return wp_cache_delete( "eo_".$ns_key."_".$key, $group );
	}
}
	

function eventorganiser_cache_set( $key, $value, $group, $expire = 0 ){
	$ns_key = wp_cache_get( 'eventorganiser_'.$group.'_key' );

	// if not set, initialize it
	if ( $ns_key === false )
		wp_cache_set( 'eventorganiser_'.$group.'_key', 1 );

	return wp_cache_add( "eo_".$ns_key."_".$key, $value, $group, $expire );
}
	
?>