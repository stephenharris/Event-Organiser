<?php
/****** CALENDAR PAGE ******/
if(!class_exists('EventOrganiser_Admin_Page')){
    require_once(EVENT_ORGANISER_DIR.'classes/class-eventorganiser-admin-page.php' );
}
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
		global $wp_locale;
	  	$eo_settings_array= get_option('eventorganiser_options'); 

		$cats =get_terms( 'event-category', array('hide_empty' => 0));
		$venues =get_terms( 'event-venue', array('hide_empty' => 0));

		add_thickbox();
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
			'categories'=>$cats,
			'venues'=>$venues,
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
			$venue = (int) $input['venue_id'];

			$post_input = array(
				'post_title' =>$input['event_title'],
				'post_status' => $status,
				'post_content'=>$input['event_content'],
				'post_type' => 'event',
				'tax_input' => array('event-venue'=>array($venue))
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

		}elseif(isset($_REQUEST['action'])&& ($_REQUEST['action'] =='delete_occurrence' || $_REQUEST['action'] =='break_series') && isset($_REQUEST['series']) && isset($_REQUEST['event'])){

			$post_id = intval($_REQUEST['series']);
			$event_id = intval($_REQUEST['event']);
			$action = $_REQUEST['action'];

			if($action=='break_series'):

				//Check nonce
				check_admin_referer('eventorganiser_break_series_'.$event_id);

				//Check permissions
				if (!current_user_can('edit_event', $post_id) || !current_user_can('delete_event', $post_id) )
					wp_die( __('You do not have sufficient permissions to edit this event','eventorganiser') );
		
				//Get current event
				$query_array= array( 
					'event_occurrence_id'=>$event_id,
					'posts_per_page'=>-1,
					'post_type'=>'event',
					'showpastevents'=>true,
					'perm' => 'readable');

				$query = new WP_Query($query_array );

				global $post;
				if($query->have_posts()):	
					$query->the_post();

					//Assign new event taxonomy terms
					$taxs = array('event-category','event-tag','event-venue');
					$tax_input = array();
					foreach($taxs as $tax):
						$terms = get_the_terms($post->ID, $tax);
						if($terms &&  !is_wp_error($terms)){
							$tax_input[$tax] = array_map('intval', wp_list_pluck($terms, 'term_id'));
						}
					endforeach;

					//Post details
					$post_array=array(
						'post_title' => $post->post_title,
					  	'post_name' =>$post->post_name,
						'post_author' => $post->post_author,
						'post_content' => $post->post_content,
						'post_status' => $post->post_status,
						'post_date' => $post->post_date,
					  	'post_date_gmt' => $post->post_date_gmt,
					  	'post_excerpt' =>$post->post_excerpt,
						'post_password' => $post->post_password,
						'post_type' => 'event',
						'tax_input' =>$tax_input,
						'comment_status' => $post->comment_status,
						'ping_status' => $post->ping_status
					);  

					//Event details
					$tz = EO_Event::get_timezone(); //blog timzone
					$event_array = array(
						'dateObjects'=>true,
						'start'=>new DateTIme($post->StartDate.' '.$post->StartTime,$tz),
						'end'=>new DateTIme($post->EndDate.' '.$post->FinishTime, $tz),
						'allday'=>$post->event_allday,
						'schedule'=>'once',
						'frequency'=>1,
						'venue'=>$post->Venue,
					);

					//Create new event with duplicated details
					$new_event_id = EO_Event::insertNewEvent($post_array,$event_array);

					//delete occurrence, 
					if($new_event_id){
						$del = $wpdb->get_results($wpdb->prepare("DELETE FROM $eventorganiser_events_table WHERE post_id=%d AND event_id=%d",$post_id,$event_id));

						$post_custom = get_post_custom($post->ID);
						foreach ($post_custom as $meta_key=>$meta_values) {
							$unique = ($meta_key[0]=='_' ? true : false);
							foreach ($meta_values as $meta_value) {
								add_post_meta($new_event_id,$meta_key,$meta_value,$unique);
							}
						}
					}
					//Redirect to prevent resubmisson
					$redirect = add_query_arg(array('post_type'=>'event','page'=>'calendar'),admin_url('edit.php'));
					wp_redirect($redirect);
				endif;

			elseif($action=='delete_occurrence'):

				//Check nonce
				check_admin_referer('eventorganiser_delete_occurrence_'.$event_id);

				//Check permissions
				if (!current_user_can('delete_event', $post_id))
					wp_die( __('You do not have sufficient permissions to delete this event','eventorganiser') );

				$del = $wpdb->get_results($wpdb->prepare("DELETE FROM $eventorganiser_events_table WHERE post_id=%d AND event_id=%d",$post_id,$event_id));

				global $EO_Errors;
				$EO_Errors = new WP_Error();
				$EO_Errors->add('eo_notice', '<strong>'.__("Occurrence deleted.",'eventorganiser').'</strong>');
			endif;
		}
	}
	
	function display(){
		//Get the time 'now' according to blog's timezone
		 $now = new DateTIme(null,EO_Event::get_timezone());
		$venues = get_terms('event-venue', array('hide_empty'=>false));
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
					<?php foreach ($venues as $venue):?>
						<option value="<?php echo intval($venue->term_id);?>"><?php echo esc_html($venue->name); ?></option>
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
$calendar_page = new EventOrganiser_Calendar_Page();
?>
