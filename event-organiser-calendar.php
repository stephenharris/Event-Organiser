<?php
/****** CALENDAR PAGE ******/
$calendar_page = new EventOrganiser_Calendar_Page();
class EventOrganiser_Calendar_Page extends EventOrganiser_Admin_Page
{
	function set_constants(){
		$this->hook = 'edit.php?post_type=event';
		$this->title =  __('Calendar View','eventorganiser');
		$this->menu =__('Calendar View','eventorganiser');
		$this->permissions ='edit_events';
		$this->slug ='calendar';
	}

	function page_scripts(){
	  	$eo_settings_array= get_option('eventorganiser_options'); 
		$EO_Venues = new EO_Venues;
		$EO_Venues->query();
		add_thickbox();
		global $wp_locale;

		$terms =get_terms( 'event-category', array('hide_empty' => 0));

		wp_enqueue_script("eo_calendar",true);
		wp_enqueue_script("eo_event",true);
		wp_localize_script( 'eo_event', 'EO_Ajax_Event', array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'startday'=>intval(get_option('start_of_week')),
			'format'=> $eo_settings_array['dateformat'].'-yy'
			));
		wp_localize_script( 'eo_calendar', 'EO_Ajax', array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'startday'=>intval(get_option('start_of_week')),
			'format'=> $eo_settings_array['dateformat'].'-yy',
			'perm_edit'=> current_user_can('edit_events'),
			'categories'=>$terms,
			'venues'=>$EO_Venues->results,		
			'locale'=>array(
				'monthNames'=>array_values($wp_locale->month),
				'monthAbbrev'=>array_values($wp_locale->month_abbrev),
				'dayNames'=>array_values($wp_locale->weekday),
				'dayAbbrev'=>array_values($wp_locale->weekday_abbrev),
				'today'=>__('today','eventorganiser'),
				'day'=>__('day','eventorganiser'),
				'week'=>__('week','eventorganiser'),
				'month'=>__('month','eventorganiser'),
				'gotodate'=>__('go to date','eventorganiser'),
				'cat'=>__('View all categories','eventorganiser'),
				'venue'=>__('View all venues','eventorganiser'),
				)
			));
		wp_enqueue_style('eo_calendar-style');
		wp_enqueue_style('eventorganiser-style');
	}

	function page_styles(){
		$terms =get_terms( 'event-category', array('hide_empty' => 0));
		if($terms && function_exists('eo_get_category_color')):
			echo "<style>";
			foreach($terms as $term):
				echo ".cat-slug-".$term->slug." span.ui-selectmenu-item-icon{
					background: ".eo_get_category_color($term).";
				} 
				";	
			endforeach;
			echo "</style>";
		endif;
	}

	function page_actions(){

		global $wpdb, $eventorganiser_events_table;

		//Check action
		if(!empty($_REQUEST['save'])|| !empty($_REQUEST['publish'])){

			//Check nonce
			check_admin_referer('eventorganiser_calendar_save');

			//authentication checks
			if (!current_user_can('edit_events')) 
				wp_die( __('You do not have sufficient permissions to create events') );

			$input = $_REQUEST['eo_event']; //Retrieve input from posted data
			
			//Set the status of the new event
			if(!empty($_REQUEST['save'])):
				$status='draft';
			else:
				if(current_user_can('publish_events'))
					$status='publish';
				else
					$status='pending';
			endif;
	
			//Set post and event details
			$input['occurrence']='once';
			$input['YmdFormated']= true;
		
			$post_input = array(
				'post_title' =>$input['event_title'],
				'post_status' => $status,
				'post_content'=>$input['event_content'],
				'post_type' => 'event',
			);

			//Insert event
			$post_id = EO_Event::insertNewEvent($post_input,$input);

			if($post_id){
				//If event was successfully inserted, redirect and display appropriate message
				$redirect = get_edit_post_link($post_id,'');

				if($status=='publish')
					$redirect=add_query_arg('message',6, $redirect);
				else
					$redirect=add_query_arg('message',7, $redirect);
				
				//Redirect to event admin page & exit
				wp_redirect($redirect);
				exit; 
			}

		}elseif(isset($_REQUEST['action'])&& $_REQUEST['action'] =='delete_occurrence' && isset($_REQUEST['series']) && isset($_REQUEST['event'])){

			$post_id = intval($_REQUEST['series']);
			$event_id = intval($_REQUEST['event']);

			//Check nonce
			check_admin_referer('eventorganiser_delete_occurrence_'.$event_id);

			if (!current_user_can('delete_event', $post_id))
				wp_die( __('You do not have sufficient permissions to delete this event') );

			$del = $wpdb->get_results($wpdb->prepare("DELETE FROM $eventorganiser_events_table WHERE post_id=%d AND event_id=%d",$post_id,$event_id));

			global $EO_Errors;
			$EO_Errors = new WP_Error();
			$EO_Errors->add('eo_notice', '<strong>'.__("Occurrence deleted.",'eventorganiser').'</strong>');
		}
	}
	
	function display(){
		//Get the time 'now' according to blog's timezone
		 $now = new DateTIme(null,EO_Event::get_timezone());
		$AllVenues = new EO_Venues;
		$AllVenues->query();
	?>

	<div class="wrap">  
		<div id='icon-edit' class='icon32'><br/>
		</div>
		<h2><?php _e('Events Calendar', 'eventorganiser'); ?></h2>

		<div id="calendar-view">
			<span id='loading' style='display:none'><?php _e('Loading&#8230;');?></span>
			<a href="" class="view-button" id="agendaDay"><?php _e('Day','eventorganiser');?> </a>
			<a href="" class="view-button" id="agendaWeek"><?php _e('Week','eventorganiser');?></a>
			<a href="" class="view-button active" id="month"><?php _e('Month','eventorganiser');?> </a>
		</div>
		<div id='eo_admin_calendar'></div>
		<span><?php _e('Current date/time','eventorganiser');?>: <?php echo $now->format('Y-m-d G:i:s \G\M\TP');?></span>
		<div id='events-meta' class="thickbox"></div>

		<?php if(current_user_can('publish_events')||current_user_can('edit_events')):?>
			<div id='eo_event_create_cal' style="display:none;" class="thickbox">
			<form name="eventorganiser_calendar" method="post" class="eo_cal">
				<table>
				<tr>
					<th><?php _e('When','eventorganiser');?>: </th>
					<td id="date"></td>
				</tr>
				<tr>
					<th><?php _e('Event Title','eventorganiser');?>: </th>
					<td><input name="eo_event[event_title]" size="30"></input></td>
				</tr>
				<tr>
					<th><?php _e('Where','eventorganiser');?>: </th>
					<td><!-- If javascript is disabed, a simple drop down menu box is displayed to choose venue.
				Otherwise, the user is able to search the venues by typing in the input box.-->		
					<select size="30" id="venue_select" name="eo_event[venue_id]">
					<option>Select a venue </option>
					<?php foreach ($AllVenues->results as $thevenue):?>
						<option value="<?php echo intval($thevenue['venue_id']);?>"><?php echo $thevenue['venue_name']; ?></option>
					<?php endforeach;?>
					</select>
					</td>
				</tr>
				<tr>
					<th></th>
					<td><textarea cols="30" rows="4" name="eo_event[event_content]"></textarea></td>
				</tr>
				</table>
				<input type="hidden" name="eo_event[StartDate]">
				<input type="hidden" name="eo_event[EndDate]">
				<input type="hidden" name="eo_event[StartTime]">
				<input type="hidden" name="eo_event[FinishTime]">
				<input type="hidden" name="eo_event[allday]">
		  		<?php wp_nonce_field('eventorganiser_calendar_save'); ?>
			<?php if(current_user_can('publish_events')):?>
					<p class="submit">	
						<input type="reset" class="button" id="reset" value="Cancel">
						<input type="submit" class="button button-highlighted" tabindex="4" value="<?php _e('Save Draft','eventorganiser');?>"" id="event-draft" name="save">
					<span class="eo_alignright">
						<input type="submit" accesskey="p" tabindex="5" value="<?php _e('Publish Event','eventorganiser');?>" class="button-primary" id="publish" name="publish">
					</span>
					<br class="clear">
					</p>
			<?php elseif(current_user_can('edit_events')):?>
				<p class="submit">	
					<input type="reset" class="button" id="reset" value="<?php _e('Cancel','eventorganiser');?>">
					<input type="submit" accesskey="p" tabindex="5" value="<?php _e('Submit for Review','eventorganiser');?>" class="eo_alignright button-primary" id="submit-for-review" name="publish">
				<br class="clear">
				</p>
			<?php endif; ?>
			</form>
		</div>
		<?php endif; ?>
	</div><!-- .wrap -->
<?php
	}
}
?>
