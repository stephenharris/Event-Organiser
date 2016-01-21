<?php
/**
 * Functions altering the CPT Event table
 *
 * @since 1.0.0
 */

/**
 * Adds custom columns to Event CPT table
 * @since 1.0.0
 * @access private
 * @ignore
 */
function eventorganiser_event_add_columns( $columns ) {

	unset( $columns['date'] );//Unset unnecessary columns

	//Set 'title' column title
	$columns['title'] = __( 'Event', 'eventorganiser' );

	//If displaying 'author', change title
	if ( isset( $columns['author'] ) ) {
		$columns['author'] = __( 'Organiser', 'eventorganiser' );
	}

	if ( isset( $columns['author'] ) && ! eo_is_multi_event_organiser() ) {
		unset( $columns['author'] );
	}

	if ( taxonomy_exists( 'event-venue' ) ) {
		$tax = get_taxonomy( 'event-venue' );
		$columns['venue'] = $tax->labels->singular_name;
	}

	$columns['datestart']   = __( 'Start Date/Time', 'eventorganiser' );
	$columns['dateend']     = __( 'End Date/Time', 'eventorganiser' );
	$columns['reoccurence'] = __( 'Recurrence', 'eventorganiser' );

	return $columns;
}
add_filter( 'manage_edit-event_columns', 'eventorganiser_event_add_columns' );

/**
 * Registers the custom columns in Event CPT table to be sortable
 * @since 1.0.0
 * @access private
 * @ignore
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
 * @access private
 * @ignore
 */
add_action( 'manage_event_posts_custom_column', 'eventorganiser_event_fill_columns', 10, 2 );
function eventorganiser_event_fill_columns( $column_name, $id ) {
	global $post;

	$series_id = ( empty( $post->event_id ) ? $id :'' );

	$phpFormat = 'M, j Y';
	if ( ! eo_is_all_day( $series_id ) ) {
		$phpFormat .= '\<\/\b\r\>'. get_option( 'time_format' );
	}

	switch ( $column_name ) {
		case 'venue':
			$taxonomy_object = get_taxonomy( 'event-venue' );
			$venue_id   = eo_get_venue( $post->ID );
			$venue_slug = eo_get_venue_slug( $post->ID );
			$venue_name = eo_get_venue_name( $venue_id );

			if ( $venue_id ) {
				printf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'event-venue', $venue_slug ) ), esc_html( $venue_name ) );
				printf( '<input type="hidden" value="%d"/>', $venue_id );
			} else {
				echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . $taxonomy_object->labels->no_terms . '</span>';
			}
			break;

		case 'datestart':
			$schedule = eo_get_event_schedule( $series_id );
			echo eo_format_datetime( $schedule['start'], $phpFormat );
			break;

		case 'dateend':
			$schedule = eo_get_event_schedule( $series_id );
			echo eo_format_datetime( $schedule['end'], $phpFormat );
			break;

		case 'reoccurence':
			echo eo_get_schedule_summary( $series_id );
			break;

	} // end switch
}

/**
 * Adds a drop-down filter to the Event CPT table by category
 * @since 1.0.0
 */
add_action( 'restrict_manage_posts', 'eventorganiser_restrict_events_by_category' );
function eventorganiser_restrict_events_by_category() {
	global $typenow;

	$category_tax = get_taxonomy( 'event-category' );

	if ( 'event' == $typenow && $category_tax && wp_count_terms( 'event-category' ) > 0 ) {
		eo_taxonomy_dropdown( array(
			'taxonomy'        => 'event-category',
			'selected'        => get_query_var( 'event-category' ),
			'hide_empty'      => false,
			'show_option_all' => $category_tax->labels->view_all_items,
		) );
	}
}

/**
 * Adds a drop-down filter to the Event CPT table by venue
 * @since 1.0.0
 */
add_action( 'restrict_manage_posts', 'eventorganiser_restrict_events_by_venue' );
function eventorganiser_restrict_events_by_venue() {
	global $typenow;

	$venue_tax = get_taxonomy( 'event-venue' );

	//Only add if CPT is event
	if ( 'event' == $typenow && $venue_tax && wp_count_terms( 'event-venue' ) > 0  ) {
		eo_taxonomy_dropdown( array(
			'taxonomy'        => 'event-venue',
			'selected'        => get_query_var( 'event-venue' ),
			'hide_empty'      => false,
			'show_option_all' => $venue_tax->labels->view_all_items,
		) );
	}
}

/**
 * Adds a drop-down filter to the Event CPT table by intervals
 * @since 1.2.0
 */
add_action( 'restrict_manage_posts', 'eventorganiser_display_occurrences' );
function eventorganiser_display_occurrences() {
	global $typenow, $wp_query;
	if ( 'event' == $typenow ) :
		$intervals = array(
			'all'     => __( 'View all events', 'eventorganiser' ),
			'future'  => __( 'Future events', 'eventorganiser' ),
			'expired' => __( 'Expired events', 'eventorganiser' ),
			'P1D'     => __( 'Events within 24 hours', 'eventorganiser' ),
			'P1W'     => __( 'Events within 1 week', 'eventorganiser' ),
			'P2W'     => sprintf( __( 'Events within %d weeks', 'eventorganiser' ), 2 ),
			'P1M'     => __( 'Events within 1 month', 'eventorganiser' ),
			'P6M'     => sprintf( __( 'Events within %d months', 'eventorganiser' ), 6 ),
			'P1Y'     => __( 'Events within 1 year', 'eventorganiser' ),
		);
		//@see https://core.trac.wordpress.org/ticket/16471
		$current = ( get_query_var( 'eo_interval' ) ? get_query_var( 'eo_interval' ) : 'all' );
?>
		<select style="width:150px;" name='eo_interval' id='show-events-in-interval' class='postform'>
			<?php foreach ( $intervals as $id => $interval ) : ?>
				<option value="<?php echo $id; ?>" <?php selected( $current, $id )?>> <?php echo $interval;?> </option>
			<?php endforeach; ?>
		</select>
<?php
	endif;//End if CPT is event
}


/*
 * Bulk and quick editting of venues. Add drop-down menu for quick editing
 * @since 3.0.0
 * @private
 */
function eventorganiser_quick_bulk_edit_box( $column_name, $post_type ) {
	if ( 'venue' != $column_name  || 'event' != $post_type ) {
		return;
	}
	$tax = get_taxonomy( 'event-venue' );

	$args = array(
		'orderby'    => 'name',
		'hide_empty' => 0,
		'name'       => 'eo_input[event-venue]',
		'taxonomy'   => 'event-venue',
	);

	if ( 'quick_edit_custom_box' == current_filter() ) {
		$args['id']              = 'eventorganiser_venue';
		$args['show_option_all'] = $tax->labels->no_tags;
	} else {
		$args['id']               = 'eventorganiser_venue_bulk';
		$args['show_option_none'] = __( '&mdash; No Change &mdash;' );
	}

	?>
	<fieldset class="inline-edit-col-left">
	<div class="inline-edit-col">
		<?php wp_nonce_field( 'eventorganiser_event_quick_edit_'.get_current_blog_id(), '_eononce' );?>
		<label class="">
			<span class="title"><?php echo esc_html( $tax->labels->singular_name ); ?></span>
			<?php wp_dropdown_categories( $args ); ?>
		</label>
	</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box',  'eventorganiser_quick_bulk_edit_box', 10, 2 );
add_action( 'bulk_edit_custom_box',  'eventorganiser_quick_bulk_edit_box', 10, 2 );

/*
 * Bulk and quick editting of venues. Save venue update.
 * @Since 1.3
 */
add_action( 'save_post', 'eventorganiser_quick_edit_save' );
function eventorganiser_quick_edit_save( $post_id ) {

	$request = array_merge( $_GET, $_POST );

	//make sure data came from our quick/bulk box
	if ( ! isset( $request['_eononce'] ) || ! wp_verify_nonce( $request['_eononce'], 'eventorganiser_event_quick_edit_'.get_current_blog_id() ) ) {
		return;
	}

	// verify this is not an auto save routine.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	//verify this is not a cron job
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}

	//authentication checks
	if ( ! current_user_can( 'edit_event', $post_id ) ) {
		return;
	}

	$venue_id = ( isset( $request['eo_input']['event-venue'] ) ? (int) $request['eo_input']['event-venue'] : - 1 );

	if ( $venue_id >= 0 ) {
		$r = wp_set_post_terms( $post_id, array( $venue_id ), 'event-venue', false );
	}

	/**
	 * Triggered after an event has been updated.
	 *
	 * @param int $post_id The ID of the event
	 */
	do_action( 'eventorganiser_save_event', $post_id );
	return;
}


add_action( 'admin_head-edit.php', 'eventorganiser_quick_edit_script' );
function eventorganiser_quick_edit_script() {
	?>
    <script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery( '#the-list' ).on( 'click', 'a.editinline', function() {
			jQuery( '#eventorganiser_venue option' ).attr("selected", false);
			var id = inlineEditPost.getId(this);
			var val = parseInt(jQuery( '#post-' + id + ' td.column-venue input' ).val() );
			jQuery( '#eventorganiser_venue option[value="'+val+'"]' ).attr( 'selected', 'selected' );
        });
    });
    </script>
    <?php
}
