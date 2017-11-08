<?php
class EO_Shortcode_Fullcalendar implements EO_Shortcode {

	private $script_lazy_loader;

	private static $calendars;

	private static $load_venues = false;

	private static $load_categories = false;

	private static $load_tags = false;

	private static $load_users = false;

	private $id = null;

	private $args = array();

	/**
	 * @var string|bool false, truthy (bottom) or 'top'
	 */
	private $key = '';

	public function __construct() {
		$this->id = count( self::$calendars ) + 1;
	}

	public function set_attributes( $args ) {

		global $wp_locale;
		$defaults = array(
			'headerleft' => 'title', 'headercenter' => '', 'headerright' => 'prev next today',
			'defaultview' => 'month', 'aspectratio' => false, 'compact' => false,
			'event-category' => '', 'event_category' => '', 'event-venue' => '', 'event_venue' => '', 'event-tag' => '',
			'event_organiser' => false,
			'timeformat' => get_option( 'time_format' ), 'axisformat' => get_option( 'time_format' ),
			'key' => false, 'tooltip' => true,
			'weekends' => true, 'mintime' => '0', 'maxtime' => '24', 'showdays' => array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' ),
			'slotduration' => '00:30:00',
			'nextdaythreshold' => '06:00:00',
			'alldayslot' => true, 'alldaytext' => __( 'All day', 'eventorganiser' ),
			'columnformatmonth' => 'D', 'columnformatweek' => 'D n/j', 'columnformatday' => 'l n/j',
			'titleformatmonth' => 'F Y', 'titleformatweek' => 'M j, Y', 'titleformatday' => 'l, M j, Y',
			'weeknumbers' => false,
			'year' => false, 'month' => false, 'date' => false, 'defaultdate' => false,	'users_events' => false,
			'event_series' => false, 'event_occurrence__in' => array(),
			'theme' => false, 'reset' => true, 'isrtl' => $wp_locale->is_rtl(),
			'responsive' => true, 'responsivebreakpoint' => 514,
		);

		//year/month/day
		if ( isset( $args['year'] ) ) {
			$args['month'] = isset( $args['month'] ) ? $args['month'] : '01';
			$args['date'] = isset( $args['date'] ) ? $args['date'] : '01';
		}
		if ( isset( $args['month'] ) ) {
			$args['year']  = isset( $args['year'] ) ? $args['year'] : date( 'Y' );
			$args['date']  = isset( $args['date'] ) ? $args['date'] : '01';
			$args['month'] = str_pad( $args['month'], 2, '0', STR_PAD_LEFT );
		}
		if ( isset( $args['date'] ) ) {
			$args['year']  = isset( $args['year'] ) ? $args['year'] : date( 'Y' );
			$args['month'] = isset( $args['month'] ) ? $args['month'] : date( 'M' );
			$args['date']  = str_pad( $args['date'], 2, '0', STR_PAD_LEFT );
		}

		if ( isset( $args['year'] ) ) {
			$args['defaultdate'] = $args['year'] . '-' . $args['month'] . '-' . $args['date'];
		}

		$args = shortcode_atts( $defaults, $args, 'eo_fullcalendar' );

		//Days to show
		$args['showdays'] = array_map( 'strtoupper', $args['showdays'] );
		$args['hiddendays'] = array_diff( array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' ), $args['showdays'] );
		$args['hiddendays'] = array_keys( $args['hiddendays'] );
		unset( $args['showdays'] );

		$this->key = $args['key'];
		unset( $args['key'] );

		//Convert event_category / event_venue to comma-delimitered strings
		$args['event_category'] = is_array( $args['event-category'] ) ? implode( ',', $args['event-category'] ) : $args['event-category'];
		$args['event_venue']    = is_array( $args['event-venue'] )    ? implode( ',', $args['event-venue'] )    : $args['event-venue'];
		$args['event_tag']      = is_array( $args['event-tag'] )      ? implode( ',', $args['event-tag'] )      : $args['event-tag'];

		unset($args['event-category']);
		unset($args['event-venue']);
		unset($args['event-tag']);

		//max/min time MUST be hh:mm format
		$times = array( 'mintime', 'maxtime' );
		foreach ( $times as $arg ) {
			$args[$arg] = explode( ':', $args[$arg] );
			if ( count( $args[$arg] ) < 2 ) {
				$args[$arg][] = '00';
			}
			$args[$arg] = implode( ':', $args[$arg] );
		}

		//Convert php time format into moment time format
		$date_attributes = array(
			'timeformat',
			'axisformat',
			'columnformatday',
			'columnformatweek',
			'columnformatmonth',
			'titleformatmonth',
			'titleformatday',
			'titleformatweek',
		);
		$args['timeformatphp'] = $args['timeformat'];
		foreach ( $date_attributes as $date_attribute ) {
			$args[$date_attribute] = str_replace( '((', '[', $args[$date_attribute] );
			$args[$date_attribute] = str_replace( '))', ']', $args[$date_attribute] );
			$args[$date_attribute.'php'] = $args[$date_attribute];
			$args[$date_attribute] = eo_php_to_moment( $args[$date_attribute] );
		}

		//Week numbers
		$args['weeknumbers'] = ! empty( $args['weeknumbers'] );

		self::$calendars[$this->id] = $args;

		$this->lazyLoadData();
	}

	public function set_content( $content = null ) {
	}

	private function lazyLoadData() {
		if ( ! self::$load_venues ) {
			self::$load_venues = $this->headersContainCaseInsensitive( 'venue' );
		}
		if ( ! self::$load_categories ) {
			self::$load_categories = $this->headersContainCaseInsensitive( 'category' );
		}
		if ( ! self::$load_tags ) {
			self::$load_tags = $this->headersContainCaseInsensitive( 'tag' );
		}
		if ( ! self::$load_users ) {
			self::$load_users = $this->headersContainCaseInsensitive( 'organiser' );
		}
	}

	private function headersContainCaseInsensitive( $key ) {
		$args = self::$calendars[$this->id];

		foreach( array( 'headerleft', 'headerright', 'headercenter' ) as $header ) {
			$headers = array_map( 'strtolower', explode( ' ' , $args[$header] ) );
			if( in_array( strtolower( $key ), $headers ) ) {
				return true;
			}
		}
		return false;
	}

	public function render( $script_lazy_loader ) {

		$args = self::$calendars[$this->id];

		// Load scripts/styles
		$script_lazy_loader->register_lazy_data_listener( 'eo_front', $this );
		$script_lazy_loader->enqueue_script( 'eo_front' );
		$script_lazy_loader->enqueue_style( 'eo_front' );
		$script_lazy_loader->enqueue_style( 'eo_calendar-style' );

		$classes = array( 'eo-fullcalendar', 'eo-fullcalendar-shortcode' );

		if ( $this->args['reset'] ) {
			$classes[] = 'eo-fullcalendar-reset';
		}

		if ( $this->args['responsive'] ) {
			$classes[] = 'eo-fullcalendar-responsive';
		}

		if ( $this->args['compact'] ) {
			$classes[] = 'fc-oneline';
		}

		$html = sprintf( '<div id="eo_fullcalendar_%s_loading" class="eo-fullcalendar-loading" >', $this->id );
		$html .= sprintf(
			'<img src="%1$s" class="eo-fullcalendar-loading-icon" alt="%2$s" /> %2$s',
			esc_url( EVENT_ORGANISER_URL . 'css/images/loading-image.gif' ),
			esc_html__( 'Loading&#8230;', 'eventorganiser' )
		);
		$html .= '</div>';

		$html .= sprintf(
			'<div class="%s" id="eo_fullcalendar_%s"></div>',
			implode( ' ', $classes ),
			$this->id
		);

		if ( 'top' == strtolower( $this->key ) ) {
			$html = $this->render_key() . $html;
		} elseif ( $key ) {
			$html .= $this->render_key();
		}

		return $html;

	}

	private function render_key() {
		$html ='<div class="eo-fullcalendar-key" id="eo_fullcalendar_key'.$this->id.'">';
		$terms = get_terms( 'event-category', array( 'orderby' => 'name', 'show_count' => 0, 'hide_empty' => 0 ) );
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

	public static function load_data() {

		$fullcal = array(
			'firstDay'   => intval( get_option( 'start_of_week' ) ),
		);

		if ( self::$load_venues ) {
			$fullcal['venues'] = get_terms( 'event-venue', array( 'hide_empty' => 0 ) );
		}

		if ( self::$load_categories ) {
			$fullcal['categories'] = get_terms( 'event-category', array( 'hide_empty' => 0 ) );
		}

		if ( self::$load_venues ) {
			$fullcal['tags'] = get_terms( 'event-tag', array( 'hide_empty' => 1 ) );
		}

		if ( self::$load_users ) {
			$users = get_users();
			if ( $users ) {
				$users = wp_list_pluck( $users, 'display_name', 'ID' );
			}
			$fullcal['users'] = $users;
		}

		return array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'fullcal'   => $fullcal,
			'calendars' => array_values( self::$calendars ),
		);
	}
}
