<?php

/**
 * Do actions
 * Hooked on to venues page
 *
 * @since 1.0.0
 */
function eventorganiser_venues_action() {
	global $EO_Venues,$EO_Venue;
	$EO_Venues = new EO_Venues;
	$EO_Venue = new EO_Venue;
	$screen = get_current_screen();

	add_filter('manage_event_page_venues_columns','mycolumns') ;
	function mycolumns($columns){
		 $columns = array(
       	     'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
       	     'name'  => 'Venue',
       	     'venue_address'     => 'Address',
       	     'venue_postal'     => 'Post Code',
       	     'venue_country'     => 'Country',
       	     'venue_slug'     => 'Slug',
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

	if( isset($_REQUEST['action']) && isset($_REQUEST['venue'])):	

		//In the backend, venue should always be the ID not the slug
		if(is_array($_REQUEST['venue']))
			$_REQUEST['venue'] = array_map('intval', $_REQUEST['venue']);
		else
			$_REQUEST['venue'] = (int) $_REQUEST['venue'];

		if(current_user_can('manage_venues')):	
			if($_REQUEST['action']=='update'){
				$EO_Venue->update($_REQUEST['venue'], $_POST['eo_venue']);

			}elseif($_REQUEST['action']=='add'){
				 $EO_Venue = $EO_Venue->add($_POST['eo_venue']); 
	
			}else{
				$venues = EO_Venues::doaction($_REQUEST['venue'], $_REQUEST['action']);
			}
		else:
			wp_die("You do not have permission to manage venues");
		endif;
	endif;

	add_screen_option( 'per_page', array('label' => 'Venues', 'default' => 20) );	
}



function eventorganiser_venues_page(){

?>    
    <div class="wrap">
		<div id='icon-edit' class='icon32'><br/>
		</div>
	
	<?php	
		if( isset($_REQUEST['action']) && ( (($_REQUEST['action'] == "edit"||$_REQUEST['action'] == "update"  )&& isset($_REQUEST['venue'])) || $_REQUEST['action'] == "create")) :

			//Display relevant title for edit / create
			if($_REQUEST['action'] == "edit"): ?>
				<h2>
					<?php _e('Edit Venue', 'eventorganiser'); ?>
					<a href="edit.php?post_type=event&page=venues&action=create" class="add-new-h2"><?php _e('Add New', 'eventorganiser'); ?></a>
				</h2>

			<?php else:
				echo '<h2>Add New Venue</h2>';
			endif; 	?>
			<div id="poststuff" class="metabox-holder">
				<div id="post-body">
					<div id="post-body-content">
					<?php event_organiser_display_venue_form();?>
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
		$search_term = false;
		if ( isset($_REQUEST['s']) && $_REQUEST['s'] )	
			$search_term = esc_attr($_REQUEST['s']);?>

		<h2>Venues <a href="edit.php?post_type=event&page=venues&action=create" class="add-new-h2"><?php _e('Add New', 'eventorganiser'); ?></a> 
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
			$venue_table->search_box( 'Search Venues','s' );
			$venue_table->display(); ?>
		 </form>
		<?php endif;?>

    </div><!--End .wrap -->
    <?php
}



/**
 * Display form for creating / editing venues
 * Uses global $EO_Venue object to display venue.
 *
 * @since 1.0.0
 */
function event_organiser_display_venue_form(){
	global $EO_Venue;

	//Set the action of the form		
	$do = ($_REQUEST['action']=='edit' ? 'update' : 'add');?>

	<form name="venuedetails" class ="metabox-holder" id="eo_venue_form" method="post" action="<?php echo network_site_url('wp-admin/edit.php?post_type=event&page=venues'); ?>">  
		<input type="hidden" name="action" value="<?php echo $do; ?>"> 
		<input type="hidden" name="eo_venue[id]" value="<?php echo $EO_Venue->id;?>">  
		<input type="hidden" name="venue" value="<?php echo $EO_Venue->id;?>">  
		<?php wp_nonce_field('eventorganiser-edit-venue');?>

		<div id="titlediv">
			<div id="titlewrap">
				<!--<label for="title" id="title-prompt-text" style="visibility:hidden" class="hide-if-no-js">Enter venue name here</label>-->
				<input type="text" placeholder="Venue name" autocomplete="off" id="title" value="<?php echo $EO_Venue->name;?>" tabindex="1" size="30" name="eo_venue[Name]">
			</div>
			<div class="inside">
				<div id="edit-slug-box">
					<?php if($EO_Venue->id): ?>
						<strong>Permalink:</strong> 
							<span id="sample-permalink">
								<?php $EO_Venue->the_structure(); ?>
								<input type="text" name="eo_venue[slug]"value="<?php echo $EO_Venue->slug;?>" id="<?php echo $EO_Venue->name; ?>-slug">
							</span> 
						<input type="hidden" value="<?php $EO_Venue->the_link(); ?>" id="shortlink">
						<a onclick="prompt('URL:', jQuery('#shortlink').val()); return false;" class="button" href="">Get Link</a>	
						<span id='view-post-btn'><a href="<?php $EO_Venue->the_link(); ?>" class='button' target='_blank'>View Venue</a></span>
					<?php endif;?>					
				</div>
			</div>
		</div>

		<div class="postbox " id="venue_address">
			<h3 class="hndle"><span>Venue Location</span></h3>
			<table class ="inside address">
				<tbody>
					<tr>
						<th><label>Address:</label></th>
						<td><input name="eo_venue[Add]" class="eo_addressInput" id="eo_venue_add"  value="<?php echo $EO_Venue->address;?>"/></td>
					</tr>
					<tr>
						<th><label>Postcode:</label></th>
						<td><input name="eo_venue[PostCode]" class="eo_addressInput" id="eo_venue_pcode"  value="<?php echo $EO_Venue->postcode;?>"/></td>
					</tr>
					<tr>
						<th><label>Country:</label></th>
						<td><?php $EO_Venue->country_select();?></td>
					</tr>
				</tbody>
			</table>
			<div id="venuemap" class="gmap3"></div>
			<div class="clear"></div>
		</div>

		<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="venue_description postarea">
			<?php wp_editor($EO_Venue->display_description('edit'), 'content', array('textarea_name'=>'eo_venue[content]','dfw' => false, 'tabindex' => 1) ); ?>
		</div>

		<input type="hidden" name="eo_venue[Lat]" id="eo_venue_Lat"  value="<?php echo $EO_Venue->latitude;?>"/>
		<input type="hidden" name="eo_venue[Lng]" id="eo_venue_Lng"  value="<?php echo $EO_Venue->longitude;?>"/>
		
		<p class="submit">  
			<input type="submit" class="button-primary" name="eo_venue[Submit]" value="<?php if($do=='update') esc_attr_e('Update Venue'); else esc_attr_e('Add Venue'); ?>" />  
		</p> 
 
	</form> 		
	<?php
}?>
