<?php
/**
 * Event venues widget class
 *
 * @since 1.8
 */
class EO_Widget_Venues extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'eo__event_venues', 'description' => __( "A list or dropdown of event venues" ) );
		parent::__construct('eo-event-venues', __( 'Event Venues', 'eventorganiser' ), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );
		
		$taxonomy = 'event-venue';

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Venues' ) : $instance['title'], $instance, $this->id_base);
		$d = ! empty( $instance['dropdown'] ) ? '1' : '0';

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		
		//Select current category by default
		if( is_tax( $taxonomy ) ){
			$term = get_term( get_queried_object_id() , $taxonomy );
			$selected = ( $term && !is_wp_error( $term ) ? $term->slug : false );
		}else{
			$selected = false;
		}

		$cat_args = array(
				'orderby' => 'name', 
				'hierarchical' => false, 
				'taxonomy' => $taxonomy, 
				'id' => 'eo-event-venue',
				'selected' => $selected
				 );
		if ( $d ) {
			$cat_args['walker'] = new EO_Walker_TaxonomyDropdown();
			$cat_args['value'] = 'slug';
			$cat_args['show_option_none'] = __('Select Category');
			wp_dropdown_categories(apply_filters('eventorganiser_widget_event_venues_dropdown_args', $cat_args));
			?>

<script type='text/javascript'>
/* <![CDATA[ */
	var event_venue_dropdown = document.getElementById("eo-event-venue");
	function eventorganiserVenueDropdownChange() {
		console.log( event_venue_dropdown.options[event_venue_dropdown.selectedIndex].value);
		if ( event_venue_dropdown.options[event_venue_dropdown.selectedIndex].value != -1 ) {
			location.href = "<?php echo home_url().'/?'.$taxonomy.'=';?>"+event_venue_dropdown.options[event_venue_dropdown.selectedIndex].value;
		}
	}
	event_venue_dropdown.onchange = eventorganiserVenueDropdownChange;
/* ]]> */
</script>

<?php
		} else {
?>
		<ul>
<?php
		$cat_args['title_li'] = '';
		wp_list_categories(apply_filters('eventorganiser_widget_event_venues_args', $cat_args));
?>
		</ul>
<?php
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = esc_attr( $instance['title'] );
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>"<?php checked( $dropdown ); ?> />
		<label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e( 'Display as dropdown' ); ?></label><br />
<?php
	}

}
