<?php
/**
 * The template for displaying a single event
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

//Call the template header
get_header(); ?>

	<div id="primary">
		<div id="content" role="main">

			<?php while ( have_posts() ) : the_post(); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<header class="entry-header">

						<!---- Display event title-->
						<h1 class="entry-title"><?php the_title(); ?></h1>

							<div class="entry-meta">
								<!----Choose a different date format depending on whether we want to include time-->
								<?php if(eo_is_all_day()): ?>
									<!----Event is all day -->
									<?php $date_format = 'j F Y'; ?>
								<?php else: ?>
									<!----Event is not all day - include time in format-->
									<?php $date_format = 'j F Y g:ia'; ?>
								<?php endif; ?>

								<?php if(eo_reoccurs()):?>
									<!----Event reoccurs - is there a next occurrence? -->
									<?php $next =   eo_get_next_occurrence($date_format);?>
									<?php if($next): ?>
										<!---- If the event is occurring again in the future, display the date -->
										<?php printf(__('This event is running from %1$s until %2$s. It is next occurring at %3$s','eventorganiser'), eo_get_schedule_start('d F Y'), eo_get_schedule_end('d F Y'), $next);?>

									<?php else: ?>
										<!---- Otherwise the event has finished (no more occurrences) -->
										<?php printf(__('This event finished on %s','eventorganiser'), eo_get_schedule_end('d F Y',''));?>
								<?php endif; ?>

								<?php else: ?>
									<!----Event is a single event -->
										<?php printf(__('This event is on %s','eventorganiser'), eo_get_the_start($date_format) );?>
								<?php endif; ?>
							</div><!-- .entry-meta -->

					</header><!-- .entry-header -->
	
					<div class="entry-content">
						<!---- The content or the description of the event-->
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link"><span>' . __( 'Pages:', 'eventorganiser' ) . '</span>', 'after' => '</div>' ) ); ?>
					</div><!-- .entry-content -->

					<footer class="entry-meta">
					<?php
					//Events have their own 'event-category' taxonomy. Get list of categories this event is in.
					$categories_list = get_the_term_list( get_the_ID(), 'event-category', '', ', ',''); 

					if ( '' != $categories_list ) {
						$utility_text = __( 'This event was posted in %1$s by <a href="%5$s">%4$s</a>. Bookmark the <a href="%2$s" title="Permalink to %3$s" rel="bookmark">permalink</a>.', 'eventorganiser' );
					} else {
						$utility_text = __( 'This event was posted by <a href="%5$s">%4$s</a>. Bookmark the <a href="%2$s" title="Permalink to %3$s" rel="bookmark">permalink</a>.', 'eventorganiser' );
					}
					printf(
						$utility_text,
						$categories_list,
						esc_url( get_permalink() ),
						the_title_attribute( 'echo=0' ),
						get_the_author(),
						esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) )
					);
					?>
					<?php edit_post_link( __( 'Edit'), '<span class="edit-link">', '</span>' ); ?>
					</footer><!-- .entry-meta -->
				</article><!-- #post-<?php the_ID(); ?> -->
						
				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<!-- Call template footer -->
<?php get_footer(); ?>
