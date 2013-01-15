<?php
/**
 * The template is used for displaying the Event List widget if the placeholder option isn't used.
 *
 * You can use this to edit how the output of the event list widget. For the shortcode [eo_events] see shortcode-event-list.php
 *
 * For a list of available functions (outputting dates, venue details etc) see http://wp-event-organiser.com/documentation/function-reference/
 *
 ***************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory. See http://wp-event-organiser.com/documentation/editing-the-templates/ for more information
 *
 * WordPress will automatically prioritise the template in your theme directory.
 ***************** NOTICE: *****************
 *
 * @package Event Organiser (plug-in)
 * @since 1.7
 */

//Date % Time format for events
$date_format = get_option('date_format');
$time_format = get_option('time_format');

//The list ID / classes
$id = $event_loop_args['id'];
$class = $event_loop_args['class'];

?>

<?php if( $event_loop->have_posts() ): ?>

	<ul id="<?php echo esc_attr($id);?>" class="<?php echo esc_attr($classes);?>" > 

		<?php while( $event_loop->have_posts() ): $event_loop->the_post(); ?>

			<?php 
				//Generate HTML classes for this event
				$event_classes = eo_get_event_classes(); 

				//For non-all-day events, include time format
				$format = ( eo_is_all_day() ? $date_format : $date_format.' '.$time_format );
			?>

			<li class="<? echo esc_attr(implode(' ',$event_classes)); ?>" >
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" ><?php the_title(); ?></a> <?php echo __('on','eventorganiser') . ' '.eo_get_the_start($format); ?>
			</li>

		<?php endwhile; ?>

	</ul>

<?php elseif( ! empty($event_loop_args['no_events']) ): ?>

	<ul id="<?php echo esc_attr($id);?>" class="<?php echo esc_attr($classes);?>" > 
		<li class="eo-no-events" > <?php echo $event_loop_args['no_events']; ?> </li>
	</ul>

<?php endif; ?>

