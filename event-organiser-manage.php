<?php
/**
 * Functions altering the CPT Event table
 *
 * @since 1.0.0
 */


/**
 * Adds custom columns to Event CPT table
 *
 * @since 1.0.0
 */
add_filter('manage_edit-event_columns', 'eventorganiser_event_add_columns');
function eventorganiser_event_add_columns($columns) {

	//Unset unnecessary columns
	unset($columns['date']);
	unset($columns['categories']);

	//Set 'title' column title
	$columns['title'] = _x('Event', 'column name');

	//If displaying 'author', change title
	if(isset($columns['author']))
		$columns['author'] = __('Organizer');

	$columns['venue'] = _x('Venue', 'column name');
	$columns['eventcategories'] = __('Categories');
	$columns['datestart'] = _x('Start Date/Time ', 'column name');
	$columns['dateend'] = _x('End Date/Time', 'column name');
	$columns['reoccurence'] = _x('Reoccurence', 'column name');

	return $columns;
}


/**
 * Registers the custom columns in Event CPT table to be sortable
 *
 * @since 1.0.0
 */
add_filter( 'manage_edit-event_sortable_columns', 'eventorganiser_event_sortable_columns' );
function eventorganiser_event_sortable_columns( $columns ) {
	$columns['datestart'] = 'eventstart';
	$columns['dateend'] = 'eventend';
 
	return $columns;
}



/**
 * What to display in custom columns of Event CPT table
 *
 * @since 1.0.0
 */
add_action('manage_event_posts_custom_column', 'eventorganiser_event_sort_columns', 10, 2);
function eventorganiser_event_sort_columns($column_name, $id) {
	global $post;

	$EO_Venue =new EO_Venue((int)$post->Venue);

	$phpFormat = 'M, jS Y';
	if(!$post->event_allday)
		$phpFormat .= '\<\/\b\r\>'. get_option('time_format');

	switch ($column_name) {
		case 'venue':
			echo "<a href='".add_query_arg( 'venue_id', $EO_Venue->id )."'>".$EO_Venue->name."</a>";
			break;

		case 'datestart':
			eo_the_start($phpFormat);
			break;
		
		case 'dateend':
			eo_the_end($phpFormat);
			break;

		case 'reoccurence':
			$summary =eo_get_schedule_summary();
			if($summary && $post->event_schedule!='once')
				$summary= 'Every '.$summary;
			echo $summary;
			break;

		case 'eventcategories':
		    	$terms = get_the_terms($post->ID, 'event-category');
 			
			if ( !empty($terms) ) {
       	 		foreach ( $terms as $term )
			            $post_terms[] = "<a href='".add_query_arg( 'event-category', $term->slug)."'>".esc_html(sanitize_term_field('name', $term->name, $term->term_id,'event-category', 'edit'))."</a>";
			        echo join( ', ', $post_terms );
			}
			break;

	default:
		break;
	} // end switch
}


add_action( 'restrict_manage_posts', 'eventorganiser_restrict_by_category' );
function my_restrict_manage_posts() {
    global $typenow,$wp_query;

	// only display these taxonomy filters on desired custom post_type listings
	if (!empty($typenow) && $typenow=='event') :

            // retrieve the taxonomy object
            $tax_obj = get_taxonomy('event-category');
            $tax_name = $tax_obj->labels->name;

            // retrieve array of term objects per taxonomy
            $terms = get_terms('event-category');
		 $selected=0;
	
		if(isset( $wp_query->query_vars['event-category'])) $selected = $wp_query->query_vars['event-category'];?>

		<select name='event-category' id='event-category' class='postform' style="width:150px;">
			<option <?php selected($selected,0); ?> value="0"><?php _e('View all categories', 'eventorganiser');?></option>
			<?php foreach ($terms as $term): ?>
				<option value="<?php echo $term->slug;?>" <?php selected($selected,$term->slug)?>> <?php echo $term->name;?> </option>
			<?php endforeach; ?>
		</select>
		<?php
    }
}


/**
 * Adds a drop-down filter to the Event CPT table
 *
 * @since 1.0.0
 */
add_action('restrict_manage_posts','eventorganiser_restrict_by_venue');
function restrict_events_by_venue() {
	global $typenow;
	global $wp_query,$wpdb, $eventorganiser_venue_table;

	//Only add if CPT is event
	if (!empty($typenow) && $typenow=='event') :
		$selected=0;
		if(!empty( $wp_query->query_vars['venue_id'] )) {
			$selected = $wp_query->query_vars['venue_id'];
		}
		$The_Venues = $wpdb->get_results(" SELECT* FROM $eventorganiser_venue_table");?>

		<select id="HWSEventFilterVenue" name="venue_id" style="width:150px;">
			<option <?php selected($selected,0); ?> value=""><?php _e('View all venues', 'eventorganiser');?></option>
			<?php foreach ($The_Venues as $index=>$venue): ?>
				 <option value="<?php echo intval($venue->venue_id); ?>" <?php selected($venue->venue_id,$selected); ?> >
					<?php _e($venue->venue_name,'eventorganiser'); ?>
				</option>
			<?php endforeach;  //End foreach $EventVenues?>
		</select>
	<?php endif; //End if CPT is event
}
?>
