<?php 
/**
 * Class used for manipulating and displaying events
 *
 * This is mainly used for the back-end, while global functions are 
 * provided for front-ed use. This could be used in templates
 * but would require front-end initialisation. Like global $eo_event for
 * event functions as well as global $post for WordPress standard functions.
 */
class EO_Event{

	var $event_id = -1;
	var $post_id = -1;
	var $venue = '';
	var	$start ='';
	var	$end='';
	var	$duration='';
	var $allday = true;
	var $schedule = 'once';
	var $meta = '';
	var $frequency = 1;
	var $schedule_start = '';
	var $schedule_end = '';
	var $occurrences = array();
	var $exceptions = array();
	var $schedule_summary;
	var $timezone;

	var $exists =false;

	var $eo_daysofweek = array(
		array('D'=>'Sun','I'=>'Sunday','c'=>'S','ical'=>'SU'),
		array('D'=>'Mon','I'=>'Monday','c'=>'M','ical'=>'MO'),
		array('D'=>'Tue','I'=>'Tuesday','c'=>'T','ical'=>'TU'),
		array('D'=>'Wed','I'=>'Wednesday','c'=>'W','ical'=>'WE'),
		array('D'=>'Thu','I'=>'Thursday','c'=>'T','ical'=>'TH'),
		array('D'=>'Fri','I'=>'Friday','c'=>'F','ical'=>'FR'),
		array('D'=>'Sat','I'=>'Saturday','c'=>'S','ical'=>'SA'),
	);
	var $eo_ical2day = array(
		'SU'=>'Sunday',
		'MO'=>'Monday',
		'TU'=>'Tuesday',
		'WE'=>'Wednesday',
		'TH'=>'Thursday',
		'FR'=>'Friday',
		'SA'=>'Saturday',
		'SU'=>'Sunday',
	);

	var $allowed_reoccurs= array(
		'once'=> 'once',
		'daily'=>'day',
		'weekly'=>'week',
		'monthly'=>'month',
		'yearly'=>'year',
	);

	var $input_fields = array(
			'post_id'=> array('name'=>'post_id'  ,'type'=>'%d'),
			'Venue'=> array('name'=>'venue_id'  ,'type'=>'%s'),
			'StartDate'=> array('name'=>'StartDate'  ,'type'=>'%s'),
			'EndDate'=> array('name'=>'EndDate'  ,'type'=>'%s'),
			'StartTime'=> array('name'=>'StartTime'  ,'type'=>'%s'),
			'FinishTime'=> array('name'=>'FinishTime'  ,'type'=>'%s'),
			'event_schedule'=> array('name'=>'schedule'  ,'type'=>'%s'),
			'event_schedule_meta'=> array('name'=>'schedule_meta'  ,'type'=>'%s'),
			'event_frequency'=> array('name'=>'event_frequency'  ,'type'=>'%d'),
			'event_allday'=> array('name'=>'allday'  ,'type'=>'%d'),
			'event_occurrence'=> array('name'=>'occurrence'  ,'type'=>'%d'),
			'reoccurrence_end'=> array('name'=>'schedule_end'  ,'type'=>'%s'),
			'exception_dates'=> array('name'=>'exception_dates'  ,'type'=>'%s'),
		);



	/**
	 * Gets data from POST (default), supplied array, or from the database if an ID is supplied
	 * @param $location_data
	 * @return null
	 */
	function EO_Event($post_id) {
		global $wpdb,$eventorganiser_events_table;

		//Generate defaults
		$this->start = new DateTime();
		$this->end = new DateTime('+1 hour');
		$this->post_id =intval($post_id);

		$events =$wpdb->get_results($wpdb->prepare("SELECT* FROM $eventorganiser_events_table WHERE post_id= %d ORDER BY event_occurrence ASC", $post_id), ARRAY_A);
		if($events ) $this->to_object($events);
	}

	function to_object( $events = array() ){
		if( is_array($events) && !empty($events)):
			//Save event data
			$this->event_id =intval($events[0]['event_id']);
			$this->post_id =intval($events[0]['post_id']);

			$this->start = new DateTIme($events[0]['StartDate'].' '.$events[0]['StartTime'],$this->get_timezone());
			$this->end = new DateTIme($events[0]['EndDate'].' '.$events[0]['FinishTime'],$this->get_timezone());
			$this->duration = date_diff($this->start,$this->end);	
			$this->schedule_start = new DateTime($events[0]['reoccurrence_start'],$this->get_timezone());
			$this->schedule_end = new DateTime($events[0]['reoccurrence_end'],$this->get_timezone());
			$this->allday = ($events[0]['event_allday']==1 ? true : false);

			$this->venue = intval($events[0]['Venue']);

			$this->schedule = esc_html($events[0]['event_schedule']);

			if($this->is_schedule('weekly')){
				$this->meta = esc_html(implode(',',unserialize($events[0]['event_schedule_meta'])));
				$this->meta = explode(',',$this->meta);
			}
			if($this->is_schedule('monthly')){
				$this->meta = esc_attr($events[0]['event_schedule_meta']);
			}
				
			$this->frequency = intval($events[0]['event_frequency']);
			$this->timezone = 'UTC';
			$this->exists = true;

			//Save event occurrences
			foreach ($events as $event):				
				//Occurrences, array of datetime objects (start date time) in UTC Timezone
				$this->occurrences[$event['event_occurrence']] = new DateTIme($event['StartDate'].' '.$event['StartTime'],$this->get_timezone());
			endforeach;	
	endif;
	}


/**
 * Checks (does not necessarily sanitise) an array for input
 * Called by eventorganiser_details_save()
 * 
 * @since 1.0.0
 *
 * @param array $raw_data the inputed data
 * @return array $input checked input array or false if there is an error
 */
	function create($raw_data){

	//Populate raw_input array 
	$raw_input=array();


	foreach ($this->input_fields as $field => $val):
		if( isset($raw_data[$val['name']])){
			$raw_input[$field]=$raw_data[$val['name']];
		}
	endforeach;

	//Define the defaults, if not present in $raw_input
	$defaults = array(
		'event_schedule'=>'once',
		'event_frequency'=> intval(1),
		'event_occurrence'=> intval(0),
		'event_allday'=> intval(0),
		'Venue'=> intval(0),
		'exception_dates'=> array(),
		'StartDate'=> '',
		'EndDate'=> '',
		'StartDate'=> '',
		'reoccurrence_end'=> '',
	);
	$input = array_merge($defaults, $raw_input);
	$errors = get_option('eo_notice');

	
	//Ensure start/end dates valid. Perform 'clever' corrections if possible. Otherwise abort with error.	
	if($this->check_date($input['StartDate'])&& $this->check_date($input['EndDate'])){
		$this->start =$this->check_date($input['StartDate']);
		$this->end = $this->check_date($input['EndDate']);

	}elseif($this->check_date($input['StartDate'])){
		$this->start = $this->check_date($input['StartDate']);
		$this->end = clone $this->start;
	}elseif($this->check_date($input['EndDate'])){
		$this->end = $this->check_date($input['StartDate']);
		$this->start = clone $this->end;
	}else{
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Start and End dates were not recognised.";
		update_option('eo_notice',$errors);
		return false;
	}

		
	//Check end date is after start date
	if($this->start > $this->end){
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "The end date is before the start date.";
		update_option('eo_notice',$errors);
		return false;
	}

	//Check the times are set
	if($input['event_allday']==1){
		$this->allday=true;
		$input['StartTime']='00:00';
		$input['FinishTime']='23:59';
	}else{
		$this->allday=false;
	}


	if($this->check_time($input['StartTime']) && $this->check_time($input['FinishTime'])){
		$s_time = $this->check_time($input['StartTime']);
		$e_time = $this->check_time($input['FinishTime']);

		$this->start->setTime($s_time[0],$s_time[1],0);
		$this->end->setTime($e_time[0],$e_time[1],59);
		
	}else{
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Start and End times were not valid.";
		update_option('eo_notice',$errors);
		return false;
	}

	//Ensure venue ID is integer
	$this->venue = intval($input['Venue']);

	//Check reoccurrence schedule is recognised (white listing it with $eo_allowed_reoccurs).
	if(!isset($input['event_schedule']) || !isset($this->allowed_reoccurs[$input['event_schedule']])){ 			
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Schedule not recognised.";
		update_option('eo_notice',$errors);
		return false;
	}
	
	$this->schedule =$input['event_schedule'];

	$this->occurrences = array();

	if($this->schedule=='once'){
		$this->frequency =1;
		$this->schedule_start= clone $this->start;
		$this->schedule_end= clone $this->start;
		$this->occurrences[] = clone $this->start;

	}else{
		/*
		 * Deal with exception dates. Not fully supported yet. In the pipeline.
		 * Creates an array of Date-Time objects to check against the occurrances array
		 */
		foreach($input['exception_dates'] as $exception):
			if($this->check_date($exception))
				$this->exceptions[] = $this->check_date($exception);
		endforeach;
			

		//Ensure event frequency is a positive integer. Else set to 1.
		$this->frequency = abs(intval($input['event_frequency']));

		if($this->frequency<1)
			$this->frequency=1;		

		//Check reoccurrence end date is valid. Else set to the end date of original occurrence.
		if($this->check_date($input['reoccurrence_end'])){
			$this->schedule_end = $this->check_date($input['reoccurrence_end']);
			$this->schedule_end->setTime(23,59,59);
		}else{
			$this->schedule_end= clone $this->start;
		}

		//Check reoccurrence end is after end date
		if($this->schedule_end < $this->start){
			$errors[$this->post_id][] = "Event dates were not saved.";
			$errors[$this->post_id][] = "Reoccurrence end date is before is before the start date.";
			update_option('eo_notice',$errors);
			return false;
		}
	}

		$occurrence = array("first","second","third","fourth","last");
		$this->duration = date_diff($this->start,$this->end);	

		$this->reoccurrence_start = clone $this->start;
		$start_days = array();

		$workaround = false;

		switch($this->schedule):
			case 'daily':
				$interval = "+".$this->frequency."day";
				$start_days[] = clone $this->start;
				break;	

			case 'weekly':
				if(isset($raw_data['days'])){
					array_map('esc_attr',$raw_data['days']);
					$this->meta= serialize($raw_data['days']);

					foreach ($raw_data['days'] as $day):
						$start_day = clone $this->start;
						$start_day->modify($this->eo_ical2day[$day]);
						$start_days[] = $start_day;
					endforeach;
				}else{
					$start_days[] = clone $this->start;
				}
				
				$interval = "+".$this->frequency."week";
				break;

			case 'monthly':
				$start_days[] = clone $this->start;
				
				if(($start_days[0]->format('d')>='29')&&($input['event_schedule_meta']=='date')){
					//This case deals with 29/30/31 of month
					$interval = "+".$this->frequency."month";
					$workaround = 'short months';
					$this->meta='date';

				}elseif($input['event_schedule_meta']=='date'){
					$this->meta='date';
					$interval = "+".$this->frequency."month";
				}else{
					$day = $start_days[0]->format('l');
					$day_num = intval($start_days[0]->format('w'));
					$date = intval($start_days[0]->format('d'));
					$n = floor(($date-1)/7);
					$interval = $occurrence[$n].' '.$day.' of +'.$this->frequency.' month';
					if($n==4) $n= -2;
					$this->meta = ($n+1).$this->eo_daysofweek[$day_num]['ical'];
				}

				break;
	

			case 'yearly':
				$start_days[] = clone $this->start;
				if($start_days[0]->format('d-m')=='29-02')
					$workaround = 'leap year';
				
				$interval = "+".$this->frequency."year";
				break;

		endswitch;


		foreach($start_days as $index => $start):
			$counter=$index;
			$current = clone $start;
			
			switch($workaround):

				case 'short months':
					 $day_int =intval($start->format('d'));
	
					//Set the first month
					$current_month= clone $start;
					$current_month->modify('first day of this month');
				
					while($current_month<=$this->schedule_end):
						$month_int = intval($current_month->format('m'));		
						$year_int = intval($current_month->format('Y'));		

						if(checkdate($month_int , $day_int , $year_int))
							$this->occurrences[] = new DateTime($day_int.'-'.$month_int.'-'.$year_int);

						$current_month->modify($interval);
					endwhile;
					break;


				case 'leap year':
					while($current<=$this->schedule_end):	
						if($this->is_leapyear($current))
							$this->occurrences[] = clone $current;
						$current->modify($interval);
					endwhile;
				break;
			
	
				default:
					while($current <= $this->schedule_end):
						$this->occurrences[] = clone $current;	
						$current->modify($interval);
					endwhile;
				break;

			endswitch;	
		endforeach;

	$this->occurrences = array_udiff($this->occurrences, $this->exceptions, array($this,'eo_compare_dates'));

	sort($this->occurrences);
	$this->schedule_start = clone $this->occurrences[0];
	$this->schedule_end = clone end($this->occurrences);
	return true;
}


/**
 * Compares two DateTime object, 
 * returns +1, 0 -1 if the first date is after, the same or before the second
 * used to filter occurrances with udiff
 *
 * @since 1.0.0
 *
 * @return int 1 | 0 | -1
 */
function eo_compare_dates($date1,$date2){
	//Don't wish to compare times
	if($date1->format('Ymd') == $date2->format('Ymd'))
		return 0;

	 return ($date1 > $date2)? 1:-1;
}


/**
 * Checks a date is in the correct format and is valid. Returns 
 * DateTime object if it is, or false if it isn't.
 *
 * @since 1.0.0
 *
 * @param string $date_string the date being checked in string format
 * @return DateTIme | false - if the date is valid or false if it is not. Sets timezone to 
			blog's timezone.
 */
function check_date($date_string){

	//If nothing set, return false.
	if(!isset($date_string)||$date_string=='') return false;

	//Get format from options: dd-mm(-yyyy) or mm-dd(-yyyy)
	$eo_settings_array= get_option('eventorganiser_options');
	$formatString = $eo_settings_array['dateformat'];
	
	//Check for format
	preg_match("/(\d{1,})-(\d{1,})-(\d{4})/", $date_string,$matches);
	if(count($matches)<4) return false;
	
	if($formatString =='dd-mm'){
		$day = intval($matches[1]);
		$month = intval($matches[2]);
	}else{
		$day = intval($matches[2]);
		$month = intval($matches[1]);
	}
	$year = intval($matches[3]);
	
	if (!checkdate($month, $day, $year)) return false;

	$datetime = new DateTime(null, $this->get_timezone());


	$datetime->setDate($year, $month, $day);

	return $datetime;
}


/**
 * Checks a time is in the correct format and is valid.
 *
 * @since 1.0.0
 *
 * @param string $time_string the time being checked in string format
 */
function check_time($time_string){
	//Check time is in correct format
	preg_match("/(\d{2}):(\d{2})/", $time_string,$matches);
	if(count($matches)<3)  return false;

	$hour = intval($matches[1]);
	$minute = intval($matches[2]);

	if($hour > 24 || $hour < 0 || $minute <0 || $minute > 59) return false;

	return array($hour,$minute);
}


/**
* Function to determine if a given year is a leap year or not
 * 
* @since 1.0.0
*
 * @param int $year year to check
 * @bool true if it is a leap year, false otherwise
 */
function is_leapyear($date){
	$year =intval($date->format('Y'));
	if($year%4==0){
		if($year%100==0){
			if($year%400==0) return true;
			return false;
		}
		return true;
	}
	return false;
}



	function is_reoccurring(){
		if($this->schedule=='once') return false;
		return true;
	}

	function is_allday(){
		return $this->allday;
	}


	function get_timezone(){
		$tz_string = get_option('timzone_string');
		$current_offset = get_option('gmt_offset');

		$allowed_zones = timezone_identifiers_list();
	
		// Remove old Etc mappings.  Fallback to gmt_offset.
		if ( !empty($tz_string) && false !== strpos($tzstring,'Etc/GMT') )
			$tzstring = '';
	
		if( !empty($tz_string)){
			//use timezone
			$timezoneName=$tzstring;

		}elseif(empty($tzstring) && $current_offset!=0){
			//use offset		
			$timezoneName = timezone_name_from_abbr("", $current_offset*3600, false);		
		
			//Some offsets do no correspond to a timezone. Find the nearest.
			if(!$timezoneName):
				$current_offset= round($current_offset);
				$timezoneName = timezone_name_from_abbr("", $current_offset*3600, false);		
			endif;

			//Issue with the timezone selected, set to 'UTC'
			if(!$timezoneName):
				$timezoneName = 'UTC';
			endif;

		}else{
			//default
			$timezoneName = 'UTC';
		}

		return new DateTimezone($timezoneName);
	}

	
	function get_the_start($format='Y-m-d'){
		return$this->start->format($format);
	}
	function the_start($format='Y-m-d'){
		echo  $this->get_the_start($format);
	}

	function get_the_end($format='Y-m-d'){
		return $this->end->format($format);
	}
	function the_end($format='Y-m-d'){
		echo  $this->get_the_end($format);
	}

	function schedule_summary(){
		//Generate summary of event
		if($this->is_schedule('once')):
			$this->schedule_summary= 'One time only';

		else:
			if($this->frequency >1){
				$summary = 'This event reoccurs every '.$this->frequency.' '.$this->allowed_reoccurs[$this->schedule].'s';
			}else{
				$summary = 'This event reoccurs every '.$this->allowed_reoccurs[$this->schedule];
			}
		
			switch($this->schedule):
				case 'weekly':
					foreach($this->meta as $ical_day){
						$days[] =  $this->eo_ical2day[$ical_day];
					}
					$summary .=' on '.implode(', ',$days);
					break;

				case 'monthly':
					$summary .= ' on ';
					if($this->meta == 'date'){
						$summary .=$this->start->format('jS');
					}else{
						$summary .=$this->meta;
					}
					break;
			endswitch;
				
		$this->schedule_summary = $summary .' until '.$this->start->format('M, jS Y');

	endif;
}

	function get_the_schedule_end($format='Y-m-d'){
		if($this->schedule_end=='') return '';
		return$this->schedule_end->format($format);
	}

	function the_schedule_end($format='Y-m-d'){
		echo  $this->get_the_schedule_end($format);
	}

	function is_schedule($schedule=''){
		return $schedule == $this->schedule;
	}

	function is_at_venue($venue=-1){
		return $venue == $this->venue;
	}

	function venue_set(){
		return intval($this->venue)>0;
	}

function link_structure(){
	global $wp_rewrite;
	$event_link = $wp_rewrite->get_extra_permastruct('event');

	if ( !empty($event_link)) {
		$event_link = str_replace("%event%", '', $event_link);
		$event_link = home_url( user_trailingslashit($event_link) );
	} else {
		$event_link = add_query_arg(array('post_type' =>'event'), '');
		$event_link = home_url($event_link);
	}
	return $event_link;	
}


}	
?>
