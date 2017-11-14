<?php
class EO_Shortcode_SubscribeLink implements EO_Shortcode {

	private $atts;
	private $content;

	public function set_attributes( $atts ) {
		$this->atts = shortcode_atts( array(
			'title' => 'Subscribe to calendar',
			'type' => 'google',
			'class' => '',
			'id' => '',
			'style' => '',
			'category' => false,
			'venue' => false,
		), $atts, 'eo_subscribe' );
	}

	public function set_content(  $content = null ) {
		$this->content = $content;
	}

	public function render( $assets_lazy_loader ) {

		if ( $this->atts['category'] ) {
			$url = eo_get_event_category_feed( $this->atts['category'] );

		} elseif ( $this->atts['venue'] ) {
			$url = eo_get_event_venue_feed( $this->atts['venue'] );

		} else {
			$url = eo_get_events_feed();
		}

		$class = $atts['class'] ? 'class="' . esc_attr( $atts['class'] ) . '"' : false;
		$title = $atts['title'] ? 'title="' . esc_attr( $atts['title'] ) . '"' : false;
		$style = $atts['style'] ? 'style="' . esc_attr( $atts['style'] ) . '"' : false;
		$id    = $atts['id']    ? 'id="' . esc_attr( $atts['id'] ) . '"' : false;

		if ( strtolower( $atts['type'] ) == 'webcal' ) {
			$url = preg_replace( '/^http(s?):/i', 'webcal:', $url );
		} elseif ( strtolower( $atts['type'] ) == 'ical' ) {
			//Do nothing
		} else {
			//Google doesn't support https:// protocols for the cid value
			//@see https://github.com/stephenharris/Event-Organiser/issues/328
			//@link  http://stackoverflow.com/a/21218052/932391
			$url = preg_replace( '/^https:/i', 'http:', $url );
			$url = add_query_arg( 'cid', urlencode( $url ), 'https://www.google.com/calendar/render' );
		}

		$html = '<a href="'.$url.'" target="_blank" '.$class.' '.$title.' '.$id.' '.$style.'>'.$this->content.'</a>';
		return $html;
	}
}
