<?php
/**
 * The template is used for displaying a single event details on the single event page.
 *
 * You can use this to edit how the details re displayed on your site. (see notice below).
 *
 * Or you can edit the entire single event template by creating a single-event.php template
 * in your theme.
 *
 * For a list of available functions (outputting dates, venue details etc) see http://codex.wp-event-organiser.com
 *
 ***************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory. See http://docs.wp-event-organiser.com/theme-integration for more information
 *
 * WordPress will automatically prioritise the template in your theme directory.
 ***************** NOTICE: *****************
 *
 * @package Event Organiser (plug-in)
 * @since 1.7
 */
?>

<div class="eventorganiser-event-meta">

	<hr>

	<!-- Event details -->
	<h4><?php _e( 'Event Details', 'eventorganiser' ); ?></h4>

	<!-- Is event recurring or a single event -->
	<?php if ( eo_recurs() ) :?>
		<!-- Event recurs - is there a next occurrence? -->
		<?php $next = eo_get_next_occurrence( eo_get_event_datetime_format() );?>

		<?php if ( $next ) : ?>
			<!-- If the event is occurring again in the future, display the date -->
			<?php printf( '<p>' . __( 'This event is running from %1$s until %2$s. It is next occurring on %3$s', 'eventorganiser' ) . '</p>', eo_get_schedule_start( 'j F Y' ), eo_get_schedule_last( 'j F Y' ), $next );?>

		<?php else : ?>
			<!-- Otherwise the event has finished (no more occurrences) -->
			<?php printf( '<p>' . __( 'This event finished on %s', 'eventorganiser' ) . '</p>', eo_get_schedule_last( 'd F Y', '' ) );?>
		<?php endif; ?>
	<?php endif; ?>

	<ul class="eo-event-meta">

		<?php if ( ! eo_recurs() ) { ?>
			<!-- Single event -->
			<li><strong><?php esc_html_e( 'Date', 'eventorganiser' );?>:</strong> <?php echo eo_format_event_occurrence();?></li>
		<?php } ?>

		<?php if ( eo_get_venue() ) {
			$tax = get_taxonomy( 'event-venue' ); ?>
			<li><strong><?php echo esc_html( $tax->labels->singular_name ) ?>:</strong> <a href="<?php eo_venue_link(); ?>"> <?php eo_venue_name(); ?></a></li>
		<?php } ?>

		<?php if ( get_the_terms( get_the_ID(), 'event-category' ) ) { ?>
			<li><strong><?php esc_html_e( 'Categories', 'eventorganiser' ); ?>:</strong> <?php echo get_the_term_list( get_the_ID(),'event-category', '', ', ', '' ); ?></li>
		<?php } ?>

		<?php if ( get_the_terms( get_the_ID(), 'event-tag' ) && ! is_wp_error( get_the_terms( get_the_ID(), 'event-tag' ) ) ) { ?>
			<li><strong><?php esc_html_e( 'Tags', 'eventorganiser' ); ?>:</strong> <?php echo get_the_term_list( get_the_ID(), 'event-tag', '', ', ', '' ); ?></li>
		<?php } ?>

		<?php if ( eo_recurs() ) {
				//Event recurs - display dates.
				$upcoming = new WP_Query(array(
					'post_type'         => 'event',
					'event_start_after' => 'today',
					'posts_per_page'    => -1,
					'event_series'      => get_the_ID(),
					'group_events_by'   => 'occurrence',
				));

				if ( $upcoming->have_posts() ) : ?>

					<li><strong><?php _e( 'Upcoming Dates', 'eventorganiser' ); ?>:</strong>
						<ul class="eo-upcoming-dates">
							<?php
							while ( $upcoming->have_posts() ) {
								$upcoming->the_post();
								echo '<li>' . eo_format_event_occurrence() . '</li>';
							};
							?>
						</ul>
					</li>

					<?php
					wp_reset_postdata();
					//With the ID 'eo-upcoming-dates', JS will hide all but the next 5 dates, with options to show more.
					wp_enqueue_script( 'eo_front' );
					?>
				<?php endif; ?>
		<?php } ?>

		<?php do_action( 'eventorganiser_additional_event_meta' ) ?>

	</ul>

	<!-- Does the event have a venue? -->
	<?php if ( eo_get_venue() && eo_venue_has_latlng( eo_get_venue() ) ) : ?>
		<!-- Display map -->
		<div class="eo-event-venue-map">
			<?php echo eo_get_venue_map( eo_get_venue(), array( 'width' => '100%' ) ); ?>
		</div>
	<?php endif; ?>


	<div style="clear:both"></div>

	<hr>

</div><!-- .entry-meta -->
