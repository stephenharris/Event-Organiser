<?php
/**
 * Class used to create the event list widget
 */
class EO_Event_List_Widget extends WP_Widget{

	var $w_arg = array(
		'title'=> 'Events',
		'numberposts'=> 5,
		'event-category'=> '',
		'venue_id'=> NULL,
		'venue'=> '',
		'orderby'=> 'eventstart',
		'showpastevents'=> 0,
		'group_events_by'=>'',
		'order'=> 'ASC'
		);

  function EO_Event_List_Widget(){
	load_plugin_textdomain( 'eventorganiser', false, EVENT_ORGANISER_I18N);
	$widget_ops = array('classname' => 'EO_Event_List_Widget', 'description' => __('Displays a list of events','eventorganiser') );
	$this->WP_Widget('EO_Event_List_Widget', __('Events','eventorganiser'), $widget_ops);
  }
 
  function form($instance){	
	$instance = wp_parse_args( (array) $instance, $this->w_arg );
  ?>
<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'eventorganiser'); ?>: </label>
	<input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" />
</p>
  <p>
  <label for="<?php echo $this->get_field_id('numberposts'); ?>"><?php _e('Number of events','eventorganiser');?>:   </label>
	  <input id="<?php echo $this->get_field_id('numberposts'); ?>" name="<?php echo $this->get_field_name('numberposts'); ?>" type="number" size="3" value="<?php echo $instance['numberposts'];?>" />
</p>
  <p>
  <label for="<?php echo $this->get_field_id('event-category'); ?>"><?php _e('Event categories', 'eventorganiser'); ?>:   </label>
  <input  id="<?php echo $this->get_field_id('event-category'); ?>" class="widefat" name="<?php echo $this->get_field_name('event-category'); ?>" type="text" value="<?php echo $instance['event-category'];?>" />
   <em><?php _e('List category slug(s), seperate by comma. Leave blank for all', 'eventorganiser'); ?> </em>
</p>
  <p>
  <label for="<?php echo $this->get_field_id('venue'); ?>"><?php _e('Venue', 'eventorganiser'); ?>:   </label>
	<?php 	$venues = new EO_Venues;
			$venues->query();?>
	<select id="<?php echo $this->get_field_id('venue'); ?>" name="<?php echo $this->get_field_name('venue'); ?>" type="text">
		<option value="" <?php selected($instance['venue'], ''); ?>><?php _e('All Venues','eventorganiser'); ?> </option>
		<?php foreach ($venues->results as $thevenue):?>
			<option <?php  selected($instance['venue'],$thevenue['venue_slug']);?> value="<?php echo $thevenue['venue_slug'];?>"><?php echo $thevenue['venue_name']; ?></option>
		<?php endforeach;?>
	</select>
</p>

  <p>
  <label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('Order by', 'eventorganiser'); ?></label>
	<select id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>" type="text">
		<option value="eventstart" <?php selected($instance['orderby'], 'eventstart'); ?>><?php _e('Start date', 'eventorganiser'); ?></option>
		<option value="title" <?php selected($instance['orderby'], 'title');?>><?php _e('Title', 'eventorganiser'); ?> </option>
	</select>
	<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>" type="text">
		<option value="asc" <?php selected($instance['order'], 'asc'); ?>><?php _e('ASC', 'eventorganiser'); ?> </option>
		<option value="desc" <?php selected($instance['order'], 'desc');?>><?php _e('DESC', 'eventorganiser'); ?> </option>
	</select>
</p>
  <p>
    <label for="<?php echo $this->get_field_id('showpastevents'); ?>"><?php _e('Include past events', 'eventorganiser'); ?>  </label>
	<input type="checkbox" id="<?php echo $this->get_field_id('showpastevents'); ?>" name="<?php echo $this->get_field_name('showpastevents'); ?>" <?php checked($instance['showpastevents'],1);?> value="1" />
</p>
  <p>
    <label for="<?php echo $this->get_field_id('group_events_by'); ?>"><?php _e('Group occurrences', 'eventorganiser'); ?>  </label>
	<input type="checkbox" id="<?php echo $this->get_field_id('group_events_by'); ?>" value="series" name="<?php echo $this->get_field_name('group_events_by'); ?>" <?php checked($instance['group_events_by'],'series');?> />
  </p>
<?php
  }
 

  function update($new_instance, $old_instance){  
	foreach($this->w_arg as $name => $val){
		if( empty($new_instance[$name]))
			$new_instance[$name] = $val;
    	}
	return $new_instance;
    }

 
 
  function widget($args, $instance){
	extract($args, EXTR_SKIP);

	$events = eo_get_events($instance);
	
    	echo $before_widget;
    	echo $before_title;
	echo $instance['title'];
    	echo $after_title;

	if($events):	
		echo '<ul class="eo-events eo-events-widget">';
		foreach ($events as $event):
			//Check if all day, set format accordingly
			if($event->event_allday){
				$format = get_option('date_format');
			}else{
				$format = get_option('date_format').'  '.get_option('time_format');
			}
			echo '<li><a title="'.$event->post_title.'" href="'.get_permalink($event->ID).'">'.$event->post_title.'</a> '.__('on','eventorganiser').' '.eo_format_date($event->StartDate.' '.$event->StartTime, $format).'</li>';
		endforeach;
		echo '</ul>';
	endif;
     	echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("EO_Event_List_Widget");') );?>
