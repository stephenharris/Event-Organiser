<?php
/*
***** SETTINGS PAGE *****
*/
function eventorganiser_options_page() {
	global $wpdb,$wp_roles,$eventorganiser_roles, $eo_daysofweek, $eo_dateformats;
	global $eotest;
   	$editable_roles = get_editable_roles();
	$sup_array = array('Organiser (Author)'=>'author','Thumbnail'=>'thumbnail','Excerpts'=>'excerpt','Custom Field'=>'custom-fields','Comments'=>'comments','Revisions'=>'revisions');
	$url = get_pagenum_link();?>

	<div class="wrap">  
		<div id='icon-options-general' class='icon32'><br />
	</div>
	<h2><?php _e('Event Settings', 'eventorganiser'); ?></h2>

	<form name="eventorganiser_settings" method="post" action="">  
    	   <input type="hidden" name="page" value="event-settings" />
	<h3 class="title"><?php _e('Permissions', 'eventorganiser'); ?></h3>
	<p>
	Set permissions for events and venue management
	</p>
	<table class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
					<th><?php _e('Role', 'eventorganiser'); ?></th>
				<?php foreach($eventorganiser_roles as $eo_role => $eo_role_display): ?>
					<th><?php _e($eo_role_display, 'eventorganiser'); ?></th>
				<?php endforeach; //End foreach $eventRole ?> 
			</tr>
		</thead>		
		<tbody id="the-list">
			<?php
			$array_index =0;
			foreach( $editable_roles as $role_name => $display_name):
				$role = $wp_roles->get_role($role_name); ?>
					<tr <?php if($array_index==0)  _e( 'class="alternate"','eventorganiser');?>>
						<td><?php echo $role->name; ?></td>
						<?php foreach($eventorganiser_roles as $eo_role => $eo_role_display): ?>
							<td><input type="checkbox" name="eo_setting[permissions][<?php _e($role_name,'eventorganiser'); ?>][<?php _e($eo_role,'eventorganiser'); ?>]" value="1" <?php checked('1', $role->has_cap($eo_role)); ?> <?php if( $role_name=='administrator') _e('disabled','eventorganiser');?> /></td>
						<?php endforeach; //End foreach $eventRoles ?>
					</tr>
				<?php	
				$array_index=($array_index+1)%2;
			endforeach; //End foreach $editable_role ?>
		</tbody>
	</table>

	<h3 class="title"><?php _e('Event Post Type supports', 'eventorganiser'); ?></h3>
	<p>Select which features events should support.</p>
	<?php	$eo_settings_array= get_option('eventorganiser_options'); ?>

	<table class="wp-list-table widefat">
	<tr>
	<?php foreach ( $sup_array as $supp_display =>$supp):?>
		<td><input type="checkbox" name="eo_setting[supports][]" value="<?php _e($supp, 'eventorganiser'); ?>" <?php checked(true, in_array($supp,$eo_settings_array['supports']) ); ?> /><?php _e($supp_display,'eventorganiser');?> </td>
	<?php endforeach;?>
	</tr>
	</table>

	<h3 class="title"><?php _e('General Settings', 'eventorganiser'); ?></h3>
	<table class="form-table">
	<tr>
		<th>Add an 'events' link to the navigation menu:	</th>
		<td><input type="checkbox" name="eo_setting[addtomenu]" value="1" <?php checked('1', $eo_settings_array['addtomenu']); ?>/>(This may not work with some themes)</td>
	</tr>
	<tr>
		<th>Date format:	</th>
		<td>
			<label>
			<select  name="eo_setting[dateformat]">
				<option  <?php selected('dd-mm', $eo_settings_array['dateformat']);?> value="dd-mm">dd-mm-yyyy</option>
				<option  <?php selected('mm-dd', $eo_settings_array['dateformat']);?> value="mm-dd">mm-dd-yyyy</option>
			</select>
			This alters the default format for inputting dates. 
			</label>
		</td>
	</tr>
	<tr>
		<th>Show past events:</th>
		<td> <label>
				<input type="checkbox" name="eo_setting[showpast]" value="1" <?php checked('1', $eo_settings_array['showpast']); ?>/>
				Display past events on calendars, event lists and archives (this can be over-ridden by shortcode attributes and widgets options).
		</label></td>
	</tr>
	<tr>
		<th>Exclude events from searches:</th>
		<td> <label>
				<input type="checkbox" name="eo_setting[excludefromsearch]" value="1" <?php checked('1', $eo_settings_array['excludefromsearch']); ?>/>
		</label></td>
	</tr>
	<tr>
		<th>Enable pretty permalinks:</th>
		<td> <input type="checkbox" name="eo_setting[prettyurl]" value="1" <?php checked('1', $eo_settings_array['prettyurl']); ?>/>
		If you have permalinks enabled on, by checking this event archives should be available at
	<?php $site_url = site_url();?>
	<p>
		<strong>Event Archives:</strong>	
		<code><?php echo $site_url.'/<strong>'.esc_html($eo_settings_array['event_redirect']).'</strong>/event';?></code>
	</p>
	<p>
		<strong>Events:</strong>	
		<code><?php echo $site_url.'/<strong>'.esc_html($eo_settings_array['event_redirect']).'</strong>/event/[event-slug]';?></code>
	</p>
	<p>
		<strong>Venues:</strong>	
		<code><?php echo $site_url.'/<strong>'.esc_html($eo_settings_array['event_redirect']).'</strong>/venue/[venue-slug]';?></code>
	</p>
	<p><strong>Categories:</strong>
		<code><?php echo $site_url.'/<strong>'.esc_html($eo_settings_array['event_redirect']).'</strong>/category/[event-category-slug]';?></code>
	</p>
	<p> Please note that you will need go to WordPress Settings > Permalinks and click 'Save Changes' before any changes will take effect</p>
		</td>
	</tr>

	<tr>
		<th>Enable templates:</th>
		<td><input type="checkbox" name="eo_setting[templates]" value="1" <?php checked('1', $eo_settings_array['templates']); ?>/>
For each of the pages, the corresponding template is used. To use your own template simply give it the same name and store in your theme folder. By default, if Event Organiser cannot find a template in your theme directory, it will use its own default template. To prevent this, uncheck this option. WordPress will then decide which template from your theme's folder to use.
		<p>
			<strong>Events archive:</strong>	<code>archive-event.php</code>
		</p>
		<p>
			<strong>Event page:</strong>	<code>single-event.php</code>
		</p>
		<p>
			<strong>Venue page:</strong> <code>venue-template.php</code>
		</p>
		<p>
			<strong>Events Category page:</strong>	<code>taxonomy-event-category.php</code>
		</p>
		</td>
	</tr>

</table>

	<?php wp_nonce_field('eventorganiser_update_settings'); ?>
	<p class="submit"><input type="submit" name="eo_setting[action]"  class="button-primary" value="<?php _e('Update Settings', 'eventorganiser'); ?>" /></p>
	</form> 
	<h3 class="title"><?php _e('Export Events', 'eventorganiser'); ?></h3>
			<p><?php _e( 'The export button below generates an ICS file of your events that can be imported to other calendar applications such as Google Calendar.', 'eventorganiser'); ?></p>
			<form method="get" action="">
				<?php wp_nonce_field( 'eventorganiser_export' ); ?>
				<input type="hidden" name="page" value="event-settings" />
				<p class="submit">
					<input type="submit" name="submit" value="<?php _e( 'Download Export File', 'eo' ); ?> &raquo;" />
					<input type="hidden" name="addquicktag_download" value="true" />
				</p>
			</form>
<?php }

/*
** Settings action
*/
function eventorganiser_update_settings(){

	if(isset($_POST['eo_setting']) && $_POST["eo_setting"]['action']=='Update Settings'): 	

		//make sure data came from our settings page
		if( !check_admin_referer('eventorganiser_update_settings')) 
			wp_die("Cheatin'");

		//authentication checks
		if (!current_user_can('manage_options')) 
			wp_die("You do not have permission to manage options");

		global $wp_roles,$EO_Errors,$eventorganiser_roles;

	   	$editable_roles = get_editable_roles();
		$new_settings = $_POST["eo_setting"];

		//Update permissions
		foreach( $editable_roles as $role_name => $display_name):
			$role = $wp_roles->get_role($role_name);

			//Don't edit the administrator
			if($role_name!='administrator'):
				//Foreach custom role, add or remove option.
				foreach($eventorganiser_roles as $eo_role => $eo_role_display):
					if(isset($new_settings['permissions'][$role_name][$eo_role]) && $new_settings['permissions'][$role_name][$eo_role]==1){
						$role->add_cap($eo_role);		
					}else{
						$role->remove_cap($eo_role);		
					}
				endforeach; //End foreach $eventRoles
			endif; // Don't change administrator
		endforeach; //End foreach $editable_roles
		
		//Checkboxes
		$checkboxes = array('addtomenu','showpast','templates','prettyurl','excludefromsearch');

		//If checkbox isn't set, set value to 0
		foreach($checkboxes as $checkbox):
			if(!isset($new_settings[$checkbox])) $new_settings[$checkbox]='0';
		endforeach;

		//Update options
		$new_sup = array();
		if(isset($new_settings['supports'])) $new_sup = array_map('esc_html',$new_settings['supports']);
		$new_sup = array_merge($new_sup,array('title','editor'));

		//Default, then white list option
		 $new_settings['format'] =	($new_settings['dateformat']=='mm-dd' ? 'mm-dd' : 'dd-mm')  ;

		$eventorganiser_new_settings = array (
			'supports' => $new_sup,
			'event_redirect'=>'events',
			'dateformat'=>esc_html($new_settings['format']),
			'prettyurl'=>intval($new_settings['prettyurl']),
			'templates'=>intval($new_settings['templates']),
			'addtomenu'=> intval($new_settings['addtomenu']),
			'excludefromsearch'=> intval($new_settings['excludefromsearch']),
			'showpast'=> intval($new_settings['showpast']),
		);
		update_option('eventorganiser_options',$eventorganiser_new_settings);

		$EO_Errors = new WP_Error('eo_notice', __("Settings were updated"));

	endif;
}
?>
