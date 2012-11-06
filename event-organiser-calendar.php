<?php
/**
 * Calendar Admin Page
 */
if(!class_exists('EventOrganiser_Admin_Page')){
    require_once(EVENT_ORGANISER_DIR.'classes/class-eventorganiser-admin-page.php' );
}
/**
 * Calendar Admin Page
 * 
 * Extends the EentOrganiser_Admin_Page class. Creates the calendar admin page
 * @version 1.0
 * @see EventOrganiser_Admin_Page
 * @package event organiser
 */
class EventOrganiser_Calendar_Page extends EventOrganiser_Admin_Page
{
    /**
     * This sets the calendar page variables
     */
	function set_constants(){
		$this->hook = 'edit.php?post_type=event';
		$this->title =  __('Calendar View','eventorganiser');
		$this->menu =__('Calendar View','eventorganiser');
		$this->permissions ='edit_events';
		$this->slug ='calendar';
	}
        
    /**
     * Enqueues the page's scripts and styles, and localises them.
     */
	function page_scripts(){
		global $wp_locale;
		
		$screen = get_current_screen();
		$cats =get_terms( 'event-category', array('hide_empty' => 0));
		$venues =get_terms( 'event-venue', array('hide_empty' => 0));

		wp_enqueue_script("eo_calendar",true);
		wp_enqueue_script("eo_event",true);
		wp_localize_script( 'eo_event', 'EO_Ajax_Event', array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'startday'=>intval(get_option('start_of_week')),
			'format'=> eventorganiser_get_option('dateformat').'-yy'
			));
		wp_localize_script( 'eo_calendar', 'EO_Ajax', array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'startday'=>intval(get_option('start_of_week')),
			'format'=> eventorganiser_get_option('dateformat').'-yy',
			'timeFormat'=> ( $screen->get_option('eofc_time_format','value') ? 'h:mmtt' : 'HH:mm'),
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

    /**
     * Prints page styles
     */
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

		//Add screen option
		$user =wp_get_current_user();
		$is12hour = get_user_meta($user->ID,'eofc_time_format',true);
		add_screen_option('eofc_time_format',array('value'=>$is12hour));
		add_filter('screen_settings', array($this,'screen_options'), 10, 2);

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
				$status = (current_user_can('publish_events') ? 'publish' : 'pending');
			endif;
	
			//Set post and event details
			$venue = (int) $input['venue_id'];

			$post_input = array(
				'post_title' =>$input['event_title'],
				'post_status' => $status,
				'post_content'=>$input['event_content'],
				'post_type' => 'event',
				'tax_input' => array('event-venue'=>array($venue))
			);
			$tz = eo_get_blog_timezone();
			$event_data=array(
				'schedule'=>'once',
				'all_day'=>$input['allday'],
				'start'=>new DateTime($input['StartDate'].' '.$input['StartTime'],$tz),
				'end'=>new DateTime($input['EndDate'].' '.$input['FinishTime'], $tz),
			);

			//Insert event
			$post_id = eo_insert_event($post_input,$event_data);

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
		
				//Get current event.
				$query = new WP_Query( array( 
					'event_occurrence_id'=>$event_id,
					'posts_per_page'=>-1,
					'post_type'=>'event',
					'showpastevents'=>true,
					'perm' => 'readable'));

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
						'post_title' => $post->post_title,'post_name' =>$post->post_name,'post_author' => $post->post_author,
						'post_content' => $post->post_content,'post_status' => $post->post_status,'post_date' => $post->post_date,
					  	'post_date_gmt' => $post->post_date_gmt,'post_excerpt' =>$post->post_excerpt,'post_password' => $post->post_password,
						'post_type' => 'event','tax_input' =>$tax_input,'comment_status' => $post->comment_status,'ping_status' => $post->ping_status);  

					//Event details
					$event_array = array(
						'start'=> new DateTime(trim($post->StartDate).' '.trim($post->StartTime), eo_get_blog_timezone()),
						'end'=>new DateTime(trim($post->EndDate).' '.trim($post->FinishTime), eo_get_blog_timezone()),
						'all_day'=> (eo_is_all_day($post_id) ? 1 : 0),
						'schedule'=>'once',
						'frequency'=>1,
					);

					//Create new event with duplicated details
					$new_event_id = eo_insert_event($post_array,$event_array);

					//delete occurrence, 
					if( $new_event_id && !is_wp_error($new_event_id) ){
						$response = _eventorganiser_remove_occurrence($post_id,$event_id);
						//FIXME copying post meta over-rides schedule information.
						/*$post_custom = get_post_custom($post->ID);
						foreach ($post_custom as $meta_key=>$meta_values) {
							$unique = ($meta_key[0]=='_' ? true : false);
							foreach ($meta_values as $meta_value) {
								add_post_meta($new_event_id,$meta_key,$meta_value,$unique);
							}
						}*/
					}
					//Redirect to prevent resubmisson
					$redirect = add_query_arg(array('post_type'=>'event','page'=>'calendar'),admin_url('edit.php'));
					wp_redirect($redirect);
				endif;

			elseif($action=='delete_occurrence'):
				global $EO_Errors;

				//Check nonce
				check_admin_referer('eventorganiser_delete_occurrence_'.$event_id);

				//Check permissions
				if (!current_user_can('delete_event', $post_id))
					wp_die( __('You do not have sufficient permissions to delete this event','eventorganiser') );

				$response = _eventorganiser_remove_occurrence($post_id,$event_id);

				//Break Cache!
				_eventorganiser_delete_calendar_cache();

				if( is_wp_error($response) ){
					$EO_Errors =$response;
				}else{
					$EO_Errors = new WP_Error('eo_notice', '<strong>'.__("Occurrence deleted.",'eventorganiser').'</strong>');
				}
			endif;
		}
	}


	function screen_options($options, $screen){
		$options .= '<h5>'.__('Calendar options','eventorganiser').'</h5>';
		$options .= sprintf('<p><label for="%s" style="line-height: 20px;"> <input type="checkbox" name="%s" id="%s" %s> %s </label></p>',
						'eofc_time_format',
						'eofc_time_format',
						'eofc_time_format',
						checked($screen->get_option('eofc_time_format','value'),0,false),
						__('24 hour time', 'eventorganiser')
       					 );
		return $options;
	}


	
	function display(){
		//Get the time 'now' according to blog's timezone
		 $now = new DateTIme(null,eo_get_blog_timezone());
		$venues = eo_get_venues();
	?>

	<div class="wrap">  
		<?php screen_icon('edit');?>
		<h2><?php _e('Events Calendar', 'eventorganiser'); ?></h2>

		<div id="calendar-view">
			<span id='loading' style='display:none'><?php _e('Loading&#8230;');?></span>
			<a href="" class="view-button" id="agendaDay"><?php _e('Day','eventorganiser');?> </a>
			<a href="" class="view-button" id="agendaWeek"><?php _e('Week','eventorganiser');?></a>
			<a href="" class="view-button active" id="month"><?php _e('Month','eventorganiser');?> </a>
		</div>
		<div id='eo_admin_calendar'></div>
		<span><?php _e('Current date/time','eventorganiser');?>: <?php echo $now->format('Y-m-d G:i:s \G\M\TP');?></span>

		<?php eventorganiser_event_detail_dialog(); ?>

		<?php if(current_user_can('publish_events')||current_user_can('edit_events')):?>
			<div id='eo_event_create_cal' style="display:none;" class="eo-dialog" title="<?php esc_attr_e('Create an event','eventorganiser'); ?>">
			<form name="eventorganiser_calendar" method="post" class="eo_cal">

				<table class="form-table">
				<tr>
					<th><?php _e('When','eventorganiser');?>: </th>
					<td id="date"></td>
				</tr>
				<tr>
					<th><?php _e('Event Title','eventorganiser');?>: </th>
					<td><input name="eo_event[event_title]" class="eo-event-title ui-autocomplete-input ui-widget-content ui-corner-all" ></td>
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
					<td><textarea rows="4" name="eo_event[event_content]" style="width: 220px;"></textarea></td>
				</tr>
				</table>
			<p class="submit">
			<input type="hidden" name="eo_event[StartDate]">
			<input type="hidden" name="eo_event[EndDate]">
			<input type="hidden" name="eo_event[StartTime]">
			<input type="hidden" name="eo_event[FinishTime]">
			<input type="hidden" name="eo_event[allday]">
		  	<?php wp_nonce_field('eventorganiser_calendar_save'); ?>
			<?php if(current_user_can('publish_events')):?>
				<input type="submit" class="button" tabindex="4" value="<?php _e('Save Draft','eventorganiser');?>"" id="event-draft" name="save">
				<input type="reset" class="button" id="reset" value="Cancel">

				<span id="publishing-action">
					<input type="submit" accesskey="p" tabindex="5" value="<?php _e('Publish Event','eventorganiser');?>" class="button-primary" id="publish" name="publish">
				</span>

			<?php elseif(current_user_can('edit_events')):?>
				<input type="reset" class="button" id="reset" value="<?php _e('Cancel','eventorganiser');?>">
				<span id="publishing-action">
					<input type="submit" accesskey="p" tabindex="5" value="<?php _e('Submit for Review','eventorganiser');?>" class="eo_alignright button-primary" id="submit-for-review" name="publish">
				</span>
			<?php endif; ?>
			
			<br class="clear">
			</form>
		</div>
		<?php endif; ?>
	</div><!-- .wrap -->
<?php
	}
}
$calendar_page = new EventOrganiser_Calendar_Page();


	function eventorganiser_event_detail_dialog(){

		$tabs = apply_filters('eventorganiser_calendar_dialog_tabs', array( 'summary'=>__('Event Details','eventorganiser')));
	
		printf("<div id='events-meta' class='eo-dialog' style='display:none;' title='%s'>",esc_attr__('Event Detail','eventorganiser'));
			echo "<div id='eo-dialog-tabs'>";
					echo "<ul style='position: relative;'>";
					foreach( $tabs as $id => $label ){
						printf("<li><a href='#%s'>%s</a></li>", esc_attr('eo-dialog-tab-'.$id), esc_html($label));
					}
					echo "</ul>";
					foreach( $tabs as $id=> $label){
						printf("<div id='%s'> </div>",esc_attr('eo-dialog-tab-'.$id));
					}
			echo "</div>";
		echo "</div>";
	?>
<style>
#eo-dialog-tabs, #events-meta {
    border: none;
    padding: 0;
}
</style>
	<?php
	}
?>
