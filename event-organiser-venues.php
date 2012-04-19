<?php
/****** VENUE PAGE ******/
if(!class_exists('EventOrganiser_Admin_Page')){
    require_once(EVENT_ORGANISER_DIR.'classes/class-eventorganiser-admin-page.php' );
}
class EventOrganiser_Venues_Page extends EventOrganiser_Admin_Page
{
	function set_constants(){
		$this->hook = 'edit.php?post_type=event';
		$this->title =  __('Venues','eventorganiser');
		$this->menu = __('Venues','eventorganiser');
		$this->permissions ='manage_venues';
		$this->slug ='venues';
	}

	/*
	* Actions to be taken prior to page loading. Hooked on to load-{page}
	*/
	function page_actions(){	
		add_filter('manage_event_page_venues_columns','eventorganiser_venue_admin_columns') ;
		function eventorganiser_venue_admin_columns($columns){
			 $columns = array(
       		     'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
       		     'name'  => __('Venue', 'eventorganiser'),
       		     'venue_address'     =>__('Address', 'eventorganiser'),
       		     'venue_postal'     => __('Post Code', 'eventorganiser'),
       		     'venue_country'     => __('Country', 'eventorganiser'),
       		     'venue_slug'     =>__('Slug'),
       		     'posts'     =>__('Events', 'eventorganiser'),
       		 );
       	 return $columns;	
		}

		//Determine action if any
		if(isset($_POST['screen-options-apply'])&& $_POST['screen-options-apply']=='Apply'){
			if(check_admin_referer('screen-options-nonce','screenoptionnonce')):
				global $current_user;
				$option = $_POST['wp_screen_options']['option'];
				$value = intval($_POST['wp_screen_options']['value']);
				update_user_option($current_user->ID,$option,$value);
			endif;
		}
		$action = $this->current_action();
		$venue =  (isset($_REQUEST['event-venue']) ? $_REQUEST['event-venue'] : false);

		if( ($action && $venue) || $action=='add'):	

			if(!current_user_can('manage_venues'))
				wp_die(__("You do not have permission to manage venues",'eventorganiser'));

			switch($action):
				case 'update':
					if( !check_admin_referer('eventorganiser_update_venue_'.$venue))
						wp_die( __("You do not have permission to edit this venue.",'eventorganiser'));

					$inserted = EO_Venue::update($venue,$_POST['eo_venue']);
					break;

				case 'add':
					if( !check_admin_referer('eventorganiser_add_venue'))
						wp_die( __("You do not have permission to edit this venue.",'eventorganiser'));
	
					$inserted = EO_Venue::insert($_POST['eo_venue']);
					break;
	
				case 'delete':
					if(is_array($_REQUEST['event-venue']))
						$nonce ='bulk-venues';
					else
						$nonce =  'eventorganiser_delete_venue_'.$venue;
	
					if( !check_admin_referer($nonce))
						wp_die(__("You do not have permission to delete this venue",'eventorganiser'));
	
					$deleted = EO_Venue::delete($venue);
					break;
				endswitch;
		endif;
	
		add_screen_option( 'per_page', array('label' => __('Venues','eventorganiser'), 'default' => 20) );
	}


	function page_scripts(){
		$action = $this->current_action();
		if(in_array($action,array('create','edit','add','update'))):
			wp_enqueue_script('eo_venue');
			wp_localize_script( 'eo_venue', 'EO_Venue', array( 'draggable' => true));
			wp_enqueue_style('eventorganiser-style');
			wp_enqueue_script('media-upload');
			add_thickbox();	
		endif;
	}


	function display(){
	?>
    <div class="wrap">
		<div id='icon-edit' class='icon32'><br/>
		</div>
	<?php	
		$action = $this->current_action();
		$venue =  (isset($_REQUEST['event-venue']) ? $_REQUEST['event-venue'] : false);

		if(  (( $action== "edit"||$action == "update" )&& $venue )  || $action == "create" ):

			//Display relevant title for edit / create
			if($action== "edit"): ?>
				<h2>
					<?php _e('Edit Venue', 'eventorganiser'); ?>
					<a href="edit.php?post_type=event&page=venues&action=create" class="add-new-h2"><?php _ex('Add New','post'); ?></a>
				</h2>
			<?php else:
				echo '<h2>'.__('Add New Venue','eventorganiser').'</h2>';
			endif; 	?>

			<div id="poststuff" class="metabox-holder">
				<div id="post-body">
					<div id="post-body-content">
					<?php $this->edit_form($venue);?>
					</div>
				</div>
			</div>
			<?php
		else: 

		//Else we are not creating or editing. Display table  
		$venue_table = new EO_List_Table();

	    	//Fetch, prepare, sort, and filter our data...
	    	$venue_table->prepare_items();    
	
		//Check if we have searched the venues
		$search_term = ( isset($_REQUEST['s']) ?  esc_attr($_REQUEST['s']) : '');?>

		<h2><?php _e('Venues','eventorganiser');?> <a href="edit.php?post_type=event&page=venues&action=create" class="add-new-h2"><?php _ex('Add New','post'); ?></a> 
		<?php
			if ($search_term)
				printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>',$search_term) ?>
		</h2>
  
       	 <form id="eo-venue-table" method="get">
       	     <!-- Ensure that the form posts back to our current page -->
       	     <input type="hidden" name="page" value="venues" />
       	     <input type="hidden" name="post_type" value="event" />

       	     <!-- Now we can render the completed list table -->
       	     <?php 
			$venue_table->search_box( __('Search Venues','eventorganiser'),'s' );
			$venue_table->display(); ?>
		 </form>
		<?php endif;?>

    </div><!--End .wrap -->
    <?php
	}


/**
 * Display form for creating / editing venues
 *
 * @since 1.0.0
 */
function edit_form($venue=''){

	$venue = get_term_by('slug',$venue,'event-venue');

	//Set the action of the form		
	$do = ($this->current_action()=='edit' ? 'update' : 'add');
	$nonce = ($do=='update' ? 'eventorganiser_update_venue_'.$venue->slug : 'eventorganiser_add_venue');
	?>
	<form name="venuedetails" class ="metabox-holder" id="eo_venue_form" method="post" action="<?php echo site_url('wp-admin/edit.php?post_type=event&page=venues'); ?>">  
		<input type="hidden" name="action" value="<?php echo $do; ?>"> 
		<input type="hidden" name="eo_venue[venue_id]" value="<?php echo ( isset($venue->term_id) ? $venue->term_id : '' ) ;?>">  
		<input type="hidden" name="event-venue" value="<?php echo ( isset($venue->slug) ? $venue->slug : '' ) ;?>">  
		<?php wp_nonce_field($nonce);?>

		<div id="titlediv">
			<div id="titlewrap">
				<input type="text" placeholder="<?php __('Venue name','eventorganiser');?>" autocomplete="off" id="title" value="<?php echo isset($venue->name) ? $venue->name:'' ;?>" tabindex="1" size="30" name="eo_venue[venue_name]">
			</div>
			<div class="inside">
				<div id="edit-slug-box">
					<?php if($venue): ?>
						<strong><?php _e('Permalink:');?></strong> 
						<span id="sample-permalink">
							<?php echo eo_get_venue_permastructure();?>
							<input type="text" name="eo_venue[venue_slug]"value="<?php echo (isset( $venue->slug ) ? esc_attr($venue->slug) : '' ) ;?>" id="<?php echo $venue->term_id; ?>-slug">
						</span> 
	
						<input type="hidden" value="<?php echo get_term_link( $venue,'event-venue'); ?>" id="shortlink">
						<a onclick="prompt('URL:', jQuery('#shortlink').val()); return false;" class="button" href=""><?php _e('Get Link','eventorganiser');?></a>	
						<span id='view-post-btn'><a href="<?php echo get_term_link( $venue,'event-venue'); ?>" class='button' target='_blank'><?php _e('View Venue','eventorganiser');?></a></span>
					<?php endif;?>					
				</div>
			</div>
		</div>

		<div class="postbox " id="venue_address">
			<h3 class="hndle"><span><?php _e('Venue Location','eventorganiser');?></span></h3>
			<table class ="inside address">
				<tbody>
					<tr>
						<th><label><?php _e('Address','eventorganiser');?>:</label></th>
						<td><input name="eo_venue[venue_address]" class="eo_addressInput" id="eo_venue_add"  value="<?php echo isset($venue->venue_address) ? $venue->venue_address:'' ;?>"/></td>
					</tr>
					<tr>
						<th><label><?php _e('Post Code','eventorganiser');?>:</label></th>
						<td><input name="eo_venue[venue_postal]" class="eo_addressInput" id="eo_venue_pcode"  value="<?php echo isset($venue->venue_postal) ? $venue->venue_postal : '';?>"/></td>
					</tr>
					<tr>
						<th><label><?php _e('Country','eventorganiser');?>:</label></th>
						<td><input name="eo_venue[venue_country]" class="eo_addressInput" id="eo_venue_country"  value="<?php echo isset($venue->venue_country) ? $venue->venue_country : '';?>"/></td>
					</tr>
				</tbody>
			</table>
			<div id="venuemap" class="gmap3"></div>
			<div class="clear"></div>
		</div>

		<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="venue_description postarea">
			<?php wp_editor(isset($venue->venue_description) ? $venue->venue_description :'' , 'content', array('textarea_name'=>'eo_venue[venue_description]','dfw' => false, 'tabindex' => 1) ); ?>
		</div>

		<input type="hidden" name="eo_venue[venue_lat]" id="eo_venue_Lat"  value="<?php echo isset($venue->venue_lat) ?  $venue->venue_lat : '';?>"/>
		<input type="hidden" name="eo_venue[venue_lng]" id="eo_venue_Lng"  value="<?php echo isset($venue->venue_lng) ?  $venue->venue_lng : '';?>"/>
		
		<p class="submit">  
			<input type="submit" class="button-primary" name="eo_venue[Submit]" value="<?php if($do=='update') _e('Update Venue','eventorganiser'); else _e('Add Venue','eventorganiser'); ?>" />  
		</p> 
 
	</form> 		
	<?php
	}
}
$venues_page = new EventOrganiser_Venues_Page();

function eo_get_venue_permastructure(){
	global  $wp_rewrite;
	$termlink = $wp_rewrite->get_extra_permastruct('event-venue');
	if( empty($termlink) ){
		$t = get_taxonomy('event-venue');
		$termlink = "?$t->query_var=";
	}else{
		$termlink = preg_replace('/%event-venue%/','',$termlink);
	}
	$termlink = home_url($termlink);

	return $termlink;
}
