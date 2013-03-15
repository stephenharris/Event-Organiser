<?php
/**
 * Event categories widget class
 *
 * @since 1.8
 */
class EO_Widget_Categories extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'eo__event_categories', 'description' => __( "A list or dropdown of event categories" ) );
		parent::__construct('eo-event-categories', __( 'Event Categories', 'eventorganiser' ), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );
		
		$taxonomy = 'event-category';

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Categories' ) : $instance['title'], $instance, $this->id_base);
		$h = ! empty( $instance['hierarchical'] ) ? '1' : '0';
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
	var event_dropdown = document.getElementById("eo-event-cat");
	function eventorganiserDropdownChange() {
		if ( event_dropdown.options[event_dropdown.selectedIndex].value != -1 ) {
			location.href = "<?php echo home_url().'/'.$taxonomy.'=';?>"+event_dropdown.options[event_dropdown.selectedIndex].value;
		}
	}
	event_dropdown.onchange = eventorganiserDropdownChange;
/* ]]> */
</script>

<?php
		} else {
?>
		<ul>
<?php
		$cat_args['title_li'] = '';
		wp_list_categories(apply_filters('eventorganiser_widget_event_categories_args', $cat_args));
?>
		</ul>
<?php
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
		$instance['dropdown'] = !empty($new_instance['dropdown']) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = esc_attr( $instance['title'] );
		$hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
		$dropdown = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>"<?php checked( $dropdown ); ?> />
		<label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e( 'Display as dropdown' ); ?></label><br />

		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>"<?php checked( $hierarchical ); ?> />
		<label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e( 'Show hierarchy' ); ?></label></p>
<?php
	}

}
