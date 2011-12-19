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

//Call the template header
get_header(); ?>

		<!-- This template follows the TwentyEleven theme-->
		<div id="primary">
		<div id="content" role="main">

			<?php if ( have_posts() ) : ?><!---- There are events, start 'the Loop'-->
				<header class="page-header">	
				<!-- Print the page title -->
				<h1 class="page-title"><?php
					printf( __( 'Events at: %s', 'twentyeleven' ), '<span>' .eo_get_venue_name(). '</span>' );
				?></h1>

				<?php
						//Get the description and print it if it exists
						$venue_description =eo_get_venue_description();

						if(!empty($venue_description)){?>
							<div class="venue-archive-meta">
								<?php echo $venue_description; ?>
							</div>
						<?php } ?>
		
				<!-- Display the venue map-->
				<?php echo do_shortcode('[eo_venue_map width="600px"]'); ?>
			</header><!-- end header -->

						
				<?php while ( have_posts()) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

						<header class="entry-header">
							<h1 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>

							<div class="entry-meta">
								<?php eo_the_start('d F Y'); ?> 
								<?php if(eo_get_venue_name()):?>
									at <a href="<?php eo_venue_link();?>"><?php eo_venue_name();?></a>
								<?php endif;?>
							</div><!-- .entry-meta -->

						</header><!-- .entry-header -->

					</article><!-- #post-<?php the_ID(); ?> -->

    				<?php endwhile; ?><!----The Loop ends-->

			<?php else : ?><!---- If there are no events -->

				<article id="post-0" class="post no-results not-found">

					<header class="entry-header">
						<h1 class="entry-title">Nothing Found</h1>
					</header><!-- end .entry-header -->

					<div class="entry-content">
						<p>Apologies, but no events were found for the requested venue</p>
					</div><!-- end .entry-content -->

				</article><!-- end #post-0 -->

			<?php endif; ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
