<?php
class EO_Shortcode_WidgetCalendar implements EO_Shortcode {

	private $calendars = array();

	private $atts;

	private $content;

	private $id;

	public function __construct() {
		$this->id = count( self::$calendars ) + 1;
	}

	public function set_attributes( $atts ) {

		/* Parse defaults */
		$atts = wp_parse_args($atts,array(
			'showpastevents' => 1,
			'show-long'      => 0,
			'link-to-single' => 0,
		));

		$this->calendars['eo_shortcode_calendar_'.$this->id] = $atts;
	}

	public function set_content(  $content = null ) {
		$this->content = $content;
	}

	public function render( $assets_lazy_loader ) {

		$atts = $this->calendars['eo_shortcode_calendar_'.$this->id];

		$tz = eo_get_blog_timezone();
		$date =  isset($_GET['eo_month']) ? $_GET['eo_month'].'-01' : 'now';
		$month = new DateTime($date,$tz);
		$month = date_create($month->format('Y-m-1'),$tz);

		return sprintf(
			'<div class="widget_calendar eo-calendar eo-calendar-shortcode eo_widget_calendar" id="%1$s">
				<div id="%1$s_content" class="eo-widget-cal-wrap" data-eo-widget-cal-id="%1$s">%2$s</div>
			</div>',
			'eo_shortcode_calendar_'.$this->id,
			EO_Calendar_Widget::generate_output( $month, $atts )
		);
	}
}
