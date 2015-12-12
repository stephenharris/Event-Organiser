<?php
/**
 * Class used to create the event list widget
 */
class EO_Event_List_Widget extends WP_Widget{

	/*
	 * Array of default settings
	 */
	public static $w_arg;

	function __construct() {
		$widget_ops = array( 'classname' => 'EO_Event_List_Widget', 'description' => __( 'Displays a list of events', 'eventorganiser' ) );
		parent::__construct( 'EO_Event_List_Widget', __( 'Events','eventorganiser' ), $widget_ops );
	}

	/**
	 * Registers the widget with the WordPress Widget API.
	 *
	 * @return void.
	 */
	public static function register() {
		register_widget( __CLASS__ );

		//When adding new variables, remember to exclude non-query related ones
		//from being pass to eventorganiser_list_events().
		self::$w_arg = array(
			'title'           => __( 'Events', 'eventorganiser' ),
			'numberposts'     => 5,
			'event-category'  => '',
			'venue'           => '',
			'orderby'         => 'eventstart',
			'scope'           => 'future',
			'group_events_by' => '',
			'order'           => 'ASC',
			'template'        => '',
			'no_events'       => __( 'No Events', 'eventorganiser' ),
		);

	}

	function get_event_intervals() {

		$intervals = array(
			'future' => array(
				'label' => __( 'Future events', 'eventorganiser' ),
				'query'	=> array(
					'event_start_after' => 'now',
				),
			),
			'all' => array(
				'label' => __( 'All events', 'eventorganiser' ),
				'query'	=> array(
					'showpastevents' => 'all',
				),
			),
			'running' => array(
				'label' => __( 'Running events', 'eventorganiser' ),
				'query'	=> array(
					'event_start_before' => 'now',
					'event_end_after'    => 'now',
				),
			),
			'past' => array(
				'label' => __( 'Past events', 'eventorganiser' ),
				'query'	=> array(
					'event_end_before' => 'now',
				),
			),
			'future-running' => array(
				'label' => __( 'Future & running events', 'eventorganiser' ),
				'query'	=> array(
					'event_end_after' => 'now',
				),
			),
			'today' => array(
				'label' => __( 'Events on today', 'eventorganiser' ),
				'query'	=> array(
					'event_start_before' => 'now',
					'event_end_after'    => 'now',
				),
			),

		);

		$intervals = apply_filters( 'eventorganiser_query_scope', $intervals );

		return $intervals;

	}

	function form( $instance ) {

		if ( ! isset( $instance['scope'] ) && isset( $instance['showpastevents'] ) ) {
			$exclude_running = eventorganiser_get_option( 'runningisnotpast',0 );
			$instance['scope'] = $instance['showpastevents'] ? 'all' : ( $exclude_running ? 'future' : 'future-running' );
		}
		$instance = wp_parse_args( (array) $instance, self::$w_arg );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'eventorganiser' ); ?>: </label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" />	
		</p>
  		<p>
  			<label for="<?php echo $this->get_field_id( 'numberposts' ); ?>"><?php _e( 'Number of events', 'eventorganiser' );?>:   </label>
	  		<input id="<?php echo $this->get_field_id( 'numberposts' ); ?>" name="<?php echo $this->get_field_name( 'numberposts' ); ?>" type="number" size="3" value="<?php echo intval( $instance['numberposts'] );?>" />
		</p>
  		<p>
  			<label for="<?php echo $this->get_field_id( 'event-category' ); ?>"><?php _e( 'Event categories', 'eventorganiser' ); ?>:   </label>
	  		<input  id="<?php echo $this->get_field_id( 'event-category' ); ?>" class="widefat" name="<?php echo $this->get_field_name( 'event-category' ); ?>" type="text" value="<?php echo esc_attr( $instance['event-category'] );?>" />
   			<em><?php _e( 'List category slug(s), seperate by comma. Leave blank for all', 'eventorganiser' ); ?></em>
		</p>
		<?php
		if ( taxonomy_exists( 'event-venue' ) ) { ?>
  			<p>
	  			<label for="<?php echo $this->get_field_id( 'venue' ); ?>"><?php _e( 'Venue:', 'eventorganiser' ); ?></label>
				<?php $venues = get_terms( 'event-venue', array( 'hide_empty' => false ) );?>
				<select id="<?php echo $this->get_field_id( 'venue' ); ?>" name="<?php echo $this->get_field_name( 'venue' ); ?>" type="text">
					<option value="" <?php selected( $instance['venue'], '' ); ?>><?php _e( 'All Venues', 'eventorganiser' ); ?> </option>
					<?php foreach ( $venues as $venue ) { ?>
						<option <?php selected( $instance['venue'], $venue->slug );?> value="<?php echo esc_attr( $venue->slug );?>"><?php echo esc_html( $venue->name ); ?></option>
					<?php } ?>
				</select>
			</p>
		<?php } ?>
		<p>
  			<label for="<?php echo $this->get_field_id( 'scope' ); ?>"><?php _e( 'Show:', 'eventorganiser' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'scope' ); ?>" name="<?php echo $this->get_field_name( 'scope' ); ?>" type="text">
				<?php
				foreach ( $this->get_event_intervals() as $scope_id => $scope ) {
					printf(
						'<option value="%s" %s>%s</option>',
						$scope_id,
						selected( $instance['scope'], $scope_id, false ),
						$scope['label']
					);
				}
				?>
			</select>
		</p>
		      
		<p>
  			<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order by', 'eventorganiser' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'orderby' ); ?>" name="<?php echo $this->get_field_name( 'orderby' ); ?>">
				<option value="eventstart" <?php selected( $instance['orderby'], 'eventstart' ); ?>><?php _e( 'Start date', 'eventorganiser' ); ?></option>
				<option value="title" <?php selected( $instance['orderby'], 'title' );?>><?php _e( 'Title', 'eventorganiser' ); ?></option>
				<option value="date" <?php selected( $instance['orderby'], 'date' );?>><?php _e( 'Publish date', 'eventorganiser' ); ?></option>
			</select>
			<select id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>" type="text">
				<option value="asc" <?php selected( $instance['order'], 'asc' ); ?>><?php _e( 'ASC', 'eventorganiser' ); ?> </option>
				<option value="desc" <?php selected( $instance['order'], 'desc' );?>><?php _e( 'DESC', 'eventorganiser' ); ?> </option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'group_events_by' ); ?>"><?php _e( 'Group occurrences', 'eventorganiser' ); ?></label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'group_events_by' ); ?>" value="series" name="<?php echo $this->get_field_name( 'group_events_by' ); ?>" <?php checked( $instance['group_events_by'],'series' );?> />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'template' ); ?>">
			<?php
				_e( 'Template (leave blank for default)', 'eventorganiser' );
				echo eventorganiser_inline_help(
					__( 'Event list widget placeholders', 'eventorganiser' ),
					sprintf(
						__( 'You can use specified tags as placeholders for event information which you want to appear in the widget. <a href="%s" target="_blank"> Find out more</a>.', 'eventorganiser' ),
						'http://docs.wp-event-organiser.com/widgets/events-list'
					)
				);
			?>
			</label>
  		</p>
  
  		<p>
    		<label for="<?php echo $this->get_field_id( 'no_events' ); ?>"><?php _e( "'No events' message", 'eventorganiser' ); ?>  </label>
	  		<input  id="<?php echo $this->get_field_id( 'no_events' ); ?>" class="widefat" name="<?php echo $this->get_field_name( 'no_events' ); ?>" type="text" value="<?php echo esc_attr( $instance['no_events'] );?>" />
  		</p>
		<?php
	}

	function update( $new_inst, $old_inst ) {

		$intervals = array_keys( $this->get_event_intervals() );

		$validated = array(
			'title'           => sanitize_text_field( $new_inst['title'] ),
			'numberposts'     => intval( $new_inst['numberposts'] ),
			'venue'           => sanitize_text_field( $new_inst['venue'] ),
			'scope'           => isset( $new_inst['scope'] ) && in_array( $new_inst['scope'], $intervals ) ? $new_inst['scope'] : 'future',
			'order'           => ( 'asc' == $new_inst['order'] ? 'asc' : 'desc' ),
			'orderby'         => in_array( $new_inst['orderby'],  array( 'title', 'eventstart', 'date' ) ) ? $new_inst['orderby'] : 'eventstart',
			'group_events_by' => isset( $new_inst['group_events_by'] ) && ( 'series' == $new_inst['group_events_by'] ? 'series' : '' ),
			'template'        => $new_inst['template'],
			'no_events'       => $new_inst['no_events'],
		);

		$event_cats = array_map( 'sanitize_text_field', explode( ',', $new_inst['event-category'] ) );
		$validated['event-category'] = implode( ',', $event_cats );

		return $validated;
	}

	function widget( $args, $instance ) {
		$instance = array_merge( array(
			'no_events' => '',
			'template'  => '',
		), $instance );

		//Backwards compatability with show past events option
		if ( ! isset( $instance['scope'] ) && isset( $instance['showpastevents'] ) ) {
			$exclude_running = eventorganiser_get_option( 'runningisnotpast',0 );
			$instance['scope'] = $instance['showpastevents'] ? 'all' : ( $exclude_running ? 'future' : 'future-running' );
		}
		unset( $instance['showpastevents'] );

		$template  = $instance['template'];
		$no_events = $instance['no_events'];

		echo $args['before_widget'];

		$widget_title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		if ( $widget_title ) {
			echo $args['before_title'] . esc_html( $widget_title ) . $args['after_title'];
		}

		$scope     = $instance['scope'];
		$intervals = $this->get_event_intervals();
		$instance  = array_merge( $instance, (array) $intervals[$scope]['query'] );

		//Ensure $query doesn't contain any non-query related values
		//@see https://github.com/stephenharris/Event-Organiser/issues/314
		$non_query = array( 'title', 'scope', 'no_events', 'template' );
		$query = array_diff_key( $instance, array_flip( $non_query ) );
		eventorganiser_list_events( $query, array(
			'type'      => 'widget',
			'class'     => 'eo-events eo-events-widget',
			'template'  => $template,
			'no_events' => $no_events,
		) );

		echo $args['after_widget'];
	}
}

/**
 * @access private
 * @ignore
 */
function eventorganiser_list_events( $query, $args = array(), $echo = 1 ) {

	$args = array_merge(array(
		'id'        => '',
		'class'     => 'eo-event-list',
		'type'      => 'shortcode',
		'no_events' => '',
	),$args);

	/* Pass these defaults - backwards compat with using eo_get_events()*/
	$query = wp_parse_args($query, array(
		'posts_per_page'   => -1,
		'post_type'        => 'event',
		'suppress_filters' => false,
		'orderby'          => 'eventstart',
		'order'            => 'ASC',
		'showrepeats'      => 1,
		'group_events_by'  => '',
		'showpastevents'   => true,
		'no_found_rows'    => true,
	));

	//Make sure false and 'False' etc actually get parsed as 0/false (input from shortcodes, for instance, can be varied).
	//This maybe moved to the shortcode handler if this function is made public.
	if ( 'false' === strtolower( $query['showpastevents'] ) ) {
		$query['showpastevents'] = 0;
	}

	if ( ! empty( $query['numberposts'] ) ) {
		$query['posts_per_page'] = (int) $query['numberposts'];
	}

	$template = isset( $args['template'] ) ? $args['template'] :'';

	global $eo_event_loop,$eo_event_loop_args;
	$eo_event_loop_args = $args;
	$eo_event_loop = new WP_Query( $query );

	/**
	 * @ignore
	 * Try to find template - backwards compat. Don't use this filter. Will be removed!
	 */
	$template_file = apply_filters( 'eventorganiser_event_list_loop', false );
	$template_file = locate_template( $template_file );
	if ( $template_file || empty( $template ) ) {
		ob_start();
		if ( empty( $template_file ) ) {
			$template_file = eo_locate_template( array( $eo_event_loop_args['type'].'-event-list.php', 'event-list.php' ), true, false );
		} else {
			require( $template_file );
		}

		$html = ob_get_contents();
		ob_end_clean();

	} else {
		//Using the 'placeholder' template
		$no_events = isset( $args['no_events'] ) ? $args['no_events'] : '';

		$id        = ( ! empty( $args['id'] ) ? 'id="'.esc_attr( $args['id'] ).'"' : '' );
		$container = '<ul '.$id.' class="%2$s">%1$s</ul>';

		$html = '';
		if ( $eo_event_loop->have_posts() ) {
			while ( $eo_event_loop->have_posts() ) {
				$eo_event_loop->the_post();
				$event_classes = eo_get_event_classes();
				$html .= sprintf(
					'<li class="%2$s">%1$s</li>',
					EventOrganiser_Shortcodes::read_template( $template ),
					esc_attr( implode( ' ', $event_classes ) )
				);
			}
		} elseif ( $no_events ) {
			$html .= sprintf( '<li class="%2$s">%1$s</li>', $no_events, 'eo-no-events' );
		}

		$html = sprintf( $container, $html, esc_attr( $args['class'] ) );
	}

	wp_reset_postdata();

	if ( $echo ) {
		echo $html;
	}

	return $html;
}
add_action( 'widgets_init', array( 'EO_Event_List_Widget', 'register' ) );
