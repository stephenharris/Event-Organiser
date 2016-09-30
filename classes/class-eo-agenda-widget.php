<?php
/**
 * Class used to create the event calendar widget
 */
class EO_Events_Agenda_Widget extends WP_Widget{

	var $w_arg = array();

	static $agendas = array();

	function __construct() {
		$widget_ops = array( 'classname' => 'widget_events', 'description' => __( 'Displays a list of events, grouped by date', 'eventorganiser' ) );
		$this->w_arg = array(
			'title'         => '',
			'mode'          => 'day',
			'group_format'  => 'l, jS F',
			'item_format'   => get_option( 'time_format' ),
			'add_to_google' => 1,
		);
		parent::__construct( 'EO_Events_Agenda_Widget', __( 'Events Agenda','eventorganiser' ), $widget_ops );
	}

	/**
	 * Registers the widget with the WordPress Widget API.
	 *
	 * @return void.
	 */
	public static function register() {
		register_widget( __CLASS__ );
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->w_arg );
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'eventorganiser' ); ?>: </label>
		<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] );?>" />
		</p>
	
		<p>
		<label for="<?php echo $this->get_field_id( 'mode' ); ?>"><?php _e( 'Group by', 'eventorganiser' ); ?>: </label>
		<select id="<?php echo $this->get_field_id( 'mode' ); ?>" name="<?php echo $this->get_field_name( 'mode' ); ?>" >
			<option value="day" <?php selected( $instance['mode'], '' ); ?>><?php _e( 'Day','eventorganiser' ); ?> </option>
			<option value="week" <?php selected( $instance['mode'], 'week' ); ?>><?php _e( 'Week', 'eventorganiser' ); ?> </option>
			<option value="month" <?php selected( $instance['mode'], 'month' ); ?>><?php _e( 'Month', 'eventorganiser' ); ?> </option>
		</select>
		</p>
	
		<p>
		<label for="<?php echo $this->get_field_id( 'group_format' ); ?>"><?php _e( 'Group date format', 'eventorganiser' ); ?>: </label>
		<input id="<?php echo $this->get_field_id( 'group_format' ); ?>" name="<?php echo $this->get_field_name( 'group_format' ); ?>" type="text" value="<?php echo esc_attr( $instance['group_format'] );?>" />
		</p>
	
		<p>
		<label for="<?php echo $this->get_field_id( 'item_format' ); ?>"><?php _e( 'Event date/time format', 'eventorganiser' ); ?>: </label>
		<input id="<?php echo $this->get_field_id( 'item_format' ); ?>" name="<?php echo $this->get_field_name( 'item_format' ); ?>" type="text" value="<?php echo esc_attr( $instance['item_format'] );?>" />
		</p>
	
		<p>
		<label for="<?php echo $this->get_field_id( 'add_to_google' ); ?>"><?php _e( 'Include \'Add To Google\' link','eventorganiser' ); ?>: </label>
		<input id="<?php echo $this->get_field_id( 'add_to_google' ); ?>" name="<?php echo $this->get_field_name( 'add_to_google' ); ?>" type="checkbox" value="1" <?php checked( $instance['add_to_google'], 1 );?> />
		</p>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$validated = array();
		delete_transient( 'eo_widget_agenda' );
		$validated['title']         = sanitize_text_field( $new_instance['title'] );
		$validated['mode']          = sanitize_text_field( $new_instance['mode'] );
		$validated['group_format']  = sanitize_text_field( $new_instance['group_format'] );
		$validated['item_format']   = sanitize_text_field( $new_instance['item_format'] );
		$validated['add_to_google'] = intval( $new_instance['add_to_google'] );
		return $validated;
	}

	function widget( $args, $instance ) {

		wp_enqueue_script( 'eo_front' );
		eo_enqueue_style( 'eo_front' );

		add_action( 'wp_footer', array( __CLASS__, 'add_options_to_script' ) );

		self::$agendas[$args['widget_id']] = array(
			'id'            => esc_attr( $args['widget_id'] ),
			'number'        => $this->number,
			'mode'          => isset( $instance['mode'] ) ? $instance['mode'] : 'day',
			'add_to_google' => $instance['add_to_google'],
			'group_format'  => eo_php_to_moment( $instance['group_format'] ),
			'item_format'   => eo_php_to_moment( $instance['item_format'] ),
		);

		//Echo widget
		echo $args['before_widget'];

		$widget_title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		if ( $widget_title ) {
			echo $args['before_title'] . esc_html( $widget_title ) . $args['after_title'];
		}

		printf( '<div data-eo-agenda-widget-id="%1$s" id="%1$s_container" class="eo-agenda-widget"></div>', esc_attr( $args['widget_id'] ) );

		echo $args['after_widget'];
	}

	static function print_main_template() {
		?>
  		<script type="text/template" id="eo-tmpl-agenda-widget">
		<div class='eo-agenda-widget-nav'>
			<span class="eo-agenda-widget-nav-prev"><</span>
			<span class="eo-agenda-widget-nav-next">></span>
		</div>
		<ul class='dates'></ul>
		</script>
  		<?php
	}

	static function print_group_template() {
		?>
	  	<script type="text/template" id="eo-tmpl-agenda-widget-group">
		<li class="date">
			{{{ group.start.format(this.param.group_format) }}}
			<ul class="a-date"></ul>
		</li>
		</script>
	  	<?php
	}

	static function print_item_template() {
		?>
		<script type="text/template" id="eo-tmpl-agenda-widget-item">
		<li class="event">
			<# if( !this.param.add_to_google ){ #>
				<a class='eo-agenda-event-permalink' href='{{{ event.link }}}'>
			<# } #>
			<span class="cat" style="background:{{{ event.color }}}"></span>
			<span><strong>
				<# if( event.all_day ){ #>
					<?php esc_html_e( 'All day', 'eventorganiser' ); ?>
				<# }else{ #>
					{{{ event.start.format(this.param.item_format) }}}
				<# } #>
			</strong></span>
			{{{ event.title }}}		
			<# if( this.param.add_to_google ){ #>		
				<div class="meta" style="display:none;">
					<span>
						<a href="{{{ event.link }}}"><?php esc_html_e( 'View', 'eventorganiser' ); ?></a>
					</span>
					<span> &nbsp; </span>
					<span>
						<a href="{{{ event.google_link }}}" target="_blank"><?php esc_html_e( 'Add To Google Calendar', 'eventorganiser' ); ?></a>
					</span>
				</div>
			<# } #>
			<# if( !this.param.add_to_google ){ #>
				</a>
			<# } #>
		</li>
		</script>
	 	<?php
	}

	static function add_options_to_script() {
		if ( ! empty( self::$agendas ) ) {
			wp_localize_script( 'eo_front', 'eo_widget_agenda', self::$agendas );
			self::print_main_template();
			self::print_group_template();
			self::print_item_template();
		}
	}
}
add_action( 'widgets_init', array( 'EO_Events_Agenda_Widget', 'register' ) );
