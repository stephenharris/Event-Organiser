<?php

class EO_Shortcode_Factory {

	private $assets_lazy_loader;

	public function __construct( $assets_lazy_loader ) {
		$this->assets_lazy_loader = $assets_lazy_loader;
	}

	public function register_shortcodes() {
		add_shortcode('eo_calendar', array($this, 'calendar'));
		add_shortcode('eo_fullcalendar', array( $this, 'fullcalendar'));
		//add_shortcode('eo_venue_map', array($this, 'venue_map'));
		add_shortcode('eo_events', array($this, 'event_list'));
		add_shortcode('eo_subscribe', array($this, 'subscribe_link'));
	}

	public function calendar( $atts = array() ) {
		$widget_calendar = new EO_Shortcode_WidgetCalendar();
		$widget_calendar->set_attributes( $this->convertUnderscoresToHyphens( $atts ) );
		$widget_calendar->set_content( $content );
		return $widget_calendar->render( $this->assets_lazy_loader );
	}

	public function subscribe_link( $atts, $content = null ) {
		$subscribe_link = new EO_Shortcode_SubscribeLink();
		$subscribe_link->set_attributes( $atts );
		$subscribe_link->set_content( $content );
		return $subscribe_link->render( $this->assets_lazy_loader );
	}

	public function fullcalendar( $atts = array() ) {

		global $wp_locale;

		/* Handle Boolean attributes - this will be passed as strings, we want them as boolean */
		$bool_atts = array(
			'tooltip' => 'true', 'weekends' => 'true', 'alldayslot' => 'true', 'users_events' => 'false',
			'theme' => 'false', 'isrtl' => $wp_locale->is_rtl() ? 'true' : 'false', 'responsive' => 'true',
			'compact' => false,
		);

		$atts = wp_parse_args( $atts, $bool_atts );

		foreach( $bool_atts as $att => $value ){
			$atts[$att] = ( strtolower( $atts[$att] ) == 'true' ? true : false );
		}

		//Backwards compatability, key used to be true/false. Now can be bottom/top
		if( isset( $atts['key'] ) ){
			if( 'true' == strtolower( $atts['key'] ) ){
				$atts['key'] = 'bottom';
			}elseif( !in_array( strtolower( $atts['key'] ), array( 'bottom', 'top' ) ) ){
				$atts['key'] = false;
			}
		}

		if( isset($atts['venue']) && !isset( $atts['event_venue'] ) ){
			$atts['event_venue'] = $atts['venue'];
			unset( $atts['venue'] );
		}
		if( isset($atts['category']) && !isset( $atts['event_category'] ) ){
			$atts['event_category'] = $atts['category'];
			unset( $atts['category'] );
		}

		$date_attributes = array(
			'timeformat', 'axisformat', 'titleformatday', 'titleformatweek', 'titleformatmonth',
			'columnformatmonth', 'columnformatweek', 'columnformatday',
		);

		foreach( $date_attributes as $attribute ){
			if( isset( $atts[$attribute] ) ){
				$atts[$attribute] = self::_cleanup_format( $atts[$attribute] );
			}
		}

		$taxonomies = get_object_taxonomies( 'event' );
		foreach( $taxonomies as $tax ){
			//Shortcode attributes can't contain hyphens
			$shortcode_attr = str_replace( '-', '_', $tax );
			if( isset( $atts[$shortcode_attr] ) ){
				$atts[$tax] = $atts[$shortcode_attr];
				unset( $atts[$shortcode_attr] );
			}
		}

		if( isset( $atts['showdays'] ) ){
			$atts['showdays'] = explode( ',', $atts['showdays'] );
		}

		$calendar = new EO_Shortcode_Fullcalendar();
		$calendar->set_attributes( $atts );
		return $calendar->render( $this->assets_lazy_loader );
	}

	/**
	 * Prior to 3.0.0, formats could accept operators to deal with ranges.
	 * Specifically {...} switches to formatting the 2nd date and ((...)) only displays
	 * the enclosed format if the current date is different from the alternate date in
	 * the same regards.E.g.  M j(( Y)){ '—'(( M)) j Y} produces the following dates:
	 * Dec 30 2013 — Jan 5 2014, Jan 6 — 12 2014
	 *
	 * This was removed in 3.0.0, fullCalendar.js will now automatically split the date where
	 * appropriate. This function removes {...} and all enclosed content and replaces ((...))
	 * by the content contained within to help prevent an users upgrading from the old version.
	 *
	 * @ignore
	 */
	static function _cleanup_format( $format ){
		$format = preg_replace( '/({.*})/', '', $format );
		$format = preg_replace_callback( '/\(\((.*)\)\)/', array( __CLASS__ ,'_replace_open_bracket' ), $format );
		return $format;
	}

	static function _replace_open_bracket( $matches ){
		return $matches[1];
	}

	static function handle_venuemap_shortcode($atts) {
		global $post;

		if( !empty( $atts['event_venue'] ) ){
			$atts['venue'] = $atts['event_venue'];
		}

		//If venue is not set get from the venue being quiered or the post being viewed
		if( empty($atts['venue']) ){
			if( eo_is_venue() ){
				$atts['venue'] = esc_attr( get_query_var( 'term' ) );
			}else{
				$atts['venue'] = eo_get_venue_slug( get_the_ID() );
			}
		}

		$venue_slugs = explode( ',', $atts['venue'] );

		$args = shortcode_atts( array(
			'zoom' => 15, 'zoomcontrol' => 'true', 'minzoom' => 0, 'maxzoom' => null,
			'scrollwheel' => 'true', 'rotatecontrol' => 'true', 'pancontrol' => 'true',
			'overviewmapcontrol' => 'true', 'streetviewcontrol' => 'true',
			'maptypecontrol' => 'true', 'draggable' => 'true', 'maptypeid' => 'ROADMAP',
			'width' => '100%','height' => '200px','class' => '', 'tooltip' => 'false',
			), $atts );

		//Cast options as boolean:
		$bool_options = array(
			'tooltip', 'scrollwheel', 'zoomcontrol', 'rotatecontrol', 'pancontrol',
			'overviewmapcontrol', 'streetviewcontrol', 'draggable', 'maptypecontrol',
		);
		foreach( $bool_options as $option  ){
			$args[$option] = ( $args[$option] == 'false' ? false : true );
		}

		return eo_get_venue_map( $venue_slugs, $args );
	}

	public function event_list( $atts = array(), $content = null ) {
			$event_list = new EO_Shortcode_EventList();

			$atts = $this->convertUnderscoresToHyphens( $atts );

			if((isset($atts['venue']) &&$atts['venue']=='%this%') ||( isset($atts['event-venue']) && $atts['event-venue']=='%this%' )){
				if( eo_get_venue_slug() ){
					$atts['event-venue']=  eo_get_venue_slug();
				}else{
					unset($atts['venue']);
					unset($atts['event-venue']);
				}
			}

			if( isset( $atts['users-events'] ) && strtolower( $atts['users-events'] ) == 'true' ){
				$atts['bookee_id'] = get_current_user_id();
			}

			$event_list->set_attributes( $atts );
			$event_list->set_content( $content );
			return $event_list->render( $this->assets_lazy_loader );
	}

	private function convertUnderscoresToHyphens( $atts ) {
		$parsed_atts = [];
		foreach( $atts as $key => $value ) {
			if ( strpos( $key, '_' ) !== false ) {
				$key = str_replace( '_', '-', $key );
			}
			$parsed_atts[$key] = $value;
		}
		return $parsed_atts;
	}

}
