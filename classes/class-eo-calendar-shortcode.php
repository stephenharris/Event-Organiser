<?php
/**
 * Class used to create the event calendar shortcode
 *
 *@uses EO_Calendar Widget class to generate calendar html
 */
class EO_Calendar_Shortcode {
	static $add_script;
 
	function init() {
		add_shortcode('eo_calendar', array(__CLASS__, 'handle_shortcode'));
		add_action('wp_footer', array(__CLASS__, 'print_script'));
	}
 
	function handle_shortcode($atts) {
		global $post;
		self::$add_script = true;

		$month = new DateTime();
		$month->modify('first day of this month');
		
 		return '<div class="widget_calendar eo-calendar eo-calendar-shortcode" id="eo_calendar">'.EO_Calendar_Widget::generate_output($month).'</div>';
	}
 
	function print_script() {
		if ( ! self::$add_script ) return;
		wp_localize_script( 'eo_front', 'EOAjax', 
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php'),
		));		
		wp_print_scripts('eo_front');	
	}
}
 
EO_Calendar_Shortcode::init();?>
