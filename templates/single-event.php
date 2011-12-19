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

get_header(); ?>

	<div id="primary">
		<div id="content" role="main">

			<?php while ( have_posts() ) : the_post(); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<header class="entry-header">
						<h1 class="entry-title"><?php the_title(); ?></h1>
							<div class="entry-meta">
								<?php $next = eo_get_next_occurrence('d F Y'); ?>
								<?php if($next): ?>										
									This event is running from <?php eo_schedule_start('d F Y'); ?>  until <?php  eo_schedule_end('d F Y',''); ?>. It is next showing at <?php echo $next;?>
								<?php else: ?>
									This event finished on <?php  eo_schedule_end('d F Y',''); ?>.
								<?php endif;?>
							</div><!-- .entry-meta -->
					</header><!-- .entry-header -->
	
					<div class="entry-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-link"><span>' . __( 'Pages:', 'twentyeleven' ) . '</span>', 'after' => '</div>' ) ); ?>
					</div><!-- .entry-content -->

					<footer class="entry-meta">
					<?php
					//Events have their own 'event-category' taxonomy. Get list of categories this event is in.
					$categories_list = get_the_term_list( $post->ID, 'event-category', '', ', ',''); 

					if ( '' != $categories_list ) {
						$utility_text = __( 'This event was posted in %1$s by <a href="%5$s">%4$s</a>. Bookmark the <a href="%2$s" title="Permalink to %3$s" rel="bookmark">permalink</a>.', 'twentyeleven' );
					} else {
						$utility_text = __( 'This event was posted by <a href="%5$s">%4$s</a>. Bookmark the <a href="%2$s" title="Permalink to %3$s" rel="bookmark">permalink</a>.', 'twentyeleven' );
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
					<?php edit_post_link( __( 'Edit', 'twentyeleven' ), '<span class="edit-link">', '</span>' ); ?>
					</footer><!-- .entry-meta -->
				</article><!-- #post-<?php the_ID(); ?> -->
						
				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->
<?php get_footer(); ?>
