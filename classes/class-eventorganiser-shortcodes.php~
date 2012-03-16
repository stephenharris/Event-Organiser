<?php
//TODO - Venue
/**
 * Class used to create the event calendar shortcode
 *
 *@uses EO_Calendar Widget class to generate calendar html
 */
class EventOrganiser_Shortcodes {
	static $add_script;
	static $fullcal =array();
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
 
	function handle_calendar_shortcode($atts) {
		global $post;
		self::$add_script = true;
		$month = new DateTime();
		$month = new DateTime();
		$month = date_create($month->format('Y-m-1'));
 		return '<div class="widget_calendar eo-calendar eo-calendar-shortcode" id="eo_calendar">'.EO_Calendar_Widget::generate_output($month).'</div>';
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
			'headerright'=>'prev,next today',
			'defaultview'=>'month',
			'firstDay'=>intval(get_option('start_of_week')),
			'category'=>'',
			'venue'=>'',
		);
		$atts = shortcode_atts( $defaults, $atts );
		$n = rand(0,100);

		$eo_settings_array= get_option('eventorganiser_options'); 
		$venues =get_terms( 'event-venue', array('hide_empty' => 0));
		$terms =get_terms( 'event-category', array('hide_empty' => 0));

		$atts['categories']=$terms;
		$atts['venues']= $venues;

		self::$fullcal =array_merge($atts);
		self::$add_script = true;

		$html='<div id="eo_fullcalendar_'.$n.'_loading" style="background:white;position:absolute;z-index:5" >';
		$html.='<img src="'.EVENT_ORGANISER_URL.'/css/images/loading-image.gif'.'" style="vertical-align:middle; padding: 0px 5px 5px 0px;" />'.__('Loading&#8230;').'</div>';
		$html.='<div class="eo-fullcalendar eo-fullcalendar-shortcode" id="eo_fullcalendar_'.$n.'"></div>';

 		return $html;
	}

	function handle_venuemap_shortcode($atts) {
		global $post;
		self::$add_script = true;

		//If venue is not set get from the venue being quiered or the post being viewed
		if(empty($atts['venue']) ){
			if(eo_is_venue()){
				$atts['venue']= esc_attr(get_query_var('term'));
			}else{
				$atts['venue'] = eo_get_venue_slug($post->ID);
			}
		}
		
		//Set the attributes
		$atts['width'] = ( !empty($atts['width']) ) ? $atts['width']:'100%';
		$atts['height'] = ( !empty($atts['height']) ) ? $atts['height']:'200px';

		 //If class is selected use that style, otherwise use specified height and width
		if(!empty($atts['class'])){
			$class = $atts['class']." eo-venue-map googlemap";
			$style="";
		}else{
			$class ="eo-venue-map googlemap";
			$style="style='height:".$atts['height'].";width:".$atts['width'].";' ";
		}
		
		//Get latlng value by slug
		$latlng = eo_get_venue_latlng($atts['venue']);
		self::$map[] =array('lat'=>$latlng['lat'],'lng'=>$latlng['lng']);
		$id = count(self::$map);

		$return = "<div class='".$class."' id='eo_venue_map-{$id}' ".$style."></div>";
		return $return;
	}
 
	function handle_eventlist_shortcode($atts=array(),$content=null) {
		global $post;
		$tmp_post = $post;

		$taxs = array('category','tag','venue');
		foreach ($taxs as $tax){
			if(isset($atts['event_'.$tax])){
				$atts['event-'.$tax]=	$atts['event_'.$tax];
				unset($atts['event_'.$tax]);
			}
		}

		if((isset($atts['venue']) &&$atts['venue']=='%this%') ||( isset($atts['event-venue']) && $atts['event-venue']=='%this%' )){
			if(!empty($post->Venue)){
				$atts['event-venue']=(int) $post->Venue; //TODO get venue slug
			}else{
				unset($atts['venue']);
				unset($atts['event-venue']);
			}
		}


		$events = eo_get_events($atts);

		if($events):	
			$return= '<ul class="eo-events eo-events-shortcode">';
			foreach ($events as $post):
				setup_postdata($post); 

				//Check if all day, set format accordingly
				if(eo_is_all_day()){
					$format = get_option('date_format');
				}else{
					$format = get_option('date_format').'  '.get_option('time_format');
				}
				$dateTime = new DateTime($post->StartDate.' '.$post->StartTime);
				
				if(empty($content)):
					$return .= '<li><a title="'.$post->post_title.'" href="'.get_permalink($post->ID).'">'.$post->post_title.'</a> '.__('on','eventorganiser').' '.eo_format_date($post->StartDate.' '.$post->StartTime, $format).'</li>';
				else:
					$return .= '<li>'.self::read_template($content).'</li>';
				endif;

			endforeach;
			$return.='</ul>';
			$post = $tmp_post;
			wp_reset_postdata();

			return $return;
		endif;
	}
	
	function read_template($template){
		$patterns = array();	
		//TODO
		//ICAL/Google link
		//lat/lng?
		$patterns[0] = '/%(event_title)%/';
		$patterns[1] = "/%(start)({([^{}]+)}{([^{}]+)}|{[^{}]+})%/";
		$patterns[2] = "/%(end)({([^{}]+)}{([^{}]+)}|{[^{}]+})%/";
		$patterns[3] = '/%(event_venue)%/';
		$patterns[4] = '/%(event_venue_url)%/';
		$patterns[5] = '/%(event_cats)%/';
		$patterns[6] = '/%(event_tags)%/';
		$patterns[7] = '/%(event_venue_address)%/';
		$patterns[8] = '/%(event_venue_postcode)%/';
		$patterns[9] = '/%(event_venue_country)%/';
		$patterns[10] = "/%(schedule_start)({([^{}]+)}{([^{}]+)}|{[^{}]+})%/";
		$patterns[11] = "/%(schedule_end)({([^{}]+)}{([^{}]+)}|{[^{}]+})%/";
		$patterns[12] = '/%(event_thumbnail)({[^{}]+})?%/';
		$patterns[13] = '/%(event_url)%/';
		$patterns[14] = '/%(event_custom_field){([^{}]+)}%/';
		$patterns[15] = '/%(event_venue_map)({[^{}]+})?%/';
		
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
				$replacement = $post->post_title;
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
		
				if(eo_is_all_day($post->ID)){
					$replacement = eo_format_date($post->$col[$matches[1]]['date'].' '.$post->$col[$matches[1]]['time'], $dateFormat);
				}else{	
					$replacement = eo_format_date($post->$col[$matches[1]]['date'].' '.$post->$col[$matches[1]]['time'], $dateFormat.$dateTime);					
				}
				break;
			case 'event_tags':
				$replacement = get_the_term_list( $post->ID, 'event-tag', '', ', ',''); 
				break;

			case 'event_cats':
				$replacement = get_the_term_list( $post->ID, 'event-category', '', ', ',''); 
				break;

			case 'event_venue':
				$replacement =eo_get_venue_name();
				break;

			case 'event_venue_map':
				if(eo_get_venue()){
					$class = (isset($matches[2]) ? self::eo_clean_input($matches[2]) : '');
					$class = (!empty($class) ?  'class='.$class : '');
					$replacement = do_shortcode('[eo_venue_map '.$class.']');
				}
				break;

			case 'event_venue_url':
				$replacement =eo_get_venue_link();
				break;
			case 'event_venue_address':
				$address = eo_get_venue_address();
				$replacement =$address['address'];
				break;
			case 'event_venue_postcode':
				$address = eo_get_venue_address();
				$replacement =$address['postcode'];
				break;
			case 'event_venue_country':
				$address = eo_get_venue_address();
				$replacement =$address['country'];
				break;
			case 'event_thumbnail':
				$size = (isset($matches[2]) ? self::eo_clean_input($matches[2]) : '');
				$size = (!empty($size) ?  $size : 'thumbnail');
				$replacement = get_the_post_thumbnail($post->ID,$size);
				break;
			case 'event_url':
				$replacement =  get_permalink();
				break;
			case 'event_custom_field':
				$field = $matches[2];
				$meta = get_post_meta($post->ID, $field);
				$replacement =  implode($meta);
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
		wp_localize_script( 'eo_front', 'EOAjax', 
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php'),
			'fullcal' => self::$fullcal,
			'map' => self::$map,
		));	
		if(!empty(self::$fullcal)):
			wp_enqueue_style('eo_calendar-style');		
			wp_enqueue_style('eventorganiser-style');
		endif;
		wp_enqueue_script( 'eo_front');	
	}
}
 
EventOrganiser_Shortcodes::init();
?>
