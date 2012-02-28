<?php
/**
 * Functions altering the CPT Event table
 *
 * @since 1.0.0
 */

/**
 * Adds custom columns to Event CPT table
 * @since 1.0.0
 */
add_filter('manage_edit-event_columns', 'eventorganiser_event_add_columns');
function eventorganiser_event_add_columns($columns) {

	unset($columns['date']);//Unset unnecessary columns

	//Set 'title' column title
	$columns['title'] =__('Event','eventorganiser');

	//If displaying 'author', change title
	if(isset($columns['author']))
		$columns['author'] = __('Organiser','eventorganiser');

	$columns['venue'] = __('Venue','eventorganiser');
	$columns['eventcategories'] = __('Categories');
	$columns['datestart'] = __('Start Date/Time','eventorganiser');
	$columns['dateend'] = __('End Date/Time', 'eventorganiser');
	$columns['reoccurence'] = __('Reoccurrence','eventorganiser'); 

	return $columns;
}


/**
 * Registers the custom columns in Event CPT table to be sortable
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
 * @since 1.0.0
 */
add_action('manage_event_posts_custom_column', 'eventorganiser_event_sort_columns', 10, 2);
function eventorganiser_event_sort_columns($column_name, $id) {
	global $post;

	$series_id = (empty($post->event_id) ? $id :'');
	$EO_Venue =new EO_Venue((int)eo_get_venue($series_id));

	$phpFormat = 'M, jS Y';
	if(!eo_is_all_day($series_id))
		$phpFormat .= '\<\/\b\r\>'. get_option('time_format');
	
	switch ($column_name) {
		case 'venue':
		    	$terms = get_the_terms($post->ID, 'event-venue');
 			
			if ( !empty($terms) ) {
       	 		foreach ( $terms as $term )
			            $post_terms[] = "<a href='".add_query_arg( 'event-venue', $term->slug)."'>".esc_html(sanitize_term_field('name', $term->name, $term->term_id,'event-venue', 'display'))."</a>";
			        echo join( ', ', $post_terms );
			}
			break;

		case 'datestart':
			eo_the_start($phpFormat,$series_id );
			break;
		
		case 'dateend':
			eo_the_end($phpFormat,$series_id );
			break;

		case 'reoccurence':
			eo_display_reoccurence($series_id );
			break;

		case 'eventcategories':
		    	$terms = get_the_terms($post->ID, 'event-category');
 			
			if ( !empty($terms) ) {
       	 		foreach ( $terms as $term )
			            $post_terms[] = "<a href='".add_query_arg( 'event-category', $term->slug)."'>".esc_html(sanitize_term_field('name', $term->name, $term->term_id,'event-category', 'display'))."</a>";
			        echo join( ', ', $post_terms );
			}
			break;

	default:
		break;
	} // end switch
}

/**
 * Adds a drop-down filter to the Event CPT table by category
 * @since 1.0.0
 */
add_action( 'restrict_manage_posts', 'restrict_events_by_category' );
function restrict_events_by_category() {

    // only display these taxonomy filters on desired custom post_type listings
    global $typenow,$wp_query;
    if ($typenow == 'event') {
	eo_event_category_dropdown(array('hide_empty'=>false,'show_option_all' => __('View all categories')));
    }
}

/**
 * Adds a drop-down filter to the Event CPT table by venue
 * @since 1.0.0
 */
add_action('restrict_manage_posts','restrict_events_by_venue');
function restrict_events_by_venue() {
	global $typenow;

	//Only add if CPT is event
	if ($typenow=='event') :	
		 eo_event_venue_dropdown(array('hide_empty'=>false,'show_option_all' => __('View all venues','eventorganiser')));
	endif;
}

/**
 * Adds a drop-down filter to the Event CPT table by intervals
 * @since 1.2.0
 */
add_action( 'restrict_manage_posts', 'eventorganiser_display_occurrences' );
function eventorganiser_display_occurrences() {
	global $typenow,$wp_query;
	if ($typenow == 'event'):
		$intervals = array(
			'all'=>__('View all events','eventorganiser'),
			'future'=>__('Future events','eventorganiser'),
			'expired'=>__('Expired events','eventorganiser'),
			'P1D'=>__('Events within 24 hours', 'eventorganiser'),
			'P1W'=>__('Events within 1 week','eventorganiser'),
			'P2W'=> sprintf(__('Events within %d weeks','eventorganiser'), 2),
			'P1M'=>__('Events within 1 month','eventorganiser'),
			'P6M'=> sprintf(__('Events within %d months','eventorganiser'), 6),
			'P1Y'=>__('Events within 1 year','eventorganiser'),
		);
		$current = (!empty($wp_query->query_vars['eo_interval']) ? $wp_query->query_vars['eo_interval'] : 'all');	
?>
		<select style="width:150px;" name='eo_interval' id='show-events-in-interval' class='postform'>
			<?php foreach ($intervals as $id=>$interval): ?>
				<option value="<?php echo $id; ?>" <?php selected($current,$id)?>> <?php echo $interval;?> </option>
			<?php endforeach; ?>
		</select>
<?php
	endif;//End if CPT is event
}
?>
