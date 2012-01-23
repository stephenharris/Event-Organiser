<?php 
/**
 * Class used to create the event calendar widget
 */
class EO_Events_Agenda_Widget extends WP_Widget
{
	var $w_arg = array(
		'title'=> '',
		);

	function EO_Events_Agenda_Widget()  {
		$widget_ops = array('classname' => 'widget_events', 'description' =>  __('Displays a list of events, grouped by date','eventorganiser'));
		$this->WP_Widget('EO_Events_Agenda_Widget', __('Events Agenda','eventorganiser'), $widget_ops);
  	}
 

	function form($instance)  {
		$instance = wp_parse_args( (array) $instance, $this->w_arg );
?>
	<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'eventorganiser'); ?>: </label>
		<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title'];?>" />
	</p>
  <?php
  }
 

  function update($new_instance, $old_instance){
    
	foreach($this->w_arg as $name => $val){
		if( empty($new_instance[$name]) ){
			$new_instance[$name] = $val;
		}
	}
	return $new_instance;
    }

 
 
  function widget($args, $instance){
	global $wp_locale;
	wp_enqueue_script( 'eo_front');
	wp_enqueue_style( 'eo_front');
	extract($args, EXTR_SKIP);

	//Echo widget
    	echo $before_widget;
    	echo $before_title;
	echo $instance['title'];
    	echo $after_title;
	echo "<div style='min-width:250px' class='eo-agenda-widget'>";
?>
	<div class='agenda-nav'>
		<span class="next button ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" role="button" title="">
			<span class="ui-button-icon-primary ui-icon ui-icon-carat-1-e"></span><span class="ui-button-text"></span>
		</span>
		<span class="prev button ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" role="button" title="">
			<span class="ui-button-icon-primary ui-icon ui-icon-carat-1-w"></span><span class="ui-button-text"></span>
		</span>
	</div>
<?php
	echo "<ul class='dates'>";
	echo '</ul>';//End dates
	echo "</div>";
?>
	<style>
		.eo-agenda-widget ul{
			list-style: none;
			margin:0;
		}	
		.eo-agenda-widget .agenda-nav{
			overflow:hidden;
		}	
		.eo-agenda-widget .next, .eo-agenda-widget .prev{
			float:right;
			padding: 5px 0px;
			background: #ececec;
			margin: 3px;
		}	
		.eo-agenda-widget ul.dates{
			border-bottom: 1px solid #ececec;
			font-weight:bold;
		}	
		.eo-agenda-widget ul.a-date{
			margin:0;
		}
		.eo-agenda-widget li.date{
			border-top: 1px solid #ececec;
			padding: 10px 0px;
		}	
		.eo-agenda-widget li.event{
			padding: 5px 0px 5px 10px;;
			font-weight:normal;
			background:#ececec;
			border-radius:3px;
			overflow:hidden;
			cursor:pointer;
			opacity:0.75;
			color:#333;
			margin:1px 0px;
			position:relative;
		}	
		.eo-agenda-widget li.event:hover{
			opacity:1;
			background:#ececec;
		}	
		.eo-agenda-widget li.event .cat{
			padding: 10px 3px;
			background: red;
			margin-right:5px;
			height:100%;
			position: absolute;
		   	 top: 0;
			left:0;
		}	
		.eo-agenda-widget li.event .meta{
			font-size:0.9em;
		}	
	</style>
<?php
  }
}
add_action( 'widgets_init', create_function('', 'return register_widget("EO_Events_Agenda_Widget");') );?>
