<?php
/**
 * Class used to create the event calendar shortcode
 *
 *@uses EO_Calendar Widget class to generate calendar html
 */
class EventOrganiser_Shortcodes {
	static $add_script;
	static $fullcal =array();
	static $map;
 
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
		$month->modify('first day of this month');
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
		);
		$atts = shortcode_atts( $defaults, $atts );
		$whitelist = array('headerleft','headercenter','headerright','defaultview','firstDay');
		$atts = array_intersect_key($atts, array_flip($whitelist));
		$n = rand(0,100);

		$eo_settings_array= get_option('eventorganiser_options'); 
		$EO_Venues = new EO_Venues;
		$EO_Venues->query();
	
		$terms =get_terms( 'event-category', array('hide_empty' => 0));
			foreach($terms as $term):
				$term_meta = get_option( "eo-event-category_$term->term_id");
				$colour = (isset($term_meta['colour']) ? $term_meta['colour'] : '');
				$term->colour = $colour;
			endforeach;

		$atts['categories']=$terms;
		$atts['venues']=$EO_Venues->results;

		self::$fullcal =array_merge($atts);
		self::$add_script = true;

		$html='<div id="eo_fullcalendar_'.$n.'_loading" style="background:white;position:absolute;z-index:5" >';
		$html.='<img src="'.EVENT_ORGANISER_URL.'/css/images/loading-image.gif'.'" style="vertical-align:middle; padding: 0px 5px 5px 0px;" />'.__('Loading&#8230;').'</div>';
		$html.='<div class="eo-fullcalendar eo-fullcalendar-shortcode" id="eo_fullcalendar_'.$n.'"></div>';

 		return $html;
	}

	function handle_venuemap_shortcode($atts) {
		global $post,$EO_Venue;
		self::$add_script = true;

		//If venue is not set, get the global venue or from the post being viewed
		if(empty($atts['venue']) ){
			if(is_venue()){
				$atts['venue']= $EO_Venue->slug;
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
		self::$map =array('lat'=>$latlng['lat'],'lng'=>$latlng['lng']);

		$return = "<div class='".$class."' id='eo_venue_map' ".$style."></div>";
		return $return;
	}
 
	function handle_eventlist_shortcode($atts=array()) {
		global $post;
		$atts['showrepeats']=1;

		if(isset($atts['venue'])&&$atts['venue']=='%this%'){
			if(!empty($post->Venue)){
				$atts['venue']=(int) $post->Venue;
			}else{
				unset($atts['venue']);
			}
		}
		if(isset($atts['event_category'])){
			$atts['event-category']=	$atts['event_category'];
			unset($atts['event_category']);
		}
		$events = eo_get_events($atts);

		if($events):	
			$return= '<ul class="eo-events eo-events-shortcode">';
			foreach ($events as $event):
				//Check if all day, set format accordingly
				if($event->event_allday){
					$format = get_option('date_format');
				}else{
					$format = get_option('date_format').'  '.get_option('time_format');
				}
				$dateTime = new DateTime($event->StartDate.' '.$event->StartTime);
				$datestring = date_i18n( $format , $dateTime->format('U'));

				$return .= '<li><a title="'.$event->post_title.'" href="'.get_permalink($event->ID).'">'.$event->post_title.'</a> '.__('on','eventorganiser').' '.eo_format_date($event->StartDate.' '.$event->StartTime, $format).$datestring.'</li>';

			endforeach;
			$return.='</ul>';
			return $return;
		endif;
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
