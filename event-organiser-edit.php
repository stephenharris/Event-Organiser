<?php
/**
 * Functions for Event CPT editing / creating page 
 *
 * @since 1.0.0
 */
//TODO Event.js - disabling input
/**
 * Initialises the plug-ins metaboxs on Event CPT
 *
 * @since 1.0.0
 */
add_action('admin_init','eventorganiser_edit_init');
function eventorganiser_edit_init(){
	//If current user can delete this event
	if (current_user_can('delete_events')) add_action('delete_post', 'eventorganiser_event_delete', 10);
	
	// add a meta box to event post types.
	add_meta_box('eventorganiser_detail', __('Event Details','eventorganiser'), 'eventorganiser_details_metabox', 'event', 'normal', 'high');

	// add a callback function to save any data a user enters in
	add_action('save_post','eventorganiser_details_save');

}

add_action('add_meta_boxes', 'eventorganiser_author_meta_box_title',0);
function eventorganiser_author_meta_box_title() {
    global $wp_meta_boxes; // array of defined meta boxes
	$wp_meta_boxes['event']['normal']['core']['authordiv']['title']= __('Organiser','eventorganiser');
}

/**
 * Sets up the event data metabox
* This allows user to enter date / time, reoccurrence and venue data for the event
 *
 * @since 1.0.0
 */
function eventorganiser_details_metabox(){
	
	global $wp_locale;	

	//Retrieve all venues
	$AllVenues = new EO_Venues;
	$AllVenues->query();

	//Sets the format as php understands it, and textual.
	$eo_settings_array= get_option('eventorganiser_options');
	if($eo_settings_array['dateformat']=='dd-mm'){
		$phpFormat = 'd-m-Y';
		$format = 'dd-mm-yyyy';
	}else{
		$phpFormat = 'm-d-Y';
		$format = 'mm-dd-yyyy';
	}

	//Get the starting day of the week
	$start_day=intval(get_option('start_of_week'));

	//Retrieve event details (if they exist)
	$event = new EO_Event(get_the_ID());

	//Retrieve venue of event
	$current_Venue = new EO_Venue((int) $event->venue);

	//Start of meta box ?>	
		<p>
			<?php if($event->is_reoccurring()):?>
				<strong><?php _e('This is a reoccurring event','eventorganiser');?></strong>. 
				<input type="checkbox" id="HWSEvent_rec" name="eo_input[AlterRe]" value="yes" /> 
				<?php _e('Check to edit this event and its reoccurrences','eventorganiser');?>.
			<?php endif;?>
		</p>

		<div class="<?php if ($event->is_reoccurring()): echo 'reoccurence'; else: echo 'onetime'; endif;?>">
			<p><?php sprintf(__('Ensure dates are entered in %1$s format and times in %2$s (24 hour) format','eventorganiser'),'<strong>'.$format.'</strong>',' <strong>hh:mm</strong>');?> </p>
			<table>
			<tr class="event-date">
			<td><?php echo __("Start Date/Time",'eventorganiser').':';?> </td>
			<td> 
			<input class="ui-widget-content ui-corner-all" name="eo_input[StartDate]" size="10" maxlength="10" id="from_date" <?php disabled($event->is_reoccurring());?> value="<?php $event->the_start($phpFormat); ?>"/>
			<input name="eo_input[StartTime]" class="eo_time ui-widget-content ui-corner-all" size="4" maxlength="5" id="HWSEvent_time" <?php disabled($event->is_reoccurring()|| $event->is_allday());?> value="<?php $event->the_start('H:i');?>"/>
			</td>
			</tr>
			<tr class="event-date">
			<td><?php echo __("End Date/Time",'eventorganiser').':';?> </td>
			<td> 
			<input class="ui-widget-content ui-corner-all" name="eo_input[EndDate]" size="10" maxlength="10" id="to_date" <?php disabled($event->is_reoccurring());?>  value="<?php $event->the_end($phpFormat); ?>"/>
			<input name="eo_input[FinishTime]" class="eo_time ui-widget-content ui-corner-all " size="4" maxlength="5" id="HWSEvent_time2" <?php disabled($event->is_reoccurring()|| $event->is_allday());?> value="<?php $event->the_end('H:i'); ?>""/>
			<label>
				<input type="checkbox" id="eo_allday"  <?php checked($event->is_allday()); ?> name="eo_input[allday]"<?php disabled($event->is_reoccurring());?> value="1"/>
				<?php _e("All day",'eventorganiser');?>
			 </label>
			</td>
			</tr>

			<tr class="event-date">
			<td><?php _e("Reoccurence:",'eventorganiser');?> </td>
			<td> 
			<?php 
				$reoccurrence_schedules= array(
					'once'=> __('once','eventorganiser'),
					'daily'=> __('daily','eventorganiser'),
					'weekly'=> __('weekly','eventorganiser'),
					'monthly'=> __('monthly','eventorganiser'),
					'yearly'=> __('yearly','eventorganiser')
				);?>
			<select id="HWSEventInput_Req" name="eo_input[schedule]" <?php disabled($event->is_reoccurring());?>>
					<?php foreach ($reoccurrence_schedules as $index=>$val): ?>
						<option value="<?php echo $index;?>" <?php selected($event->is_schedule($index));?>><?php echo $val;?></option>
					<?php endforeach;  //End foreach $allowed_reoccurs?>
			</select>
			</td>
			</tr>

			<tr class="event-date reocurrence_row">
			<td></td><td>
			<p>
				<?php _e("Repeat every ",'eventorganiser');?>
				<input <?php disabled($event->is_reoccurring());?> class="ui-widget-content ui-corner-all" name="eo_input[event_frequency]" id="HWSEvent_freq" type="number" min="1" max="365" maxlength="4" size="4" disabled="disabled" value="<?php echo $event->frequency;?>" /> 
				<span id="recpan" >  </span>				
			</p>

			<p id="dayofweekrepeat">
			<?php _e("on",'eventorganiser');?>	

				<?php for($i = 0; $i <= 6; $i++):
					$d = ($start_day + $i)%7;
					$ical_d = EO_Event::$daysofweek[$d]['ical'];
					$day =$wp_locale->weekday_abbrev[$wp_locale->weekday[$d]];
				?>
					<input type="checkbox" id="day-<?php echo $day;?>"  <?php checked($event->meta[$d],'1'); ?>  value="<?php echo $ical_d?>" class="daysofweek" name="eo_input[days][]" disabled="disabled" />
					<label for="day-<?php echo $day;?>" > <?php echo $day;?></label>
				<?php endfor;  ?>
			</p>

			<p id="dayofmonthrepeat">
				<input type="radio" id="bymonthday" disabled="disabled" name="eo_input[schedule_meta]" <?php checked($event->occursBy(),'BYMONTHDAY'); ?> value="BYMONTHDAY=" /> 
				<label for="bymonthday" >	<?php _e("day of month",'eventorganiser');?>	</label>
				<input type="radio" id="byday" disabled="disabled" name="eo_input[schedule_meta]"  <?php checked($event->occursBy()=='BYMONTHDAY',false); ?> value="BYDAY=" /> 
				<label for="byday" >	<?php _e("day of week",'eventorganiser');?></label>
			</p>

			<p class="reoccurrence_label">
			<?php _e("until",'eventorganiser');?> 
				<input <?php disabled($event->is_reoccurring());?> class="ui-widget-content ui-corner-all" name="eo_input[schedule_end]" id="recend" size="10" maxlength="10" disabled="disabled" value="<?php $event->the_schedule_end($phpFormat); ?>"/>
			</p>

			<p id="event_summary"> </p>

			</td>
			</tr>

			<tr>	
			<td class="label"> <?php _e("Venue",'eventorganiser');?> : </td>
			<td> 
			<!-- If javascript is disabed, a simple drop down menu box is displayed to choose venue.
			Otherwise, the user is able to search the venues by typing in the input box.-->		
				<select size="50" id="venue_select" name="eo_input[venue_id]">
					<option><?php _e("Select a venue",'eventorganiser');?></option>
				<?php foreach ($AllVenues->results as $thevenue):?>
					<option <?php  selected($event->is_at_venue($thevenue['venue_id']));?> value="<?php echo intval($thevenue['venue_id']);?>"><?php echo $thevenue['venue_name']; ?></option>
				<?php endforeach;?>
				</select>
			<span style="font-size:0.8em;line-height:0.8em;"> <?php _e("Search for a venue. To add a venues go to the venue page.",'eventorganiser');?></span>
			</td>
			</tr>
			<tr class="venue_row <?php if (!$event->venue_set()) echo 'novenue';?>" >
			<td></td>
			<td>

			<div id="eventorganiser_venue_meta" style="display:none;">
				<input type="hidden" id="eo_venue_Lat" value="<?php  echo $current_Venue->latitude;?>" />
				<input type="hidden" id="eo_venue_Lng" value="<?php  echo $current_Venue->longitude;?>" />
			</div>
			
			<div id="venuemap" class="ui-widget-content ui-corner-all gmap3"></div>
			<div class="clear"></div>
			</td>
			</tr>

			</table>
		</div>
	<?php 

	// create a custom nonce for submit verification later
	wp_nonce_field('eventorganiser_event_update_'.get_the_ID(),'_eononce');	
}	


/**
 * Saves the event data posted from the event metabox.
 * Hooked to the 'save_post' action
 * 
 * @since 1.0.0
 *
 * @param int $post_id the event post ID
 * @return int $post_id the event post ID
 */
function eventorganiser_details_save($post_id) {
	global $wpdb,$eventorganiser_events_table;

	//make sure data came from our meta box
	if(!isset($_POST['_eononce']) || !wp_verify_nonce($_POST['_eononce'],'eventorganiser_event_update_'.$post_id))
		return $post_id;

	// verify this is not an auto save routine. 
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;

	//authentication checks
	if (!current_user_can('edit_event', $post_id)) return $post_id;

	$raw_data = $_POST['eo_input'];

	//Check if there is existing event data.
	$event = new EO_Event($post_id);
	
	/*
	 * If event data exists, we may have to delete the occurrances and replace them with new ones.
	* First we check if this is necessary. If not, we just update the data and exit.
	*/
	$delete_existing=false;
	if($event->exists):

		//We are updating a single event (and it is still a one time event), can just update all data easily				
		if($raw_data['schedule']=='once'&& $event->is_schedule('once')){
			$event->create($raw_data);
			$event_input =array(
				'post_id'=>$post_id,
				'StartDate'=>$event->start->format('Y-m-d'),
				'StartTime'=>$event->start->format('H:i:s'),
				'EndDate'=>$event->end->format('Y-m-d'),
				'FinishTime'=>$event->end->format('H:i:s'),
				'Venue'=>$event->venue,
				'event_schedule' => $event->schedule,
				'event_schedule_meta' => $event->meta,
				'event_frequency' => $event->frequency,
				'event_occurrence' => 0,
				'event_allday' =>  $event->allday,
				'reoccurrence_start' => $event->schedule_start->format('Y-m-d'),
				'reoccurrence_end' => $event->schedule_end->format('Y-m-d'),
			);
			$upd = $wpdb->update( $eventorganiser_events_table, $event_input, array( 'post_id' => $post_id ));		
			return $post_id;

		//Event was reoccurring 
		}elseif(!$event->is_schedule('once')){
			
			//If 'edit reocurrences' is checked we need to replace reoccurrences
			if((isset($raw_data['AlterRe']) && $raw_data['AlterRe']=='yes')){
				$delete_existing=true;
				
			//Edit 'edit reocurrences' is not checked - reoccurrances are to remain. Just update Venue and exist
			}else{
				$upd = $wpdb->update( $eventorganiser_events_table, array('Venue'=>intval($raw_data['venue_id'])), array( 'post_id' => $post_id ));
				return $post_id;
			}

		//Was a one-time event, is now a reoccurring event - delete event.
		}else{
				$delete_existing=true;
		}
	endif;

	//Populate event data from raw input and inserts (after deleting existing occurrences, if necessary)
	$event->insertEvent($raw_data, $post_id, $delete_existing);

	return $post_id;
}



/**
 * Display custom error or alert messages on events CPT page
 *
 * @since 1.0.0
 */
add_action('admin_notices', 'event_edit_admin_notice',0);
function event_edit_admin_notice(){
	//print the message
	global $post;
	$notice = get_option('eo_notice');
	if (empty($notice)) return '';
	foreach($notice as $pid => $messages){
		if (!empty($post->ID) && $post->ID == $pid ){
			echo '<div id="message" class="error">';
			foreach ($messages as $m):
				echo '<p>'.$m.'</p>';
			endforeach;
			echo '</div>';
			//make sure to remove notice after its displayed so its only displayed when needed.
			unset($notice[0]);
			unset($notice[$pid]);
			update_option('eo_notice',$notice);
        	}
	}	
}


/**
 * Deletes the event data associated with post
 *
 * @since 1.0.0
 *
 * @param int $post_id the post id, whose event data is being deleted
 */
function eventorganiser_event_delete($post_id){
	global $wpdb, $eventorganiser_events_table;
	if(!current_user_can('delete_event', $post_id))
		wp_die( __('You are not allowed to delete events.','eventorganiser') );

	$del = $wpdb->get_results($wpdb->prepare("DELETE FROM $eventorganiser_events_table WHERE post_id=%d",$post_id));
}

?>
