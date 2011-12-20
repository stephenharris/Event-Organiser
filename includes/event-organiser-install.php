<?php
 function eventorganiser_install(){
       global $wpdb, $eventorganiser_version, $eventorganiser_venue_table, $eventorganiser_events_table;
	$table_posts = $wpdb->prefix . "posts";

	$charset_collate = '';
	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";

	//Events table
	$sql_events_table = "CREATE TABLE " .$eventorganiser_events_table. " (
		event_id bigint(20) NOT NULL AUTO_INCREMENT,
		post_id bigint(20) NOT NULL,
		Venue bigint(20) NOT NULL,
		StartDate DATE NOT NULL,
		EndDate DATE NOT NULL,
		StartTime TIME NOT NULL,
		FinishTime TIME NOT NULL,
		event_schedule text NOT NULL,
		event_schedule_meta text NOT NULL,
		event_frequency smallint NOT NULL,
		event_occurrence bigint(20) NOT NULL,
		event_allday TINYINT(1) NOT NULL,
		reoccurrence_start DATE NOT NULL,
		reoccurrence_end DATE NOT NULL,
		PRIMARY KEY  (event_id),
		CONSTRAINT ".$table_posts."
		FOREIGN KEY (post_id)
		REFERENCES ".$table_posts."(ID)
		ON DELETE CASCADE )".$charset_collate;
	
	//Venue table
	$sql_venue_table = "CREATE TABLE " . $eventorganiser_venue_table. " (
	  venue_id bigint(20) NOT NULL AUTO_INCREMENT,
	  venue_name text NOT NULL,
	  venue_slug text NOT NULL,
	  venue_address text NOT NULL,
	  venue_postal text NOT NULL,
	  venue_country text NOT NULL,
	  venue_lng FLOAT( 10, 6 ) NOT NULL DEFAULT 0,
	  venue_lat FLOAT( 10, 6 ) NOT NULL DEFAULT 0,
	  venue_owner bigint(20) NOT NULL,
	  venue_description longtext NOT NULL,
	  PRIMARY KEY  (venue_id) )".$charset_collate;

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql_events_table);
   dbDelta($sql_venue_table);
	

	//Add options and capabilities
	$eventorganiser_options = array (	
		'supports' => array('title','editor','author','thumbnail','excerpt','custom-fields','comments'),
		'event_redirect' => 'events',
		'dateformat'=>'dd-mm',
		'prettyurl'=> 1,
		'templates'=> 1,
		'addtomenu'=> 0,
		'excludefromsearch'=>0,
		'showpast'=> 0
	);
	add_option("eventorganiser_version",$eventorganiser_db_version);
	add_option('eventorganiser_options',$eventorganiser_options);
			
	global $wp_roles,$eventorganiser_roles;	
	$all_roles = $wp_roles->roles;
	foreach ($all_roles as $role_name => $display_name):
		$role = $wp_roles->get_role($role_name);
		if($role->has_cap('manage_options')){
			foreach($eventorganiser_roles as $eo_role=>$eo_role_display):
				$role->add_cap($eo_role);
			endforeach;  
		}
	endforeach;  //End foreach $all_roles
}


function eventorganiser_deactivate(){
    }


function eventorganiser_uninstall(){
	global $wpdb,$eventorganiser_venue_table, $eventorganiser_events_table,$eventorganiser_roles, $wp_roles,$wp_taxonomies;

	//Drop tables    
	$wpdb->query("DROP TABLE IF EXISTS $eventorganiser_events_table");
	$wpdb->query("DROP TABLE IF EXISTS $eventorganiser_venue_table");

	//Remove all posts of CPT Event
	//?? $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'event'");

	//Delete options
	delete_option('eventorganiser_options');
	delete_option('eventorganiser_version');
	delete_option('eo_notice');
	delete_option('widget_eo_calendar_widget');
	delete_option('widget_eo_list_widget');

	//Remove Event Organiser capabilities
	$all_roles = $wp_roles->roles;
	foreach ($all_roles as $role_name => $display_name):
		$role = $wp_roles->get_role($role_name);
		foreach($eventorganiser_roles as $eo_role=>$eo_role_display):
			$role->remove_cap($eo_role);
		endforeach;  
	endforeach; 

	//Remove 	event category and terms
	$terms = get_terms( 'event-category', 'hide_empty=0' );
		foreach ($terms as $term) {
			wp_delete_term( $term->term_id, 'event-category');
		}
		unset($wp_taxonomies['event-category']);

	//Remove user-meta-data:
	$meta_keys = array('metaboxhidden_event','closedpostboxes_event','wp_event_page_venues_per_page','manageedit-eventcolumnshidden');	
	$sql =$wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE ");
	foreach($meta_keys as $key):
		$sql .= $wpdb->prepare("meta_key = %s OR ",$key);
	endforeach;
	$sql.=" 1=0 "; //Deal with final 'OR', must be something false!
	$re =$wpdb->get_results( $sql);	

    }
?>
