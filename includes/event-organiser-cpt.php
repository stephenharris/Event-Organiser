<?php
 /*
* add's custom taxonomy and then custom post type
*/ 

//Register the custom taxonomy Event-category
add_action( 'init', 'create_event_taxonomies', 0 );
function create_event_taxonomies() {
  // Add new taxonomy, make it hierarchical (like categories)
  $labels = array(
    'name' => _x( 'Event Categories', 'taxonomy general name' ),
    'singular_name' => _x( 'Category', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Categories' ),
    'all_items' => __( 'All Categories' ),
    'parent_item' => __( 'Parent Category' ),
    'parent_item_colon' => __( 'Parent Category:' ),
    'edit_item' => __( 'Edit Category' ), 
    'update_item' => __( 'Update Category' ),
    'add_new_item' => __( 'Add New Category' ),
    'new_item_name' => __( 'New Category Name' ),
	'not_found' =>  __('No categories found'),
    'menu_name' => __( 'Categories' ),
  ); 	

register_taxonomy('event-category',array('event'), array(
	'hierarchical' => true,
	'labels' => $labels,
	'show_ui' => true,
	'query_var' => true,
	'capabilities'=>array(
		'manage_terms' => 'manage_event_categories',
		'edit_terms' => 'manage_event_categories',
		'delete_terms' => 'manage_event_categories',
		'assign_terms' =>'edit_events'),
	'public'=> true,
	'rewrite' => array( 'slug' => 'events/category', 'with_front' => false ),
  ));
}

//Register the custom post type Event
add_action('init', 'eventorganiser_cpt_register');
function eventorganiser_cpt_register() {
$eventorganiser_option_array = get_option('eventorganiser_options'); 
  	$labels = array(
		'name' => _x('Event', 'post type general name'),
		'singular_name' => _x('Event', 'post type singular name'),
		'add_new' => _x('Add New', 'event'),
		'add_new_item' => __('Add New Event'),
		'edit_item' => __('Edit Event'),
		'new_item' => __('New Event'),
		'all_items' => __('All events'),
		'view_item' => __('View Event'),
		'search_items' => __('Search events'),
		'not_found' =>  __('No events found'),
		'not_found_in_trash' => __('No events found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' =>  'Events'
  );

$exclude_from_search = ($eventorganiser_option_array['excludefromsearch']==0) ? false : true;

$args = array(
	'labels' => $labels,
	'public' => true,
	'publicly_queryable' => true,
	'exclude_from_search'=>$exclude_from_search,
	'show_ui' => true, 
	'show_in_menu' => true, 
	'query_var' => true,
	'capability_type' => 'event',
	'rewrite' => array(
		'slug'=> 'events/event',
		'with_front'=> false,
		'feeds'=> true,
		'pages'=> true
	),		
	'capabilities' => array(
		'publish_posts' => 'publish_events',
		'edit_posts' => 'edit_events',
		'edit_others_posts' => 'edit_others_events',
		'delete_posts' => 'delete_events',
		'delete_others_posts' => 'delete_others_events',
		'read_private_posts' => 'read_private_events',
		'edit_post' => 'edit_event',
		'delete_post' => 'delete_event',
		'read_post' => 'read_event',
	),
	'has_archive' => true, 
	'hierarchical' => false,
	'menu_icon' => EVENT_ORGANISER_URL.'/css/images/eoicon-16.png',
	'menu_position' => 5,
	'supports' => $eventorganiser_option_array['supports']
  ); 
  register_post_type('event',$args);
}



//add filter to ensure the text event, or event, is displayed when user updates a event 
add_filter('post_updated_messages', 'eventorganiser_messages');
function eventorganiser_messages( $messages ) {
	global $post, $post_ID;

	$messages['event'] = array(
    		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Event updated. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Event updated.'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Event restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Event published. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Event saved.'),
		8 => sprintf( __('Event submitted. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'),
		 // translators: Publish box date format, see http://php.net/date
      		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Event draft updated. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  	);
	return $messages;
}



//Meta capabilities for post type event
add_filter( 'map_meta_cap', 'eventorganiser_event_meta_cap', 10, 4 );
function eventorganiser_event_meta_cap( $caps, $cap, $user_id, $args ) {

	/* If editing, deleting, or reading a event, get the post and post type object. */
	if ( 'edit_event' == $cap || 'delete_event' == $cap || 'read_event' == $cap ) {
		$post = get_post( $args[0] );
		$post_type = get_post_type_object( $post->post_type );

		/* Set an empty array for the caps. */
		$caps = array();
	}

	/* If editing a event, assign the required capability. */
	if ( 'edit_event' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->edit_posts;
		else
			$caps[] = $post_type->cap->edit_others_posts;
	}

	/* If deleting a event, assign the required capability. */
	elseif ( 'delete_event' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->delete_posts;
		else
			$caps[] = $post_type->cap->delete_others_posts;
	}

	/* If reading a private event, assign the required capability. */
	elseif ( 'read_event' == $cap ) {

		if ( 'private' != $post->post_status )
			$caps[] = 'read';
		elseif ( $user_id == $post->post_author )
			$caps[] = 'read';
		else
			$caps[] = $post_type->cap->read_private_posts;
	}

	/* Return the capabilities required by the user. */
	return $caps;
}


// Rewrite rules for venues page
add_action('generate_rewrite_rules', 'eventorganiser_create_rewrite_rules');
function eventorganiser_create_rewrite_rules() {
	global $wp_rewrite;
 
	// add rewrite tokens
	$keytag = '%venue%';
	$wp_rewrite->add_rewrite_tag($keytag, '(.+?)', 'post_type=event&venue=');
 
	$keywords_structure = $wp_rewrite->root . "events/venue/$keytag/";
	$keywords_rewrite = $wp_rewrite->generate_rewrite_rules($keywords_structure);
 
	$wp_rewrite->rules = $keywords_rewrite + $wp_rewrite->rules;
	return $wp_rewrite->rules;
}


// This adds the Event Organiser icon to the page head
add_action('admin_head', 'eventorganiser_plugin_header_image');
function eventorganiser_plugin_header_image() {
        global $post_type;
	?>
	<style>
	<?php if (($_GET['post_type'] == 'event') || ($post_type == 'event')) : ?>
	#icon-edit { background:transparent url('<?php echo EVENT_ORGANISER_URL.'/css/images/eoicon-32.png';?>') no-repeat; }		
	<?php endif; ?>
        </style>
        <?php
}

// Filter wp_nav_menu() to add event link if selected in options
$eo_settings_array= get_option('eventorganiser_options'); 
if($eo_settings_array['addtomenu']){
	add_filter( 'wp_list_pages', 'eventorganiser_menu_link' );
	add_filter( 'wp_nav_menu_items', 'eventorganiser_menu_link' );
}
function eventorganiser_menu_link($items) {
	global $wp_query;
	$class ='menu-item menu-item-type-event';
	if(isset($wp_query->query_vars['post_type'])&&$wp_query->query_vars['post_type']=='event') $class = 'current_page_item';
		$eventlink = '<li class="'.$class.'"><a href="'.EO_Event::link_structure().'">Events</a></li>';
		$items = $items . $eventlink;
	return $items;
}

/*
 * Add contextual help
*/
add_action( 'contextual_help', 'eventorganiser_cpt_help_text', 10, 3 );
function eventorganiser_cpt_help_text($contextual_help, $screen_id, $screen) { 

	switch($screen->id):
		//Add help for event editing / creating page
		case ('event'):
			    $screen->add_help_tab( array(
			        'id'      => 'creating-events', 
			        'title'   => 'Creating events',
        			'content' => '<p>' . __('Creating events:') . '</p>'.
			'<ul>' .
				'<li>' . __('The start date is the date the event starts. If the event is a reoccuring event, this is the start date of the first occurrence.') . '</li>' .
				'<li>' . __('The end date is the date the event finishes. If the event is a reoccuring event, this is the end date of the first occurrence.') . '</li>' .
				'<li>' . __('All dates and times must be entered in the specified format. This format can changed in the settings page.') . '</li>' .
			'</ul>'
				));
			    $screen->add_help_tab( array(
			        'id'      => 'repeating-events',
			        'title'   => 'Repeating events',
        			'content' => '<p>' . __('To repeat an event according to some regular pattern, use the reocurrence dropdown menu to select how the event is to repeat. Further options then appear, ') . '</p>' .
			'<ul>' .
				'<li>' . __('Specify how regularly the event should repeat (default 1)') . '</li>' .
				'<li>' . __('Choose the reoccurrence end date. No further occurrences are added after this date, but an occurrence that starts before may finish after this date.') . '</li>' .
				'<li>' . __('If monthly reoccurrence is selected, select whether this should repeat on that date of the month (e.g. on the 24th) or on the day of the month (e.g. on the third Tuesday) ') . '</li>' .
				'<li>' . __('If weekly reoccurrence is selected, select which days of the week the event should be repeated. If no days are selected, the day of the start date is used') . '</li>' .
			'</ul>'
				));
			    $screen->add_help_tab( array(
			        'id'      => 'selecting-venues', 
			        'title'   => 'Selecting a venue',
        			'content' => '<p>' . __('Selecting a venue') . '</p>' .
					'<ul>' .
						'<li>' . __('Use the venues input field to search for existing venues') . '</li>' .
						'<li>' . __('Only pre-existing venues can be selected. To add a venue, go to the venues page.') . '</li>' .
					'</ul>'
				));
			break;

		//Add help for event admin table page
		case ('edit-event'):

			$screen->add_help_tab( array(
				'id'=>'overview',
				'title'=>'Overview',
				'content'=>'<p>' . __('This is the list of all saved events. Note that ').'<strong>'.__('reoccurring events appear as a single row').'</strong>'.__(' in the table and the start and end date refers to the first occurrence of that event.') . '</p>' ));

			    $screen->add_help_tab( array(
			        'id'      => 'screen-content', // This should be unique for the screen.
			        'title'   => 'Screen Content',
        			'content' => '<p>' . __('You can customize the display of this screen in a number of ways:') . '</p>' .
			'<ul>' .
				'<li>' . __('You can hide/display columns based on your needs and decide how many posts to list per screen using the Screen Options tab.') . '</li>' .
				'<li>' . __('You can filter the list of posts by post status using the text links in the upper left to show All, Published, Draft, or Trashed posts. The default view is to show all posts.') . '</li>' .
				'<li>' . __('You can refine the list to show only events in a specific category or at a specific venue by using the dropdown menus above the posts list. Click the Filter button after making your selection. You also can refine the list by clicking on the event organiser or category in the posts list.') . '</li>' .
			'</ul>' ));

			    $screen->add_help_tab( array(
			        'id'      => 'available-action', // This should be unique for the screen.
			        'title'   => 'Available Actions',
        			'content' => '<p>' . __('Hovering over a row in the events list will display action links that allow you to manage your post. You can perform the following actions:') . '</p>' .
					'<ul>' .
						'<li>' . __('Edit takes you to the editing screen for that event. You can also reach that screen by clicking on the event title.') . '</li>' .
						'<li>' . __('Trash removes your event from this list and places it in the trash, from which you can permanently delete it.') . '</li>' .
						'<li>' . __('Preview will show you what your event page will look like if you publish it. View will take you to your live site to view the event. Which link is available depends on your event&#8217;s status.') .'</li>' .
						'</ul>' ));
			break;

		//Add help for venue admin table page
		case ('event_page_venues'):
			$contextual_help = 
			'<p>'.__("You can hide/display columns based on your needs and decide how many posts to list per screen using the Screen Options tab").'</p>'.
			'<p>' . __("Hovering over a row in the posts list will display action links that allow you to manage your post. You can perform the following actions:") . '</p>' .
			'<ul>' .
				'<li>' . __('Edit takes you to the editing screen for that venue. You can also reach that screen by clicking on the post title.') . '</li>' .
				'<li>' . __('Delete permanently remove the venue') . '</li>' .
				'<li>' . __("View will take you to the venue's page") . '</li>' .
			'</ul>';
			break;

		//Add help for calendar view
		case ('event_page_calendar'):
			$screen->add_help_tab( array(
				'id'=>'overview',
				'title'=>'Overview',
				'content'=>'<p>' . __("This page shows all (occurrances of) events. You can view the summary of an event by clicking on it. If you have the necessary permissions, a link to the event's edit page will appear also.") . '</p>' .
			'<p>' . __("By clicking the relevant tab, you can view events in Month, Week or Day mode.") . '</p>' 
			));

		case ('event_page_calendar'):
			$screen->add_help_tab( array(
				'id'=>'add-event',
				'title'=>'Add Event',
				'content'=>'<p>' . __("You can create an event on this Calendar, by clicking on day or dragging over multiple days (in Month view) or multiple times (in Week and Day view). This can be immediately published or saved as a draft. In any case, the event is created and you are forwarded that that event’s edit page.") . '</p>' 
			));
			break;

		//Add help for settings page
		case ('settings_page_event-settings'):
			$contextual_help = 
			'<p>' . __('Here you can customise the settings of the Event Organiser plug-in. You can ') . '</p>' .
			'<ul>' .
				'<li>' . __('Assign permissions to user roles.') . '</li>' .
				'<li>' . __('Select what features the Event post type shoul support (thumbnails, comments etc .') . '</li>' .
				'<li>' . __("Specify general settings such as date format on the event edit page.") . '</li>' .
			'</ul>'.
			'<p>' . __("If there are other options you would like to appear here, then visit the plugin's forum") . '</p>' ;
			break;

		//Add help for event category page
		case ('edit-event-category'):
			$contextual_help = 
			'<p>' . __('When adding a new category on this screen, you’ll fill in the following fields:') . '</p>' .
			'<ul>' .
				'<li>' . __('    Name - The name is how it appears on your site.') . '</li>' .
				'<li>' . __('Slug - The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.') . '</li>' .
				'<li>' . __(" Parent - Categories can have a hierarchy. You might have a comedy category, and under that have children categories for stand-up and sketch shows. To create a subcategory, just choose another category from the Parent dropdown.") . '</li>' .
				'<li>' . __("Description - The description is not prominent by default; however, some themes may display it.") . '</li>' .
			'</ul>'.
			'<p>' . __('You can change the display of this screen using the Screen Options tab to set how many items are displayed per screen and to display/hide columns in the table.') . '</p>' ;
			break;

	endswitch;

	//Add a link to Event Organiser documentation on every page
	$screen->set_help_sidebar( '<p> <strong> For more information</strong> </p><p>See the <a target="_blank" href="http://www.harriswebsolutions.co.uk/event-organiser/documentation/"> documentation</a></p>');

	return $contextual_help;
}
?>
