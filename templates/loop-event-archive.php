<?php
/**
 * The template for displaying the venue page
 *
 ***************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory. 
 *
 * WordPress will automatically prioritise the template in your theme directory.
 ***************** NOTICE: *****************
 *
 * @package Event Organiser (plug-in)
 * @since 1.0.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header">
		<?php if( has_post_thumbnail() ): ?>
			<div class="eventorganiser-event-thumbnail" style="float:left;"> 
				<?php the_post_thumbnail('thumbnail'); ?>
			</div>
		<?php endif; ?>

		<h1 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>

		<div class="entry-meta">
			<!-- Output the date of the occurrence-->
			<?php if(eo_is_all_day()):?>
				<!-- Event is an all day event -->
				<?php eo_the_start('d F Y'); ?> 

			<?php else: ?>
				<!-- Event is not an all day event - display time -->
				<?php eo_the_start('d F Y g:ia'); ?> 

			<?php endif; ?>
	
			<!-- If the event has a venue saved, display this-->
			<?php if(eo_get_venue_name()):?>
				<?php _e('at','eventorganiser');?> <a href="<?php eo_venue_link();?>"><?php eo_venue_name();?></a>
			<?php endif;?>

		</div><!-- .entry-meta -->

		<div style="clear:both;"></div>
	</header><!-- .entry-header -->

</article><!-- #post-<?php the_ID(); ?> -->
<?php
