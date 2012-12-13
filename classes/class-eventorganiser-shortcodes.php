<?php
/**
 * Class used to create the event calendar shortcode
 *
 *@uses EO_Calendar Widget class to generate calendar html
 */
class EventOrganiser_Shortcodes {
	static $add_script;
	static $calendars =array();
	static $widget_calendars =array();
	static $map = array();
	static $event;
 
	function init() {
		add_shortcode('eo_calendar', array(__CLASS__, 'handle_calendar_shortcode'));
		add_shortcode('eo_fullcalendar', array(__CLASS__, 'handle_fullcalendar_shortcode'));
		add_shortcode('eo_venue_map', array(__CLASS__, 'handle_venuemap_shortcode'));
		add_shortcode('eo_events', array(__CLASS__, 'handle_eventlist_shortcode'));
		add_shortcode('eo_subscribe', array(__CLASS__, 'handle_subscription_shortcode'));
		add_action('wp_footer', array(__CLASS__, 'print_script'));
	}
 
	function handle_calendar_shortcode($atts=array()) {
		global $post;

		/* Shortcodes don't accept hyphens, so convert taxonomy names */
		$taxs = array('category','tag','venue');
		foreach ($taxs as $tax){
			if(isset($atts['event_'.$tax])){
				$atts['event-'.$tax]=	$atts['event_'.$tax];
				unset($atts['event_'.$tax]);
			}
		}

		/* Backwards compatibility */
		$atts = wp_parse_args($atts,array(
			'showpastevents'=>1,
		));
	
		self::$add_script = true;

		$id = count(self::$widget_calendars);
		self::$widget_calendars['eo_shortcode_calendar_'.$id] = $atts;

		$tz = eo_get_blog_timezone();
		$date =  isset($_GET['eo_month']) ? $_GET['eo_month'].'-01' : 'now';
		$month = new DateTime($date,$tz);
		$month = date_create($month->format('Y-m-1'),$tz);

		$html = '<div class="widget_calendar eo-calendar eo-calendar-shortcode eo_widget_calendar" id="eo_shortcode_calendar_'.$id.'">';
		$html .= '<div id="eo_shortcode_calendar_'.$id.'_content">'.EO_Calendar_Widget::generate_output($month,$atts).'</div>';
		$html .= '</div>';

		return $html;
	}

	function handle_subscription_shortcode($atts, $content=null) {
		extract( shortcode_atts( array(
			'title' => 'Subscribe to calendar',
			'type' => 'google',
		      'class' => '',
		      'id' => '',
		), $atts ) );

		$url = add_query_arg('feed','eo-events',site_url());

		$class = esc_attr($class);
		$title = esc_attr($title);
		$id = esc_attr($id);
		
		if(strtolower($type)=='webcal'):
			$url = str_replace( 'http://', 'webcal://',$url);
		elseif( strtolower($type)=='ical' ):
			//Do nothing
		else:
			$url = add_query_arg('cid',urlencode($url),'http://www.google.com/calendar/render');
		endif;

		$html = '<a href="'.$url.'" target="_blank" class="'.$class.'" title="'.$title.'" id="'.$id.'">'.$content.'</a>';
		return $html;
	}

	function handle_fullcalendar_shortcode($atts=array()) {
		global $post;
		$defaults = array(
			'headerleft'=>'title', 
			'headercenter'=>'',
			'headerright'=>'prev next today',
			'defaultview'=>'month',
			'category'=>'',
			'venue'=>'',
			'timeformat'=>'G:i',
			'axisformat'=>get_option('timeformat'),
			'key'=>'false',
			'tooltip'=>'true',
			'weekends'=>'true',
			'mintime'=>'0',
			'maxtime'=>'24',
			'alldayslot'=>'true',
			'alldaytext'=>__('All Day','eventorganiser'),
			'columnformatmonth'=>'D',
			'columnformatweek'=>'D n/j',
			'columnformatday'=>'l n/j',
		);
		$atts = shortcode_atts( $defaults, $atts );

		/* Handle Boolean attributes */
		$atts['tooltip'] = (strtolower($atts['tooltip'])=='true' ? true : false);
		$atts['weekends'] = (strtolower($atts['weekends'])=='true' ? true : false);
		$atts['alldayslot'] = (strtolower($atts['alldayslot'])=='true' ? true : false);

		/* Handley key attribute */
		$key = ($atts['key']=='true' ? true : false);
		unset($atts['key']);
	
		//Convert php time format into xDate time format
		$date_attributes = array('timeformat','axisformat','columnformatday','columnformatweek','columnformatmonth');
		$atts['timeformatphp'] = $atts['timeformat'];
		foreach( $date_attributes as $date_attribute )
			$atts[$date_attribute] =eventorganiser_php2xdate($atts[$date_attribute]);

		self::$calendars[] =array_merge($atts);
		self::$add_script = true;
		$id = count(self::$calendars);

		$html='<div id="eo_fullcalendar_'.$id.'_loading" style="background:white;position:absolute;z-index:5" >';
		$html.='<img src="'.esc_url(EVENT_ORGANISER_URL.'/css/images/loading-image.gif').'" style="vertical-align:middle; padding: 0px 5px 5px 0px;" />'.__('Loading&#8230;').'</div>';
		$html.='<div class="eo-fullcalendar eo-fullcalendar-shortcode" id="eo_fullcalendar_'.$id.'"></div>';
		if($key){
			$args = array('orderby'=> 'name','show_count'   => 0,'hide_empty'   => 0);
			$html .= eventorganiser_category_key($args,$id);
		}
 		return $html;
	}

	function handle_venuemap_shortcode($atts) {
		global $post;

		//If venue is not set get from the venue being quiered or the post being viewed
		if( empty($atts['venue']) ){
			if( eo_is_venue() ){
				$atts['venue']= esc_attr(get_query_var('term'));
			}else{
				$atts['venue'] = eo_get_venue_slug(get_the_ID());
			}
		}

		$venue_slugs = explode(',',$atts['venue']);

		$args = shortcode_atts( array(
			'zoom' => 15, 'scrollwheel'=>'true','zoomcontrol'=>'true',
			'rotatecontrol'=>'true','pancontrol'=>'true','overviewmapcontrol'=>'true',
			'streetviewcontrol'=>'true','maptypecontrol'=>'true','draggable'=>'true',
			'maptypeid' => 'ROADMAP',
			'width' => '100%','height' => '200px','class' => '',
			'tooltip'=>'false'
			), $atts );

		//Cast options as boolean:
		$bool_options = array('tooltip','scrollwheel','zoomcontrol','rotatecontrol','pancontrol','overviewmapcontrol','streetviewcontrol','draggable','maptypecontrol');
		foreach( $bool_options as $option  ){
			$args[$option] = ( $args[$option] == 'false' ? false : true );
		}

		return eo_get_venue_map($venue_slugs, $args);
	}



	function handle_eventlist_shortcode($atts=array(),$content=null) {
		$taxs = array('category','tag','venue');
		foreach ($taxs as $tax){
			if(isset($atts['event_'.$tax])){
				$atts['event-'.$tax]=	$atts['event_'.$tax];
				unset($atts['event_'.$tax]);
			}
		}

		if((isset($atts['venue']) &&$atts['venue']=='%this%') ||( isset($atts['event-venue']) && $atts['event-venue']=='%this%' )){
			if( eo_get_venue_slug() ){
				$atts['event-venue']=  eo_get_venue_slug();
			}else{
				unset($atts['venue']);
				unset($atts['event-venue']);
			}
		}

		$args = array(
			'class'=>'eo-events eo-events-shortcode',
			'template'=>$content,
			'no_events'=>'',
		);

		return eventorganiser_list_events( $atts,$args, 0);
	}


	function read_template($template){
		$patterns = array(
			'/%(event_title)%/',
			'/%(start)({([^{}]*)}{([^{}]*)}|{[^{}]*})?%/',
			'/%(end)({([^{}]*)}{([^{}]*)}|{[^{}]*})?%/',
			'/%(event_venue)%/',
			'/%(event_venue_url)%/',
			'/%(event_cats)%/',
			'/%(event_tags)%/',
			'/%(event_venue_address)%/',
			'/%(event_venue_postcode)%/',
			'/%(event_venue_city)%/',
			'/%(event_venue_country)%/',
			'/%(schedule_start)({([^{}]*)}{([^{}]*)}|{[^{}]*})?%/',
			'/%(schedule_end)({([^{}]*)}{([^{}]*)}|{[^{}]*})?%/',
			'/%(event_thumbnail)(?:{([^{}]+)})?(?:{([^{}]+)})?%/',
			'/%(event_url)%/',
			'/%(event_custom_field){([^{}]+)}%/',
			'/%(event_venue_map)({[^{}]+})?%/',
			'/%(event_excerpt)(?:{(\d+)})?%/',
			'/%(cat_color)%/',
			'/%(event_title_attr)%/',
			'/%(event_duration){([^{}]+)}%/',
			'/%(event_content)%/',
		);
		$template = preg_replace_callback($patterns, array(__CLASS__,'parse_template'), $template);
		return $template;
	}
	
	function parse_template($matches){
		global $post;
		$replacement='';
		$col = array(
			'start'=>array('date'=>'StartDate','time'=>'StartTime'),
			'end'=>array('date'=>'EndDate','time'=>'FinishTime'),
			'schedule_start'=>array('date'=>'reoccurrence_start','time'=>'StartTime'),
			'schedule_end'=>array('date'=>'reoccurrence_end','time'=>'FinishTime')
		);
		
		switch($matches[1]):
			case 'event_title':
				$replacement = get_the_title();
				break;
				
			case 'start':
			case 'end':
			case 'schedule_start':
			case 'schedule_end':
				switch(count($matches)):
					case 2:
						$dateFormat = get_option('date_format');
						$dateTime = get_option('time_format');
						break;
					case 3:
						$dateFormat =  self::eo_clean_input($matches[2]);
						$dateTime='';
						break;
					case 5:
						$dateFormat =  self::eo_clean_input($matches[3]);
						$dateTime =  self::eo_clean_input($matches[4]);
						break;
				endswitch;
		
				if( eo_is_all_day(get_the_ID()) ){
					$replacement = eo_format_date($post->$col[$matches[1]]['date'].' '.$post->$col[$matches[1]]['time'], $dateFormat);
				}else{	
					$replacement = eo_format_date($post->$col[$matches[1]]['date'].' '.$post->$col[$matches[1]]['time'], $dateFormat.$dateTime);					
				}
				break;
			case 'event_duration':
				$start = eo_get_the_start(DATETIMEOBJ);
				$end = eo_get_the_end(DATETIMEOBJ);
				if( eo_is_all_day() )
					$end->modify('+1 minute');

				if( !function_exists('date_diff') ){
					$duration = date_diff($start,$end);
					$replacement = $duration->format($matches[2]);
				}else{
					$replacement = eo_date_interval($start,$end,$matches[2]);
				}
				break;

			case 'event_tags':
				$replacement = get_the_term_list( get_the_ID(), 'event-tag', '', ', ',''); 
				break;

			case 'event_cats':
				$replacement = get_the_term_list( get_the_ID(), 'event-category', '', ', ',''); 
				break;

			case 'event_venue':
				$replacement =eo_get_venue_name();
				break;

			case 'event_venue_map':
				if(eo_get_venue()){
					$class = (isset($matches[2]) ? self::eo_clean_input($matches[2]) : '');
					$class = (!empty($class) ?  'class='.$class : '');
					$replacement =  eo_get_venue_map( eo_get_venue(), compact('class') );
				}
				break;

			case 'event_venue_url':
				$venue_link =eo_get_venue_link();
				$replacement = ( !is_wp_error($venue_link) ? $venue_link : '');
				break;
			case 'event_venue_address':
				$address = eo_get_venue_address();
				$replacement =$address['address'];
				break;
			case 'event_venue_postcode':
				$address = eo_get_venue_address();
				$replacement =$address['postcode'];
				break;
			case 'event_venue_city':
				$address = eo_get_venue_address();
				$replacement =$address['city'];
				break;
			case 'event_venue_country':
				$address = eo_get_venue_address();
				$replacement =$address['country'];
				break;
			case 'event_thumbnail':
				$size = (isset($matches[2]) ? self::eo_clean_input($matches[2]) : '');
				$size = (!empty($size) ?  $size : 'thumbnail');
				$attr = (isset($matches[3]) ? self::eo_clean_input($matches[3]) : '');

				//Decode HTML entities as shortcode encodes them
				$attr = html_entity_decode($attr);
				$replacement = get_the_post_thumbnail(get_the_ID(),$size, $attr);
				break;
			case 'event_url':
				$replacement =get_permalink();
				break;
			case 'event_custom_field':
				$field = $matches[2];
				$meta = get_post_meta(get_the_ID(), $field);
				$replacement =  implode($meta);
				break;
			case 'event_excerpt':
				$length = ( isset($matches[2]) ? intval($matches[2]) : 55 );
				//Using get_the_excerpt adds a link....
				if ( post_password_required($post) ) {
					$output = __('There is no excerpt because this is a protected post.');
				}else{
					$output = $post->post_excerpt;
				}
				$replacement = eventorganiser_trim_excerpt( $output, $length);
				break;
			case 'event_content':
				$replacement = get_the_content();
				break;
			case 'cat_color':
				$replacement =  eo_get_event_color();
				break;
			case 'event_title_attr':
				$replacement = get_the_title();
				break;

		endswitch;
		return $replacement;
	}

	function eo_clean_input($input){
		$input = trim($input,"{}"); //remove { }
		$input = str_replace(array("'",'"',"&#8221;","&#8216;", "&#8217;"),'',$input); //remove quotations
		return $input;
	}
 
	function print_script() {
		global $wp_locale;
		if ( ! self::$add_script ) return;
		$fullcal = (empty(self::$calendars) ? array() : array(
			'firstDay'=>intval(get_option('start_of_week')),
			'venues' => get_terms( 'event-venue', array('hide_empty' => 0)),
			'categories' => get_terms( 'event-category', array('hide_empty' => 0)),
		));
		wp_localize_script( 'eo_front', 'EOAjax', 
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php'),
			'calendars' => self::$calendars,
			'widget_calendars' => self::$widget_calendars,
			'fullcal' => $fullcal,
			'map' => self::$map,
		));	

		if(!empty(self::$calendars) || !empty(self::$map) || !empty(self::$widget_calendars) ):				
			wp_enqueue_script( 'eo_qtip2');	
			wp_enqueue_style('eventorganiser-jquery-ui-style',EVENT_ORGANISER_URL.'css/eventorganiser-admin-fresh.css',array());	
			wp_enqueue_style('eo_calendar-style');	
			wp_enqueue_style('eo_front');	
			wp_enqueue_script( 'eo_front');
		endif;
	}
}
 
EventOrganiser_Shortcodes::init();

	function eventorganiser_category_key($args=array(),$id=1){
		$args['taxonomy'] ='event-category';

		$html ='<div class="eo-fullcalendar-key" id="eo_fullcalendar_key'.$id.'">';
		$terms = get_terms( 'event-category', $args );
		$html.= "<ul class='eo_fullcalendar_key'>";
		foreach ($terms as $term):
			$slug = esc_attr($term->slug);
			$color = esc_attr($term->color);
			$class = "class='eo_fullcalendar_key_cat eo_fullcalendar_key_cat_{$slug}'";
			$html.= "<li {$class}><span class='eo_fullcalendar_key_colour' style='background:{$color}'>&nbsp;</span>".esc_attr($term->name)."</li>";			
		endforeach;
		$html.='</ul></div>';

		return $html;
	}
?>
