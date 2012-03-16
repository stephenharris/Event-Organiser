<?php 
/**
 * Class used for manipulating and displaying events
 *
 * This is mainly used for the back-end, while global functions are 
 * provided for front-ed use. This could be used in templates
 * but would require front-end initialisation. Like global $eo_event for
 * event functions as well as global $post for WordPress standard functions.
 */
global $wp_locale;

class EO_Event{

	var $event_id = -1;
	var $post_id = -1;
	var $venue = '';
	var	$start ='';
	var	$end='';
	var	$duration='';
	var $duration_seconds=0;
	var $duration_string='';
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
	
	static public $daysofweek = array(
		array('D'=>'Sun','I'=>'Sunday','c'=>'S','ical'=>'SU'),
		array('D'=>'Mon','I'=>'Monday','c'=>'M','ical'=>'MO'),
		array('D'=>'Tue','I'=>'Tuesday','c'=>'T','ical'=>'TU'),
		array('D'=>'Wed','I'=>'Wednesday','c'=>'W','ical'=>'WE'),
		array('D'=>'Thu','I'=>'Thursday','c'=>'T','ical'=>'TH'),
		array('D'=>'Fri','I'=>'Friday','c'=>'F','ical'=>'FR'),
		array('D'=>'Sat','I'=>'Saturday','c'=>'S','ical'=>'SA'),
	);
	static public $ical2day = array(
		'SU'=>'Sunday',
		'MO'=>'Monday',
		'TU'=>'Tuesday',
		'WE'=>'Wednesday',
		'TH'=>'Thursday',
		'FR'=>'Friday',
		'SA'=>'Saturday',
	);

	static public $allowed_reoccurs= array(
		'once'=> 'once',
		'daily'=>'day',
		'weekly'=>'week',
		'monthly'=>'month',
		'yearly'=>'year',
	);

	static public $input_fields = array(
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

			if(function_exists('date_diff')){
				$this->duration = date_diff($this->start,$this->end);	
			}
		
			//Work around for PHP < 5.3
			$seconds = round(abs($this->start->format('U') - $this->end->format('U')));
			// 86400 = 60*60*24 seconds in a normal day
			$days = floor($seconds/86400);
			$sec_diff = $seconds - $days*86400;
			$this->duration_string = '+'.$days.'days '.$sec_diff.' seconds';

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
				$meta = esc_attr($events[0]['event_schedule_meta']);
				$bymonthday = preg_match('/BYMONTHDAY=/',$meta);
				$byday = preg_match('/BYDAY=/',$meta);

				//Check for the old system first...;
				if($meta=='date'):
					$this->meta = 'BYMONTHDAY='.$this->start->format('d');

				elseif(!($bymonthday||$byday)):
					$this->meta = 'BYDAY='.$meta;

				else:
					$this->meta = $meta;

				endif;
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
* This functions inserts a new post of event type, with data given in the $post_data
* and event data given in $event_data. Returns the post_id.

 * @since 1.1.0
 *
 * @param array $post_data - array of data to be used by wp_insert_post.
 * @param array $event_data - array of data to be used by EO_Event::create() or EO_Event::createFromObject()
 * @return int $post_id - the post ID of the newly create event
 */
	function insertNewEvent($post_data=array(),$event_data=array()){
		global $EO_Errors;

		//Perform some checks
		if (!current_user_can('edit_events')) 
			wp_die( __('You do not have sufficient permissions to create events') );

		if(empty($post_data)||empty($event_data))
			wp_die( __('Inserting event error: Post or Event data must be supplied.') );


		/*
		* First of all 'create' the event - this performs necessary validation checks and populates the event object
		* We either use EO_Event::create if dates are given by strings (assumed to be blog local-time)
		* Or we use EO_Event::createFromObject if dates are given by DateTime objects
		*/
		$event = new EO_Event(0);
		if(!empty($event_data['dateObjects'])):
			$result = $event->createFromObjects($event_data);
		else:
			
			$result = $event->create($event_data);
		endif;

		if($result):
			//Event is valid, now create new 'event' post
			$post_data_preset=array('post_type'=>'event');
			$post_input = array_merge($post_data,$post_data_preset);

			if(empty($post_input['post_title']))
				$post_input['post_title']='untitled event';
			
			$post_id = wp_insert_post($post_input);

			//Did the event insert correctly?
			if ( is_wp_error( $post_id) || $post_id==0) :
				$EO_Errors->add('eo_error', "Event was <strong>not </strong> created");
				$EO_Errors->add('eo_error', $post_id->get_error_message());

			else:
				//Insert event date details. 
				$event->insert($post_id);
			endif;

		else:
			$EO_Errors->add('eo_error', "Event was <strong>not </strong> created");
			return false;

		endif;

	return $post_id;
	}

/**
* This functions inserts new event data for a post of event type.
* Optionally it can delete existing occurrences / data before inserting new ones.
* It creates (or validates) the event before inserting. If there is an error, nothing
* is deleted. Post_id must be supplied. Returns the created and inserted event as an object.

 * @since 1.1.0
 *
 * @param array $event_data - array of data to be used by EO_Event::create() or EO_Event::createFromObject()
 * @param int $post_id  the ID of the post for which the event is being inserted.
 * @param bool $delete - if true, deletes existing event-data before inserting new ones.
 * @return EO_Event $event - the event object
 */
	function insertEvent($event_data=array(),$post_id=null,$delete=false){

		if (!current_user_can('edit_events')) 
			wp_die( __('You do not have sufficient permissions to create events') );

		if(empty($event_data)||empty($post_id))
			wp_die( __('Inserting event error: Event data and associated post id must be supplied.') );

		$post = get_post($post_id);

		if (empty($post)||$post->post_type !='event') 
			wp_die( __('Event Organiser error: Invalid post') );

		//First of all 'create' the event - this performs necessary validation checks and populates the event object
		$event = new EO_Event($post_id);

		if(!empty($event_data['dateObjects']))
			$result = $event->createFromObjects($event_data);
		else
			$result = $event->create($event_data);

		if($result){
			if($delete){
				eventorganiser_event_delete($post_id);
			}
			$event->insert($post_id);
		}else{
			return false;
		}

		return $event;
	}


/**
* This inserts an event into the database. Post_id must be provided. The event
* should have been created (and validated) and it occurrences calculated and written to the object.

 * @since 1.1.0
 *
 * @param int $post_id  the ID of the post for which the event is being inserted.
 * @return bool - if true insertion was successful
 */
	protected function insert($post_id=null){
		global $wpdb, $eventorganiser_events_table;

		if(empty($post_id))
			return false;
	
		foreach($this->occurrences as $counter=> $occurrence):
			$occurrence_end = clone $occurrence;
			if(function_exists('date_diff')){
				$occurrence_end->add($this->duration);
				$this->duration = date_diff($this->start,$this->end);	

			}else{
				$occurrence_end->modify($this->duration_string);
			}

			$occurrence_input =array(
				'post_id'=>$post_id,
				'StartDate'=>$occurrence->format('Y-m-d'),
				'StartTime'=>$occurrence->format('H:i:s'),
				'EndDate'=>$occurrence_end->format('Y-m-d'),
				'FinishTime'=>$this->end->format('H:i:s'),
				'Venue'=>$this->venue,
				'event_schedule' => $this->schedule,
				'event_schedule_meta' => $this->meta,
				'event_frequency' => $this->frequency,
				'event_occurrence' => $counter,
				'event_allday' =>  $this->allday,
				'reoccurrence_start' => $this->schedule_start->format('Y-m-d'),
				'reoccurrence_end' => $this->schedule_end->format('Y-m-d'),
			);
			$ins = $wpdb->insert($eventorganiser_events_table, $occurrence_input);
		endforeach;
		return true;
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
	global $EO_Errors;

	//Populate raw_input array 
	$raw_input=array();

	if(!empty($raw_data['YmdFormated']))
		$formated = true;
	else
		$formated = false;

	foreach (self::$input_fields as $field => $val):
		if( isset($raw_data[$val['name']])){
			$raw_input[$field]=$raw_data[$val['name']];
		}
	endforeach;

	//Define the defaults, if not present in $raw_input
	$defaults = array(
		'event_schedule'=>'once',
		'event_frequency'=> 1,
		'event_occurrence'=> 0,
		'event_allday'=> 0,
		'Venue'=> 0,
		'exception_dates'=> array(),
		'StartDate'=> '',
		'EndDate'=> '',
		'reoccurrence_end'=> '',
	);
	$input = array_merge($defaults, $raw_input);
	$errors = get_option('eo_notice');

	//First check start and end date/times are supplied and set $start, $end to be the datetime objects
	if($this->check_date($input['StartDate'],$formated)):
		$start =$this->check_date($input['StartDate'],$formated);
		$end = $this->check_date($input['EndDate'],$formated);

	else:
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Start date is required.";
		$EO_Errors->add('eo_error','Start date is required.');
		update_option('eo_notice',$errors);
		return false;
	endif;

	if(!$this->check_date($input['EndDate'] , $formated))
		$end = clone $start;

	//Check the times are set
	if($input['event_allday']==1){
		$allday =1;
		$input['StartTime']='00:00';
		$input['FinishTime']='23:59';

	}else{
		$allday =0;
	}


	if($this->check_time($input['StartTime']) && $this->check_time($input['FinishTime'])):
		$s_time = $this->check_time($input['StartTime']);
		$e_time = $this->check_time($input['FinishTime']);
		$start->setTime($s_time[0],$s_time[1],0);
		$end->setTime($e_time[0],$e_time[1],59);
			
	else:	
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Start and End times are not valid.";
		$EO_Errors->add('eo_error','Start and End times are not valid.');
		update_option('eo_notice',$errors);
		return false;
	
	endif;

	//Set $meta
	if($input['event_schedule']=='weekly')
		$meta = $raw_data['days'];
	else
		$meta = (isset($raw_data['schedule_meta']) ? $raw_data['schedule_meta'] : '');

	//Set $exceptions to be an array of datetime objects
		$exceptions=array();
		foreach($input['exception_dates'] as $exception):
			if($this->check_date($exception,$formated))
				$exceptions[] = $this->check_date($exception,$formated);
		endforeach;

	//Check reoccurrence end date is valid. Else set to the start date of original occurrence.
	if($this->check_date($input['reoccurrence_end'],$formated)){
		$schedule_end = $this->check_date($input['reoccurrence_end'],$formated);
		$schedule_end->setTime(23,59,59);
	}else{
		$schedule_end= clone $start;
	}

	//Create an input array with (datetime) and call createFromObjects
	$obj_input=array(
			'start'=>$start,	
			'end'=>$end,	
			'allday'=>$allday,
			'schedule'=>$input['event_schedule'],
			'schedule_meta'=>$meta,
			'frequency'=>$input['event_frequency'],
			'schedule_end'=>$schedule_end,
			'venue'=>$input['Venue'],
			'exceptions'=>$exceptions
	);

	$result = $this->createFromObjects($obj_input);

	return $result;
}

/*
*This functions takes an array of data to create an event.
*The start/end/schedule_end variables must be DateTime objects
*The exceptions array must be an array of DateTime objects.
* This functons writes to the objects all the supplied data and the occurrences, once its generated them.
* All dates are converted to the blog's timezone after occurrences have been generate
*
 * @since 1.0.0
 *
 * @param array $input array of event data, with dates as DateTime objects
 * @return bool true if no errors encountered.
 */
function createFromObjects($input=array()){

	$errors = get_option('eo_notice');
	$defaults = array(
		'start'=>null,
		'end'=>null,
		'allday'=>0,
		'schedule'=>'once',
		'schedule_meta'=>'',
		'frequency'=>1,
		'schedule_end'=>'',
		'venue'=>0,
		'exceptions'=>array()
	);

	$input = array_merge($defaults,$input);
	extract($input);
	
	//Reset occurrences:
	$this->occurrences =array();

	//Check dates are supplied and are valid
	if(!isset($start) || !($start instanceof DateTime)){
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Start date not provided.";
		update_option('eo_notice',$errors);
		return false;
	}
	
	if(!isset($end) || !($end instanceof DateTime))
		$end = clone $start;

	if(!isset($schedule_end) || !($schedule_end instanceof DateTime))
		$schedule_end = clone $start;


	//Check dates are in chronological order
	if($end < $start){
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Start date occurs after end date";
		update_option('eo_notice',$errors);
		return false;
	}
	if($schedule_end < $start){
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Reoccurrence end date is before is before the start date.";
		update_option('eo_notice',$errors);



	//Check reoccurrence schedule is recognised (white listing it with $allowed_reoccurs).
	if(self::$allowed_reoccurs[$schedule]):
		$this->schedule = $schedule;
	else:
		$errors[$this->post_id][] = "Event dates were not saved.";
		$errors[$this->post_id][] = "Schedule not recognised.";
		update_option('eo_notice',$errors);
		return false;
	endif;

		return false;
	}


	//Ensure event frequency is a positive integer. Else set to 1.
	$frequency = max(abs(intval($frequency)),1);

	//Write to object everything except occurrences and exceptions
	$this->start =$start;
	$this->end =$end;
	$this->schedule_end =$schedule_end;
	$this->schedule = $schedule;
	$this->venue=intval($venue);
	$this->allday = ($allday ? 1 : 0);

	if(function_exists('date_diff')){
		$this->duration = date_diff($this->start,$this->end);
	}
		
	//Work around for PHP < 5.3
	$seconds = round(abs($this->start->format('U') - $this->end->format('U')));
	// 86400 = 60*60*24 seconds in a normal day
	$days = floor($seconds/86400);
	$sec_diff = $seconds - $days*86400;
	$this->duration_string = '+'.$days.'days '.$sec_diff.' seconds';

	$this->frequency = $frequency;		
	$this->meta = $schedule_meta;

	if($this->schedule=='once'):
		$this->frequency =1;
		$this->meta ='';
		$this->schedule_start= clone $this->start;
		$this->schedule_end= clone $this->start;
		$this->occurrences[] = clone $this->start;
		return true;
	endif;


	/*
	* Write exception dates to object. Not fully supported yet. In the pipeline.
	* We wil check these dates (date, NOT time part) against the occurrances array
 	*/
	foreach($exceptions as $exception):
		$this->exceptions[] = $exception;
	endforeach;

	/*
	* Perform schedule checks, returns the 'start_days', interval we jump by and if a work-around function
	* is needed, i.e to deal with 'short month' or leap year problem
	*/

	$schedule_data = $this->setupSchedule();
	
	$start_days =$schedule_data['days']; 	
	$interval =$schedule_data['interval'];
	$workaround =$schedule_data['workaround'];

	//Wipe the slate clean..
	$this->occurrences = array();

	if(!$schedule_data)
		return false;

	foreach($start_days as $index => $start):
		$current = clone $start;
			
		switch($workaround):
			case 'php5.2':
				while($current <= $this->schedule_end):
					$this->occurrences[] = clone $current;	
					$current = $this->php52_modify($current,$interval);
				endwhile;
				break;

			case 'short months':
				 $day_int =intval($start->format('d'));
	
				//Set the first month
				$current_month= clone $start;
				//$current_month->modify('first day of this month');
				$current_month = date_create($current_month->format('Y-m-1'));
				
				while($current_month<=$this->schedule_end):
					$month_int = intval($current_month->format('m'));		
					$year_int = intval($current_month->format('Y'));		

					if(checkdate($month_int , $day_int , $year_int))
						$this->occurrences[] = new DateTime($day_int.'-'.$month_int.'-'.$year_int);

					$current_month->modify($interval);
				endwhile;	
				break;

			//To be used for yearly events occuring on Feb 29
			case 'leap year':
				$current_year = clone $current;
				$current_year->modify('-1 day');

				while($current_year<=$this->schedule_end):	
					$is_leap_year = intval($current_year->format('L'));

					if($is_leap_year){
						$current = clone $current_year;
						$current->modify('+1 day');
						$this->occurrences[] = clone $current;
					}

					$current_year->modify($interval);
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

	//Removes exceptions and duplicates
	$this->occurrences = array_udiff($this->occurrences, $this->exceptions, array($this,'eo_compare_dates'));
	$this->occurrences  = EO_Event::removeDuplicate($this->occurrences);

	//Make sure datetime is in blog's timezone
	$blog_tz =$this->get_timezone();
	$H = intval($this->start->format('H'));
	$i = intval($this->start->format('i'));

	foreach($this->occurrences as $occurrence):
		$occurrence->setTime($H,$i );
		$occurrence->setTimezone($blog_tz);
	endforeach;	

	$this->start->setTimezone($blog_tz);
	$this->end->setTimezone($blog_tz);

	sort($this->occurrences);

	$this->schedule_start = clone $this->occurrences[0];
	$this->schedule_end = clone end($this->occurrences);

	return true;
}


function removeDuplicate($array=array()){

	if(empty($array))
		return $array;

        $unique = array();
	foreach ($array as $key=>$object){
		if (!in_array($object, $unique))
			$unique[$key] = $object;
        }

        return $unique;
} 


/*
*Validates schedule, prior to calculating reoccurrences.
*
 * @since 1.0.0
 *
 * @return bool true if no errors encountered.
 */
protected function setupSchedule(){
		$errors = get_option('eo_notice');
		$workaround = false;
		$occurrence = array("first","second","third","fourth","last");
		$start_days = array();

		switch($this->schedule):
			case 'daily':
				$interval = "+".$this->frequency."day";
				$start_days[] = clone $this->start;
				break;	

			case 'weekly':
				$this->meta = array_filter($this->meta);
				if(!empty($this->meta)):
					array_map('esc_attr',$this->meta);
					foreach ($this->meta as $day):
						$start_day = clone $this->start;
						$start_day->modify(self::$ical2day[$day]);
						$start_days[] = $start_day;
					endforeach;
				else:
					$start_days[] = clone $this->start;
				endif;
				$this->meta= serialize($this->meta);
				$interval = "+".$this->frequency."week";
				break;

			case 'monthly':
				$start_days[] = clone $this->start;

				$rule_value = explode('=',$this->meta,2);
				$rule =$rule_value[0];
				$values = explode(',',$rule_value[1]);//Should only be one value, but may support more in future
				$values =  array_filter($values);
				
				if($rule=='BYMONTHDAY'):
					$day = $start_days[0]->format('d');
					$interval = "+".$this->frequency."month";
					
					if($day >='29')
						$workaround = 'short months';	//This case deals with 29/30/31 of month

					$this->meta='BYMONTHDAY='.$day;

				else:
					$day = $start_days[0]->format('l');
					$day_num = intval($start_days[0]->format('w'));
					$date = intval($start_days[0]->format('d'));
					$n = floor(($date-1)/7);
					if($n==4) $n= -2;

					if(empty($values))
						$values =array(($n+1).self::$daysofweek[$day_num]['ical']);

					$this->meta = 'BYDAY='.$values[0];

					preg_match('/^(-?\d{1,2})([a-zA-Z]{2})/' ,$values[0],$matches);

					$n=(intval($matches[1])-1);
					if($n==-2) $n= 4;
					$day= self::$ical2day[$matches[2]]; 

					if(!isset($occurrence[$n])|| !isset(self::$ical2day[$matches[2]])){
						$errors[$this->post_id][] = "Event dates were not saved.";
						$errors[$this->post_id][] = "Invalid reoccurrence schedule";
						update_option('eo_notice',$errors);
						return false;
					}
					$interval = $occurrence[$n].' '.$day.' of +'.$this->frequency.' month';
					
					//Work around for PHP <5.3
					if(!function_exists('date_diff')){
						$workaround = 'php5.2';
					}
				endif;
				break;
	
			case 'yearly':
				$start_days[] = clone $this->start;
				if($start_days[0]->format('d-m')=='29-02')
					$workaround = 'leap year';
				
				$interval = "+".$this->frequency."year";
				break;

		endswitch;
		
		//Remove duplicates
		$start_days  = EO_Event::removeDuplicate($start_days);
		
		$schedule_data = array('days'=>$start_days,'interval'=>$interval,'workaround'=>$workaround);
		
		return $schedule_data;
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
function check_date($date_string,$formated=false){

	//If nothing set, return false.
	if(!isset($date_string)||$date_string=='') return false;

	//Unless specified, get format from options: dd-mm(-yyyy) or mm-dd(-yyyy)
	if(!$formated){
		$eo_settings_array= get_option('eventorganiser_options');
		$formatString = $eo_settings_array['dateformat'];
	}

	//Check for format
	if($formated)
		preg_match("/(\d{4})[-.\/](\d{1,})[-.\/](\d{1,})/", $date_string,$matches);

	else
		preg_match("/(\d{1,})[-.\/](\d{1,})[-.\/](\d{4})/", $date_string,$matches);

	if(count($matches)<4) return false;

	if($formated):
		$day = intval($matches[3]);
		$month = intval($matches[2]);
		$year = intval($matches[1]);
	
	else:
		if($formatString =='dd-mm'){
			$day = intval($matches[1]);
			$month = intval($matches[2]);
		}else{
			$day = intval($matches[2]);
			$month = intval($matches[1]);
		}
		$year = intval($matches[3]);
	endif;
	
	if (!checkdate($month, $day, $year)) return false;

	//Get blog tz
	$blog_tz =$this->get_timezone();
	if(!($this->timezone instanceof DateTimeZone))
		$this->timezone = $blog_tz;

	$datetime = new DateTime(null, $this->timezone);

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
		return $this->is_all_day();
	}
	function is_all_day(){
		return $this->allday;
	}

	function get_timezone(){
		$tzstring =get_option('timezone_string');
		$offset = get_option('gmt_offset');
		$allowed_zones = timezone_abbreviations_list();

		// Remove old Etc mappings.  Fallback to gmt_offset.
		if ( !empty($tz_string) && false !== strpos($tzstring,'Etc/GMT') )
			$tzstring = '';

		if(empty($tzstring) && $offset!=0):
			//use offset		
			$offset *= 3600; // convert hour offset to seconds

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
		if(empty($tzstring)):
			$tzstring = 'UTC';
		endif;

		return new DateTimezone($tzstring);
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


	function ics_rrule(){
		//Set up schedule end date in UTC
		$the_end = clone $this->schedule_end;
		
		if($this->is_allday())
			$format =	'Ymd';
		else
			$format =	'Ymd\THis\Z';

		$the_end->setTimezone(new DateTimeZone('UTC'));
		$schedule_end = $the_end->format($format);

		switch($this->schedule):
			case 'once':
				return false;

			case 'yearly':
				return "FREQ=YEARLY;INTERVAL=".$this->frequency.";UNTIL=".$schedule_end;

			case 'monthly':
				$reoccurrence_rule = "FREQ=MONTHLY;INTERVAL=".$this->frequency.";";
				$reoccurrence_rule.=$this->meta.";";
				$reoccurrence_rule.= "UNTIL=".$schedule_end;
				return $reoccurrence_rule;
	
			case 'weekly':
				return "FREQ=WEEKLY;INTERVAL=".$this->frequency.";BYDAY=".implode(',',$this->meta).";UNTIL=".$schedule_end;

			case 'daily':
				return "FREQ=DAILY;INTERVAL=".$this->frequency.";UNTIL=".$schedule_end;

			default:
		endswitch;
		return false;
	}

	/**
	 * Deals with ordinal month manipulation (e.g. second day of +2 month) for PHP <5.3
	 * @since 1.2
	 * @param datetime - 'current' date-time 
	 * @string the modify string: second day of +2 month
	 * @return datetime - the date-time calculated.
	 */
	function php52_modify($date='',$modify=''){
		$pattern = '/([a-zA-Z]+)\s([a-zA-Z]+) of \+(\d+) month/';
		preg_match($pattern, $modify, $matches);

		$ordinal_arr = array(
			'last'=>0,
			'first'=>1,
			'second'=>2,
			'third'=>3,
			'fourth'=>4
		);
		$week = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
	
		$ordinal =$ordinal_arr[$matches[1]];
		$day = array_search(strtolower($matches[2]), $week); 
		$freq = intval($matches[3]);

		//set to first day of month
		$date = date_create($date->format('Y-m-1'));

		//add months
		$date->modify('+'.$freq.' month');

		//Calculate offset to day of week	
		//Date of desired day
		if($ordinal >0):
			$offset = ($day-intval($date->format('w')) +7)%7;
			$d =($offset) +7*($ordinal-1) +1;

		else:
			$date = date_create($date->format('Y-m-t'));
			$offset = intval(($date->format('w')-$day+7)%7);
			$d = intval($date->format('t'))-$offset;
		endif;
	
		$date = date_create($date->format('Y-m-'.$d));

		return $date;
}


	function occursBy(){

		if($this->schedule == 'weekly')
			return 'BYDAY';

		if($this->schedule != 'monthly')
			return false;
			
		$bymonthday = preg_match('/BYMONTHDAY=/',$this->meta);

		if($bymonthday)
			return 'BYMONTHDAY';
		else
			return 'BYDAY';
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
