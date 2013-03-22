<?php 
/**
 * Class used to create the event calendar widget
 */
class EO_Calendar_Widget extends WP_Widget
{

	var $w_arg = array(
		'title'=> '',
		'showpastevents'=>1,
		'event-category'=>'',
		'event-venue'=>'',
		);
	static $widget_cal =array();

	function __construct() {
		$widget_ops = array('classname' => 'widget_calendar eo_widget_calendar', 'description' => __('Displays a calendar of your events','eventorganiser') );
		parent::__construct('EO_Calendar_Widget', __('Events Calendar','eventorganiser'), $widget_ops);
  	}
 
	function form($instance)  {
	
		$instance = wp_parse_args( (array) $instance, $this->w_arg ); 	

		printf('<p>
					<label for="%1$s"> %2$s: </label>
					<input type="text" id="%1$s" name="%3$s" value="%4$s">
				</p>',
				esc_attr( $this->get_field_id('title') ),
				 esc_html__('Title', 'eventorganiser'),
				esc_attr( $this->get_field_name('title') ),
				esc_attr($instance['title'])
		);

		printf('<p>
					<label for="%1$s"> %2$s: </label>
					<input type="checkbox" id="%1$s" name="%3$s" value="1" %4$s>
				</p>',
				esc_attr( $this->get_field_id('showpastevents') ),
				 esc_html__('Include past events', 'eventorganiser'),
				esc_attr( $this->get_field_name('showpastevents') ),
				checked($instance['showpastevents'], 1, false)
		);

		printf('<p>
					<label for="%1$s"> %2$s: </label>
					<input type="text" id="%1$s" name="%3$s" value="%4$s" class="widefat">
					<em> %5$s </em>
				</p>',
				esc_attr( $this->get_field_id('event-category') ),
				 esc_html__('Event categories', 'eventorganiser'),
				esc_attr( $this->get_field_name('event-category') ),
				esc_attr($instance['event-category']),
				 esc_html__('List category slug(s), seperate by comma. Leave blank for all', 'eventorganiser')
		);

		printf('<p>
					<label for="%1$s"> %2$s: </label>
					%3$s
				</p>',
				esc_attr( $this->get_field_id('event-venue') ),
				 esc_html__('Event venue', 'eventorganiser'),
				eo_event_venue_dropdown(array('echo'=>0,'show_option_all'=>esc_html__('All Venues','eventorganiser'),'id'=>$this->get_field_id('event-venue'),'selected'=>$instance['event-venue'], 'name'=>$this->get_field_name('event-venue'),'hide_empty'=>false))
		);

	}
 

	function update($new_instance, $old_instance){
		$validated=array();
		$validated['title'] = sanitize_text_field( $new_instance['title'] );
		$validated['event-category'] = sanitize_text_field( $new_instance['event-category'] );
		$validated['event-venue'] = sanitize_text_field( $new_instance['event-venue'] );
		$validated['showpastevents'] = ( !empty($new_instance['showpastevents']) ? 1:  0);
		delete_transient( 'eo_widget_calendar' );
		return $validated;
	}

 
 
	function widget($args, $instance){
		wp_enqueue_script( 'eo_front');
		extract($args, EXTR_SKIP);

		//Set the month to display (DateTime must be 1st of that month)
		$tz = eo_get_blog_timezone();
		$date =  get_query_var('ondate') ?  str_replace('/','-',get_query_var('ondate')) : 'now';
		try{
			$month = new DateTime($date,$tz);
		}catch( Exception $e){
			$month = new DateTime('now',$tz);
		}
		$month = date_create($month->format('Y-m-1'),$tz);
	
		/* Set up the event query */
		$calendar = array(
			'showpastevents'=> (empty($instance['showpastevents']) ? 0 : 1),
			'event-venue'=> (!empty($instance['event-venue']) ? $instance['event-venue'] : 0),
			'event-category'=> (!empty($instance['event-category']) ? $instance['event-category'] : 0),
		);

		add_action('wp_footer', array(__CLASS__, 'add_options_to_script'));

		$id = esc_attr($args['widget_id']);
		self::$widget_cal[$id] = $calendar;

		//Echo widget
    		echo $before_widget;

		$widget_title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

	    	if ( $widget_title )
	   		echo $before_title.esc_html($widget_title).$after_title;

		echo "<div id='{$id}_content' >";
		echo $this->generate_output($month,$calendar);

		echo "</div>";
    		echo $after_widget;
	}

	function add_options_to_script() {
		wp_enqueue_script( 'eo_front');
		if(!empty(self::$widget_cal))
			wp_localize_script( 'eo_front', 'eo_widget_cal', self::$widget_cal);	
	}

/**
* Generates widget / shortcode calendar html
*
* param $month - DateTime object for first day of the month (in blog timezone)
*/
function generate_output( $month, $args=array() ){

	//Translations
	global $wp_locale;
	
	$today = new DateTime('now',eo_get_blog_timezone());

	$key= $month->format('YM').serialize($args).get_locale().$today->format('Y-m-d');
	$calendar = get_transient('eo_widget_calendar');
	if( $calendar && is_array($calendar) && isset($calendar[$key]) ){
		return $calendar[$key];
	}
	
	//Month details
	$first_day_of_month= intval($month->format('N')); //0=sun,...,6=sat
	$days_in_month= intval($month->format('t')); // 28-31
	$last_month = clone $month;
	$last_month->modify('last month');	
	$next_month = clone $month;
	$next_month->modify('next month');

	//Retrieve the start day of the week from the options.
	$start_day=intval(get_option('start_of_week'));//0=sun,...,6=sat

	//How many blank cells before inserting dates
	$offset = ($first_day_of_month-$start_day +7)%7;

	//Number of weeks to show in Calendar
	$totalweeks = ceil(($offset + $days_in_month)/7);

	//Get events for this month
	$start = $month->format('Y-m-d');
	$end = $month->format('Y-m-t');

	//Query events
	$required = array( 'numberposts'=>-1, 'showrepeats'=>1, 'event_start_before'=>$end, 'event_start_after'=>$start );
	$events=  eo_get_events(array_merge($args,$required));
	
	//Populate events array
	$calendar_events =array();
	foreach($events as $event):
		$date = eo_get_the_start('Y-m-d', $event->ID, null, $event->occurrence_id);
		$calendar_events[$date][]=  $event;
	endforeach;

	$before = "<table id='wp-calendar'>";

	$title = sprintf("<caption> %s </caption>", esc_html(eo_format_datetime($month, 'F Y')) );

	$head="<thead><tr>";
	for ($d=0; $d <= 6; $d++): 
			$day = $wp_locale->get_weekday( ($d+$start_day)%7 );
			$day_abbrev =$wp_locale->get_weekday_initial( $day );
			$head .= sprintf("<th title='%s' scope='col'>%s</th>", esc_attr($day), esc_html($day_abbrev) );
	endfor;
	$head.="</tr></thead>";

	$foot = sprintf(	"<tfoot><tr>
							<td id='eo-widget-prev-month' colspan='3'><a title='%s' href='%s'>&laquo; %s</a></td>
							<td class='pad'>&nbsp;</td>
							<td id='eo-widget-next-month' colspan='3'><a title='%s' href='%s'> %s &raquo; </a></td>
						</tr></tfoot>",
						esc_html__('Previous month','eventorganiser'),
						add_query_arg('eo_month',$last_month->format('Y-m')),
						esc_html(eo_format_datetime($last_month, 'M')),
						esc_html__('Next month','eventorganiser'),
						add_query_arg('eo_month',$next_month->format('Y-m')),
						esc_html(eo_format_datetime($next_month, 'M'))
					);							


	$body ="<tbody>";
	$current_date = clone $month;
	$event_archive_link = get_post_type_archive_link('event');
			
	//Foreach week in calendar
	for( $w = 0; $w <= $totalweeks-1; $w++ ):
		$body .="<tr>";

		//For each cell in this week
 		for( $cell = $w*7 +1; $cell <= ($w+1)*7;  $cell++ ): 

			$formated_date = $current_date->format('Y-m-d');

			if( $cell<=$offset || $cell-$offset > $days_in_month ){
					$body .="<td class='pad' colspan='1'>&nbsp;</td>";

			}else{
				$class=array();

				//Is the date 'today'?
				if( $formated_date == $today->format('Y-m-d') )
					$class[] ='today';

				//Does the date have any events
				if( isset($calendar_events[$formated_date]) ){
					$class[] ='event';
					$events = $calendar_events[$formated_date];

					$link = esc_url(eo_get_event_archive_link($current_date->format('Y'),$current_date->format('m'),$current_date->format('d')));

					/**
					 * Filters the the link of a date on the events widget calendar
					 *@param string $link The link
					 *@param datetime $current_date The date being filtered
					 *@param array $events Array of events starting on this day
					*/
					$link = apply_filters('eventorganiser_widget_calendar_date_link', $link, $current_date, $events );
					
					$classes = implode(' ',$class);
					$titles = implode(', ',wp_list_pluck( $events, 'post_title') );

					$body .= sprintf("<td class='%s'> <a title='%s' href='%s'> %s </a></td>",
										esc_attr($classes),
										esc_attr($titles),
										$link,
										($cell-$offset)
								);
				}else{
					$classes = implode(' ',$class);
					$body .= sprintf("<td class='%s'> %s </td>", esc_attr($classes), ($cell-$offset) );
				}

				//Proceed to next day
				$current_date->modify('+1 day');
			}

		 endfor;//Endfor each day in week
		$body .="</tr>";

	endfor; //End for each week

	$body .="</tbody>";
	$after = "</table>";

	if( !$calendar || !is_array($calendar) )
		$calendar = array();
	
	$calendar[$key] = $before.$title.$head.$foot.$body.$after;

	set_transient('eo_widget_calendar',$calendar, 60*60*24);
	return $calendar[$key];
	}
}

?>
