<?php
/****** SETTINGS PAGE ******/
if(!class_exists('EventOrganiser_Admin_Page')){
    require_once(EVENT_ORGANISER_DIR.'classes/class-eventorganiser-admin-page.php' );
}
class EventOrganiser_Settings_Page extends EventOrganiser_Admin_Page{

	static $editable_roles;
	static $sup_array;
	static $eventorganiser_roles;
	static $settings;
	static $sections;
	static $checkboxes,$text,$permalinks,$select,$radio,$defaults;

	function set_constants(){
		$this->hook = 'options-general.php';
		$this->title = __('Event Organiser Settings','eventorganiser');
		$this->menu ='Event Organiser';
		$this->permissions ='manage_options';
		$this->slug ='event-settings';

		self::$sections = array(
			'general'=>array(
				'title'=>__('General'),
				'callback'=>array(__CLASS__,'display_general')
			),
			'permissions'=>array(
				'title'=>__('Permissions'),
				'callback'=>array(__CLASS__,'display_permissions')
			),
			'permalinks'=>array(
				'title'=>__('Permalinks'),
				'callback'=>array(__CLASS__,'display_permalinks')
			),
			'imexport'=>array(
				'title'=>__('Import','eventorganiser').'/'.__('Export','eventorganiser'),
				'callback'=>array(__CLASS__,'display_imexport'),
			));

		self::$checkboxes = array('showpast','templates','prettyurl','excludefromsearch','deleteexpired','feed','eventtag','group_events');
		self::$text = array('navtitle');
		self::$permalinks = array('url_event','url_events','url_venue','url_cat','url_tag');

		$menus = get_terms('nav_menu');

		self::$select = array('dateformat' => array(
				'dd-mm'=>__('dd-mm-yyyy','eventorganiser'),
				'mm-dd'=>__('mm-dd-yyyy','eventorganiser')
			));		
		$menus = get_terms('nav_menu');
		self::$select['addtomenu']['0'] = 'None';
		self::$select['addtomenu']['1'] = 'Fallback';
		 foreach($menus as $menu): 
			self::$select['addtomenu'][$menu->slug] = $menu->name;
		 endforeach; 


		self::$radio = array(
			'runningisnotpast' => array(
				'0'=>__('Yes'),
				'1'=>__('No')
			));

		self::$defaults =array(
			'dateformat' =>'dd-mm',
			'url_event'=> 'events/event',
			'url_venue'=> 'events/venue',
			'url_cat'=> 'events/category',
			'url_tag'=> 'events/tag',
		);
	}

	function  admin_init_actions(){
		//Register options
		register_setting( 'eventorganiser_options', 'eventorganiser_options', array($this,'validate'));
		self::$sections = apply_filters('eventorganiser_settings_sections',self::$sections);
	}

	function validate($option){

		$permissions = (isset($option['permissions']) ? $option['permissions'] : array());
		$this->update_roles($permissions);

		$clean = array();
		$clean = $this->validate_checkboxes($option,$clean);
		$clean = $this->validate_text($option,$clean);
		$clean = $this->validate_select($option,$clean);
		$clean = $this->validate_radio($option,$clean);
		$clean = $this->validate_permalink($option,$clean);

		$clean['supports'] = (isset($option['supports']) ? array_map('esc_html',$option['supports']) : array());
		$clean['supports'] = array_merge($clean['supports'],array('title','editor'));

		$clean = $this->update_nav_menu($option,$clean);

		return $clean;
	}

	function validate_checkboxes($options,$clean){
		foreach(self::$checkboxes as $checkbox):
  			$clean[$checkbox] = (!empty($options[$checkbox]) ? 1 : 0);
		endforeach;
		$clean['group_events'] = ($clean['group_events'] ? 'series' : '');
		return $clean;
	}

	function validate_select($options,$clean){
		foreach(self::$select as $id=>$choices):
			$clean[$id] = (isset($choices[$options[$id]]) ? $options[$id] : self::$defaults[$id]) ;
		endforeach;
		return $clean;
	}

	function validate_radio($options,$clean){
		foreach(self::$radio as $id=>$choices):
			$clean[$id] = (isset($choices[$options[$id]]) ? $options[$id] : self::$defaults[$id]) ;
		endforeach;
		return $clean;
	}

	function validate_text($options,$clean){
		foreach(self::$text as $id):
			$clean[$id] =( !empty($options[$id]) ? esc_html($options[$id]) :  __('Events','eventorganiser'));
		endforeach;
		return $clean;
	}

	function validate_permalink($options,$clean){
		foreach(self::$permalinks as $id):
			$value = esc_url_raw($options[$id]);
			$value = str_replace( 'http://', '', $value );
			$value = trim($value, "/");
			$clean[$id] =( !empty($value) ? $value :  self::$defaults[$id]);
		endforeach;
		return $clean;
	}

	function update_nav_menu($options,$clean){
		$eo_options= get_option('eventorganiser_options');

		$menu = $clean['addtomenu'];
		$menu_item_db_id = isset($eo_options['menu_item_db_id']) ? (int) $eo_options['menu_item_db_id'] : 0;
		$current = (isset($eo_options['addtomenu']) ? $eo_options['addtomenu'] : 0);

		if(!is_nav_menu_item($menu_item_db_id)){
			$menu_item_db_id=0;
			$current ='';
		}

		if((empty($menu)||$menu=='1')&& is_nav_menu_item($menu_item_db_id) ){
			wp_delete_post( $menu_item_db_id, true );
			$menu_item_db_id=0;
		}

		if(( !empty($menu) && $menu !='1')){
			$menu_item_data = array();
			$menu_obj = wp_get_nav_menu_object($menu);
			$menu_id = ($menu_obj ? $menu_obj->term_id : 0);
			$status = ($menu_id==0 ? '' : 'publish');

			$menu_item_data = array(	
				'menu-item-title'=> $clean['navtitle'],
				'menu-item-url' =>get_post_type_archive_link('event'),
				'menu-item-object' =>'event',
				'menu-item-status' =>$status,
				'menu-item-type'=>'post_type_archive'
			);
			$menu_item_db_id = wp_update_nav_menu_item( $menu_id, $menu_item_db_id,$menu_item_data);
		}

		$clean['menu_item_db_id'] =$menu_item_db_id;
		return $clean;
	}


	function update_roles($permissions){
		global $wp_roles,$EO_Errors,$eventorganiser_roles;
		$editable_roles = get_editable_roles();
		foreach( $editable_roles as $role_name => $display_name):
			$role = $wp_roles->get_role($role_name);
			//Don't edit the administrator
			if($role_name!='administrator'):
				//Foreach custom role, add or remove option.
				foreach($eventorganiser_roles as $eo_role => $eo_role_display):
					if(isset($permissions[$role_name][$eo_role]) &&$permissions[$role_name][$eo_role]==1){
						$role->add_cap($eo_role);		
					}else{
						$role->remove_cap($eo_role);		
					}
				endforeach; //End foreach $eventRoles
			endif; // Don't change administrator
		endforeach; //End foreach $editable_roles
	}


	function init(){
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
			<?php screen_icon('options-general'); ?>
		<h2 class="nav-tab-wrapper">
		<?php 
			echo __('Event Settings', 'eventorganiser').' '; 
			foreach (self::$sections as $section_id => $section )
				echo "<a class='nav-tab nav-tab-active' id='eo-tab-{$section_id}' href=''>".esc_html($section['title'])."</a>";
		;?>
		</h2>

		<form name="eventorganiser_settings" method="post" action="options.php">  

			<?php settings_fields('eventorganiser_options'); 

			foreach (self::$sections as $section_id => $section ):
				if('imexport' == $section_id)
					continue;
				echo "<div class='tab-content eo-tab-{$section_id}-content'>";
					call_user_func($section['callback']);
					do_settings_fields(self::$slug, $section_id);
					echo "<p class='submit'><input type='submit' name='eventorganiser_options[action]'  class='button-primary' value='".__('Save Changes')."' /></p>";
				echo "</div>";
			endforeach;
		?>
		</form>
		<?php

		echo "<div class='tab-content eo-tab-imexport-content'>";
			call_user_func(self::$sections['imexport']['callback']);
			do_settings_fields(self::$slug, 'imexport');
		echo "</div>";
	}

	function display_imexport(){
		do_action('eventorganiser_event_settings_imexport'); 
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
							<td><input type="checkbox" name="eventorganiser_options[permissions][<?php echo $role->name; ?>][<?php echo $eo_role; ?>]" value="1" <?php checked('1', $role->has_cap($eo_role)); ?> <?php if( $role->name=='administrator') echo 'disabled';?> /></td>
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
		 self::$settings['supports'] = (empty(self::$settings['supports']) ? array() :  self::$settings['supports'] );	
		foreach ( self::$sup_array as $supp_display =>$supp):
			echo '<td><input type="checkbox" name="eventorganiser_options[supports][]" value="'.$supp.'" '.checked(true, in_array($supp,self::$settings['supports']),false).' />'.$supp_display.'</td>';
			if($counter==4)
				echo '</tr><tr>';
			$counter++;
		endforeach;
	
		 self::$settings['eventtag'] = (empty(self::$settings['eventtag']) ? 0 : 1);		
	?>

		<td><input type="checkbox" name="eventorganiser_options[eventtag]" value="1" <?php checked('1', self::$settings['eventtag']); ?>/><?php _e("Event Tags",'eventorganiser');?></td>
	</tr>
	</table>
		</td>
	</tr>
	<tr>
		<th><?php _e("Add an 'events' link to the navigation menu:",'eventorganiser');?></th>
		<td>
			<?php self::$settings['addtomenu'] =( !empty(self::$settings['addtomenu']) ? self::$settings['addtomenu'] :  0); ?>
			<?php $menus = get_terms('nav_menu');?>
				<select  name="eventorganiser_options[addtomenu]">
					<option  <?php selected(0,self::$settings['addtomenu']);?> value="0"><?php _e('Do not add to menu','eventorganiser'); ?> </option>
				<?php foreach($menus as $menu): ?>
					<option  <?php selected($menu->slug,self::$settings['addtomenu']);?> value="<?php echo $menu->slug; ?>"><?php echo $menu->name;?> </option>
				<?php endforeach; ?>
					<option  <?php selected(1,self::$settings['addtomenu']);?> value="1"><?php _e('Page list (fallback)','eventorganiser'); ?></option>
				</select>

			<?php self::$settings['navtitle'] =( !empty(self::$settings['navtitle']) ? self::$settings['navtitle'] :  __('Events','eventorganiser')); ?>
			<?php self::$settings['menu_item_db_id'] =( !empty(self::$settings['menu_item_db_id']) ? (int) self::$settings['menu_item_db_id'] : 0); ?>

			<input type="hidden" name ="eventorganiser_options[menu_item_db_id]" value="<?php echo self::$settings['menu_item_db_id'];?>" />
			<input type="text" name="eventorganiser_options[navtitle]" value="<?php echo self::$settings['navtitle'];?>" />
			<?php _e("(This may not work with some themes):",'eventorganiser');?>
		</td>
	</tr>
	<tr>
		<th><?php _e('Date Format:','eventorganiser');?></th>
		<td>
			<label>
			<select  name="eventorganiser_options[dateformat]">
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
				<input type="checkbox" name="eventorganiser_options[showpast]" value="1" <?php checked('1', self::$settings['showpast']); ?>/>
				<?php _e("Display past events on calendars, event lists and archives (this can be over-ridden by shortcode attributes and widget options).",'eventorganiser');?>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Group occurrences",'eventorganiser');?>:</th>
		<?php 	self::$settings['group_events'] = (isset(self::$settings['group_events']) ? self::$settings['group_events'] : '');?>
		<td> <label>
				<input type="checkbox" name="eventorganiser_options[group_events]" value="series" <?php checked('series', self::$settings['group_events']); ?>/>
				<?php _e("If selected only one occurrence of an event will be displayed on event lists and archives (this can be over-ridden by shortcode attributes and widget options).",'eventorganiser');?>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Are current events past?",'eventorganiser');?></th>
		<td> 
			<?php $runningsisnotpast = (empty(self::$settings['runningisnotpast']) ? 0 : self::$settings['runningisnotpast'])?>
				<input type="radio" id="whatispast-0" name="eventorganiser_options[runningisnotpast]" value="0" <?php checked('0', $runningsisnotpast); ?>/>
				<label for="whatispast-0"><?php _e('Yes');?></label>
				<input type="radio" id="whatispast-1" name="eventorganiser_options[runningisnotpast]" value="1" <?php checked('1', $runningsisnotpast); ?>/>
				<label for="whatispast-1"><?php _e('No');?></label></br>
				<?php _e("If 'no' is selected, an occurrence of an event is only past when it has finished. Otherwise, an occurrence is considered 'past' as soon as it starts.",'eventorganiser');?>
		</td>
	</tr>
	<tr>
		<th><?php _e("Delete expired events:",'eventorganiser');?></th>
		<td> <label>
				<input type="checkbox" name="eventorganiser_options[deleteexpired]" value="1" <?php checked('1', self::$settings['deleteexpired']); ?>/>
				<?php _e("If selected the event will be automatically trashed 24 hours after the last occurrence finishes.",'eventorganiser');?>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Enable events ICAL feed:",'eventorganiser');?></th>
		<td> 
				<input type="checkbox" name="eventorganiser_options[feed]" value="1" <?php checked('1', self::$settings['feed']); ?>/>
				<label> <?php printf(__('If selected, visitors can subscribe to your events with the url: %s','eventorganiser'), '<code>'.eo_get_events_feed().'</code>') ?></label>
		</td>
	</tr>

	<tr>
		<th><?php _e("Exclude events from searches:",'eventorganiser');?></th>
		<td> <label>
				<input type="checkbox" name="eventorganiser_options[excludefromsearch]" value="1" <?php checked('1', self::$settings['excludefromsearch']); ?>/>
		</label></td>
	</tr>
	<tr>
		<th><?php _e("Enable templates:",'eventorganiser');?></th>
		<td><input type="checkbox" name="eventorganiser_options[templates]" value="1" <?php checked('1', self::$settings['templates']); ?>/>
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
		<td> <input type="checkbox" name="eventorganiser_options[prettyurl]" value="1" <?php checked('1',self::$settings['prettyurl']); ?>/>
		<?php _e("If you have pretty permalinks enabled, select to have pretty premalinks for events.",'eventorganiser');?>
	</tr>
	<tr>
		<th><?php _e("Event (single)",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eventorganiser_options[url_event]" value="<?php echo eventorganiser_get_option('url_event');?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.eventorganiser_get_option('url_event').'</strong>/'.'[event_slug]' ;?></code></label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Event (archive)",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eventorganiser_options[url_events]" value="<?php echo eventorganiser_get_option('url_events');?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.eventorganiser_get_option('url_events').'</strong>' ;?></code></label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Venues",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eventorganiser_options[url_venue]" value="<?php echo eventorganiser_get_option('url_venue');?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.eventorganiser_get_option('url_venue').'</strong>/'.'[venue_slug]' ;?></code></label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Event Categories",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eventorganiser_options[url_cat]" value="<?php echo eventorganiser_get_option('url_cat');?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.eventorganiser_get_option('url_cat').'</strong>/'.'[event_cat_slug]' ;?></code></label>
		</td>
	</tr>
	<tr>
		<th><?php _e("Event Tags",'eventorganiser');?></th>
		<td> 
			<input type="text" name="eventorganiser_options[url_tag]" value="<?php echo eventorganiser_get_option('url_tag'); ?>" /> </br>
			<label><code> <?php echo $site_url.'/<strong>'.eventorganiser_get_option('url_tag').'</strong>/'.'[event_tag_slug]' ;?></code></label>
		</td>
	</tr>
</table>
	<p> <strong><?php _e("Please note that you will need go to WordPress Settings > Permalinks and click 'Save Changes' before any changes will take effect.",'eventorganiser');?></strong></p>
<?php

	}

	function footer_scripts(){
		?>
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
}
$settings_page = new EventOrganiser_Settings_Page();
?>
