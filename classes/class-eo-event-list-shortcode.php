<?php 
/**
 * Class used to create the event list shortcode
 */
class EO_Event_LIst_Shortcode {
 
	function init() {
		add_shortcode('eo_events', array(__CLASS__, 'handle_shortcode'));
	}
 
	function handle_shortcode($atts=array()) {
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
			echo '<ul class="eo-events eo-events-shortcode">';
			foreach ($events as $event):
				//Check if all day, set format accordingly
				if($event->event_allday){
					$format = get_option('date_format');
				}else{
					$format = get_option('date_format').'  '.get_option('time_format');
				}
				echo '<li><a title="'.$event->post_title.'" href="'.get_permalink($event->ID).'">'.$event->post_title.'</a> on '.eo_format_date($event->StartDate.' '.$event->StartTime, $format).'</li>';
			endforeach;
			echo '</ul>';
		endif;
	}
}
EO_Event_LIst_Shortcode::init();?>
