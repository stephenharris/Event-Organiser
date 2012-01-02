<?php 
/**
 * Class used to create the venue map shortcode
 */
class EO_Venue_Map_Shortcode {
 
	static $add_script;

	function init() {
		add_shortcode('eo_venue_map', array(__CLASS__, 'handle_shortcode'));
		add_action('wp_footer', array(__CLASS__, 'print_script'));
	}
 
	function handle_shortcode($atts) {
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
		$atts['width'] = ( !empty($atts['width']) ) ? $atts['width']:'300px';
		$atts['height'] = ( !empty($atts['height']) ) ? $atts['height']:'200px';

		 //If class is selected use that style, otherwise use specified height and width
		if(!empty($atts['class'])){
			$class = $atts['class']." eo-venue-map googlemap";
			$style="";
		}else{
			$class ="eo-venue-map googlemap";
			$style="style='height:".$atts['height'].";width:".$atts['width'].";'";
		}
		
		//Get latlng value by slug
		$latlng = eo_get_venue_latlng($atts['venue']);

		$return = "<div class='".$class."' id='eo_venue_map' ".$style."</div>";
		$return .="<input type='hidden' name='eo_venue[Lat]' id='eo_venue_Lat'  value='".$latlng['lat']."'/>";
		$return .=	"<input type='hidden' name='eo_venue[Lng]' id='eo_venue_Lng'  value='".$latlng['lng']."'/>";
		return $return;
	}

	function print_script() {
		if ( ! self::$add_script ) return;
		wp_print_scripts('eo_front');	
	}
}
 
EO_Venue_Map_Shortcode::init();?>
