<?php
/*
***** SETTINGS PAGE *****
*/
function eventorganiser_options_page() {
	global $wpdb,$wp_roles,$eventorganiser_roles, $eo_daysofweek, $eo_dateformats;

	$settings_page = new EventOrganiser_Settings_Page();
	$settings_page->display();
}

/*
** Settings action
*/
function eventorganiser_update_settings(){
	global $EO_Errors;

	if(isset($_POST['eo_setting']) && $_POST["eo_setting"]['action']==__('Save Changes')): 	

		//make sure data came from our settings page
		if( !check_admin_referer('eventorganiser_update_settings')) 
			wp_die(__('Cheatin&#8217; uh?'));

		//authentication checks
		if (!current_user_can('manage_options')) 
			wp_die(__('You do not have sufficient permissions to manage options for this site.'));

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
		$checkboxes = array('addtomenu','showpast','templates','prettyurl','excludefromsearch','deleteexpired','feed','eventtag','group_events');

		//If checkbox isn't set, set value to 0
		foreach($checkboxes as $checkbox):
			if(!isset($new_settings[$checkbox])) $new_settings[$checkbox]='0';
		endforeach;
	
		if($new_settings['deleteexpired']=='0'){
			eventorganiser_clear_cron_jobs();
		}else{
			if(!wp_next_scheduled( 'eventorganiser_delete_expired' )){
				eventorganiser_cron_jobs();
			}
		}

		//Update options
		$new_sup = array();
		if(isset($new_settings['supports'])) $new_sup = array_map('esc_html',$new_settings['supports']);
		$new_sup = array_merge($new_sup,array('title','editor'));

		//Default, then white list option
		 $new_settings['format'] =	($new_settings['dateformat']=='mm-dd' ? 'mm-dd' : 'dd-mm')  ;

		 $new_settings['navtitle'] =( !empty($new_settings['navtitle']) ? $new_settings['navtitle'] :  __('Events','eventorganiser'));

		$eventorganiser_new_settings = array (
			'supports' => $new_sup,
			'url_event' =>str_replace (" ", "", $new_settings['url_event']),
			'url_venue' =>str_replace (" ", "", $new_settings['url_venue']),
			'url_cat' =>str_replace (" ", "", $new_settings['url_cat']),
			'url_tag' =>str_replace (" ", "", $new_settings['url_tag']),
			'runningisnotpast' => intval($new_settings['runningisnotpast']),
			'dateformat'=>esc_html($new_settings['format']),
			'prettyurl'=>intval($new_settings['prettyurl']),
			'templates'=>intval($new_settings['templates']),
			'feed'=>intval($new_settings['feed']),
			'eventtag'=> intval($new_settings['eventtag']),
			'addtomenu'=> intval($new_settings['addtomenu']),
			'deleteexpired'=> intval($new_settings['deleteexpired']),
			'navtitle'=> esc_attr($new_settings['navtitle']),
			'excludefromsearch'=> intval($new_settings['excludefromsearch']),
			'showpast'=> intval($new_settings['showpast']),
			'group_events'=> (empty($new_settings['group_events']) ? '' : 'series'),
		);
		update_option('eventorganiser_options',$eventorganiser_new_settings);

		$EO_Errors = new WP_Error('eo_notice', '<strong>'.__("Settings saved").'</strong>');

	endif;
}


/**
 */
class EventOrganiser_Settings_Page{
	static $editable_roles;
	static $sup_array;
	static $eventorganiser_roles;
	static $settings;

	function __construct() {
		global $eventorganiser_roles;
	   	self::$editable_roles = get_editable_roles();
		self::$sup_array = array(__('Organiser','eventorganiser').' ('.__('Author').')'=>'author',__('Thumbnail')=>'thumbnail',__('Excerpt')=>'excerpt',__('Custom Fields')=>'custom-fields',__('Comments')=>'comments',__('Revisions')=>'revisions');
		self::$eventorganiser_roles = array(
			 'edit_events' =>__('Edit Events','eventorganiser'),
			 'publish_events' =>__('Publish Events','eventorganiser'),
			 'delete_events' => __('Delete Events','eventorganiser'),
			'edit_others_events' =>__('Edit Others\' Events','eventorganiser'),
			 'delete_others_events' => __('Delete Other\'s Events','eventorganiser'),
			'read_private_events' =>__('Read Private Events','eventorganiser'),
			 'manage_venues' => __('Manage Venues','eventorganiser'),
			 'manage_event_categories' => __('Manage Event Categories & Tags','eventorganiser'),
		);
		self::$settings = get_option('eventorganiser_options');
	}

	function display(){
		?>
		<div class="wrap">  
			<div id='icon-options-general' class='icon32'><br />
		</div>
		<h2 class="nav-tab-wrapper">
		<?php _e('Event Settings', 'eventorganiser'); ?>
				<a class="nav-tab nav-tab-active" id="eo-tab-general" href=""><?php _e('General');?></a>
				<a class="nav-tab" id="eo-tab-permssions" href=""><?php _e('Permissions','eventorganiser');?></a>
				<a class="nav-tab" id="eo-tab-permalinks" href=""><?php _e('Permalinks');?></a>
				<a class="nav-tab" id="eo-tab-imexport" href=""><?php echo __('Import','eventorganiser').'/'.__('Export','eventorganiser');?></a>
		</h2>
		
		</br>
		<form name="eventorganiser_settings" method="post" action="">  
			<input type="hidden" name="page" value="event-settings" />
			<?php wp_nonce_field('eventorganiser_update_settings'); ?>

			<div class="tab-content eo-tab-permssions-content">
				<?php 	$this->display_permissions(); ?>
				<?php 	$this->display_submit(); ?>
			</div>

			<div class="tab-content eo-tab-general-content">
				<?php 	$this->display_general(); ?>
				<?php 	$this->display_submit(); ?>
			</div>

			<div class="tab-content eo-tab-permalinks-content">
				<?php 	$this->display_permalinks(); ?>
				<?php 	$this->display_submit(); ?>
			</div>
		</form> 

		<div class="tab-content eo-tab-imexport-content">
			<?php do_action('eventorganiser_im_export'); ?>
		</div>
	
		<script>
		(function($){
			$(document).ready( function() {
			var tabs = $('h2.nav-tab-wrapper a');
			var contents = $('.tab-content');
			tabs.click(function(e){
				e.preventDefault();
				contentID = $(this).attr('id')+'-content';
				tabs.removeClass('nav-tab-active');
				contents.hide();
				$('.tab-content.'+contentID).show();
				$(this).addClass('nav-tab-active');
			});
			contents.hide();
			$(tabs[0]).trigger('click');
			});
		})(jQuery);
		</script>
	<?php
	}

	function display_submit(){
		?>
		<p class="submit"><input type="submit" name="eo_setting[action]"  class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>
	<?php
	}
	
	function display_permissions(){
		global $wp_roles;
	?>
	<p>
	<?php _e('Set permissions for events and venue management','eventorganiser'); ?>
	</p>
	<table class="wp-list-table widefat fixed posts ">
		<thead>
			<tr>
					<th><?php _e('Role'); ?></th>
				<?php foreach(self::$eventorganiser_roles as $eo_role => $eo_role_display): ?>
					<th><?php echo $eo_role_display;?></th>
				<?php endforeach; //End foreach $eventRole ?> 
			</tr>
		</thead>		
		<tbody id="the-list">
			<?php
			$array_index =0;
			foreach( self::$editable_roles as $role_name => $display_name):
				$role = $wp_roles->get_role($role_name); 
				$role_name = isset( $wp_roles->role_names[$role_name] ) ? translate_user_role( $wp_roles->role_names[$role_name] ) : __( 'None' );
				?>
					<tr <?php if($array_index==0)  echo 'class="alternate"';?>>
						<td><?php echo $role_name; ?></td>
						<?php foreach(self::$eventorganiser_roles as $eo_role => $eo_role_display): ?>
							<td><input type="checkbox" name="eo_setting[permissions][<?php echo $role->name; ?>][<?php echo $eo_role; ?>]" value="1" <?php checked('1', $role->has_cap($eo_role)); ?> <?php if( $role->name=='administrator') echo 'disabled';?> /></td>
						<?php endforeach; //End foreach $eventRoles ?>
					</tr>
				<?php	
				$array_index=($array_index+1)%2;
			endforeach; //End foreach $editable_role ?>
		</tbody>
	</table>
<?php
	}
	
	function display_general(){
	?>

	<table class="form-table">
	<tr>
		<th><?php _e('Select which features events should support','eventorganiser');?>:</th>
		<td>
	<table>
	<tr>
	<?php	
		$counter=1; 
		foreach ( self::$sup_array as $supp_display =>$supp):
			echo '<td><input type="checkbox" name="eo_setting[supports][]" value="'.$supp.'" '.checked(true, in_array($supp,self::$settings['supports']),false).' />'.$supp_display.'</td>';
			if($counter==4)
				echo '</tr><tr>';
			$counter++;
		endforeach;
	
		 self::$settings['eventtag'] = (empty(self::$settings['eventtag']) ? 0 : 1);		
	?>

		<td><input type="checkbox" name="eo_setting[eventtag]" value="1" <?php checked('1', self::$settings['eventtag']); ?>/><?php _e("Event Tags",'eventorganiser');?></td>
	</tr>
	</table>
		</td>
	</tr>
	<tr>
		<th><?php _e("Add an 'events' link to the navigation menu:",'eventorganiser');?></th>
		<td>
			<input type="checkbox" name="eo_setting[addtomenu]" value="1" <?php checked('1', self::$settings['addtomenu']); ?>/>
			<?php self::$settings['navtitle'] =( !empty(self::$settings['navtitle']) ? self::$settings['navtitle'] :  __('Events','eventorganiser')); ?>
			<input type="text" name="eo_setting[navtitle]" value="<?php echo self::$settings['navtitle'];?>" />
			<?php _e("(This may not work with some themes):",'eventorganiser');?>
		</td>
	</tr>
	<tr>
		<th><?php _e('Date Format:','eventorganiser');?></th>
		<td>
			<label>
			<select  name="eo_setting[dateformat]">
				<option  <?php selected('dd-mm', self::$settings['dateformat']);?> value="dd-mm"><?php _e('dd-mm-yyyy','eventorganiser');?></option>
				<option  <?php selected('mm-dd', self::$settings['dateformat']);?> value="mm-dd"><?php _e('mm-dd-yyyy','eventorganiser');?></option>
			</select>
			<?php _e("This alters the default format for inputting dates.",'eventorganiser');?>
			</label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Show past events:",'eventorganiser');?></th>
		<td> <label>
				<input type="checkbox" name="eo_setting[showpast]" value="1" <?php checked('1', self::$settings['showpast']); ?>/>
				<?php _e("Display past events on calendars, event lists and archives (this can be over-ridden by shortcode attributes and widget options).",'eventorganiser');?>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Group occurrences",'eventorganiser');?>:</th>
		<?php 	self::$settings['group_events'] = (isset(self::$settings['group_events']) ? self::$settings['group_events'] : '');?>
		<td> <label>
				<input type="checkbox" name="eo_setting[group_events]" value="series" <?php checked('series', self::$settings['group_events']); ?>/>
				<?php _e("If selected only one occurrence of an event will be displayed on event lists and archives (this can be over-ridden by shortcode attributes and widget options).",'eventorganiser');?>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Are current events past?",'eventorganiser');?></th>
		<td> 
			<?php $runningsisnotpast = (empty(self::$settings['runningisnotpast']) ? 0 : self::$settings['runningisnotpast'])?>
				<input type="radio" id="whatispast-0" name="eo_setting[runningisnotpast]" value="0" <?php checked('0', $runningsisnotpast); ?>/>
				<label for="whatispast-0"><?php _e('Yes');?></label>
				<input type="radio" id="whatispast-1" name="eo_setting[runningisnotpast]" value="1" <?php checked('1', $runningsisnotpast); ?>/>
				<label for="whatispast-1"><?php _e('No');?></label></br>
				<?php _e("If 'no' is selected, an occurrence of an event is only past when it has finished. Otherwise, an occurrence is considered 'past' as soon as it starts.",'eventorganiser');?>
		</td>
	</tr>
	<tr>
		<th><?php _e("Delete expired events:",'eventorganiser');?></th>
		<td> <label>
				<input type="checkbox" name="eo_setting[deleteexpired]" value="1" <?php checked('1', self::$settings['deleteexpired']); ?>/>
				<?php _e("If selected the event will be automatically trashed 24 hours after the last occurrence finishes.",'eventorganiser');?>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Enable events ICAL feed:",'eventorganiser');?></th>
		<td> 
				<input type="checkbox" name="eo_setting[feed]" value="1" <?php checked('1', self::$settings['feed']); ?>/>
				<label> <?php printf(__('If selected, visitors can subscribe to your events with the url: %s','eventorganiser'), '<code>'.eo_get_events_feed().'</code>') ?></label>
		</td>
	</tr>

	<tr>
		<th><?php _e("Exclude events from searches:",'eventorganiser');?></th>
		<td> <label>
				<input type="checkbox" name="eo_setting[excludefromsearch]" value="1" <?php checked('1', self::$settings['excludefromsearch']); ?>/>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Enable templates:",'eventorganiser');?></th>
		<td><input type="checkbox" name="eo_setting[templates]" value="1" <?php checked('1', self::$settings['templates']); ?>/>
		<?php _e("For each of the pages, the corresponding template is used. To use your own template simply give it the same name and store in your theme folder. By default, if Event Organiser cannot find a template in your theme directory, it will use its own default template. To prevent this, uncheck this option. WordPress will then decide which template from your theme's folder to use.",'eventorganiser');?>
		<p>
			<strong><?php _e("Events archives:",'eventorganiser');?></strong><code>archive-event.php</code>
		</p>
		<p>
			<strong><?php _e("Event page:",'eventorganiser');?></strong>	<code>single-event.php</code>
		</p>
		<p>
			<strong><?php _e("Venue page:",'eventorganiser');?></strong> <code>venue-template.php</code>
		</p>
		<p>
			<strong><?php _e("Events Category page:",'eventorganiser');?></strong>	<code>taxonomy-event-category.php</code>
		</p>
		</td>
	</tr>

</table>
	<?php
}

	function display_permalinks(){
		$site_url = site_url();
		?>
	<p>
		<?php _e("Choose a custom permalink structure for events, venues, event categories and event tags.",'eventorganiser');?>
	</p><p>
		<?php _e("Please note to enable these structures you must first have pretty permalinks enabled on WordPress in Settings > Permalinks.",'eventorganiser');?>
	</p>
	<table class="form-table">

	<tr>
		<th><?php _e("Enable event pretty permalinks:",'eventorganiser');?></th>
		<td> <input type="checkbox" name="eo_setting[prettyurl]" value="1" <?php checked('1',self::$settings['prettyurl']); ?>/>
		<?php _e("If you have pretty permalinks enabled, select to have pretty premalinks for events.",'eventorganiser');?>
	</tr>
	<tr>
		<th><?php _e("Events",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eo_setting[url_event]" value="<?php echo self::$settings['url_event'];?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.self::$settings['url_event'].'</strong>/'.'[event_slug]' ;?></code></label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Venues",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eo_setting[url_venue]" value="<?php echo self::$settings['url_venue'];?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.self::$settings['url_venue'].'</strong>/'.'[venue_slug]' ;?></code></label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Event Categories",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eo_setting[url_cat]" value="<?php echo self::$settings['url_cat'];?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.self::$settings['url_cat'].'</strong>/'.'[event_cat_slug]' ;?></code></label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Event Tags",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eo_setting[url_tag]" value="<?php echo self::$settings['url_tag']; ?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.self::$settings['url_tag'].'</strong>/'.'[event_tag_slug]' ;?></code></label>
		</td>
	</tr>
</table>
	<p> <strong><?php _e("Please note that you will need go to WordPress Settings > Permalinks and click 'Save Changes' before any changes will take effect.",'eventorganiser');?></strong></p>
<?php

	}
}
?>
