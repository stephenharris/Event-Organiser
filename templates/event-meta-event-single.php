<?php
/**
 * The template is used for displaying a single event details.
 *
 * You can use this to edit how the details re displayed on your site. (see notice below).
 *
 * Or you can edit the entire single event template by creating a single-event.php template
 * in your theme.
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
?>

<div class="entry-meta eventorganiser-event-meta">
	<!-- Choose a different date format depending on whether we want to include time -->
	<?php if( eo_is_all_day() ){
		$date_format = 'j F Y'; 
	}else{
		$date_format = 'j F Y g:ia'; 
	} ?>

	<!-- Is event recurring or a single event -->
	<?php if( eo_reoccurs() ):?>

		<!-- Event reoccurs - is there a next occurrence? -->
		<?php $next =   eo_get_next_occurrence($date_format);?>

		<?php if($next): ?>
			<!-- If the event is occurring again in the future, display the date -->
			<?php printf('<p>'.__('This event is running from %1$s until %2$s. It is next occurring at %3$s','eventorganiser').'.</p>', eo_get_schedule_start('d F Y'), eo_get_schedule_last('d F Y'), $next);?>
	
		<?php else: ?>
			<!-- Otherwise the event has finished (no more occurrences) -->
			<?php printf('<p>'.__('This event finished on %s','eventorganiser').'.</p>', eo_get_schedule_last('d F Y',''));?>
		<?php endif; ?>

	<?php else: ?>
		<!-- Event is a single event -->
		<?php printf('<p>'.__('This event is on %s','eventorganiser').'.</p>', eo_get_the_start($date_format) );?>
	<?php endif; ?>


	<!-- Does the event have a venue? -->
	<?php if( eo_get_venue() ): ?>
		<!-- Display map -->
		<?php echo '<p>'.eo_get_venue_map(eo_get_venue()).'</p>'; ?>
	<?php endif; ?>

</div><!-- .entry-meta -->
