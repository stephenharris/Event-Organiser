<?php
/**
 * The template for displaying lists of events
 *
 * Queries to do with events will default to this template if a more appropriate template cannot be found
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

<div id="primary" role="main" class="content-area">

	<!-- Page header-->
	<header class="page-header">
		<h1 class="page-title">
		<?php
		if( eo_is_event_archive( 'day' ) ){
			//Viewing date archive
			echo __( 'Events: ','eventorganiser' ).' '.eo_get_event_archive_date( 'jS F Y' );
		}elseif( eo_is_event_archive( 'month' ) ){
			//Viewing month archive
			echo __( 'Events: ','eventorganiser' ).' '.eo_get_event_archive_date( 'F Y' );
		}elseif( eo_is_event_archive( 'year' ) ){
			//Viewing year archive
			echo __( 'Events: ','eventorganiser' ).' '.eo_get_event_archive_date( 'Y' );
		}else{
			_e( 'Events', 'eventorganiser' );
		}
		?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>

		<!-- Navigate between pages-->
		<?php 
		global $wp_query;
		if ( $wp_query->max_num_pages > 1 ) : ?>
			<nav id="nav-above">
				<div class="nav-next events-nav-later"><?php next_posts_link( __( 'Later events <span class="meta-nav">&rarr;</span>' , 'eventorganiser' ) ); ?></div>
				<div class="nav-previous events-nav-newer"><?php previous_posts_link( __( ' <span class="meta-nav">&larr;</span> Newer events', 'eventorganiser' ) ); ?></div>
			</nav><!-- #nav-above -->
		<?php endif; ?>

		<?php 
		while ( have_posts() ) : the_post(); 
			eo_get_template_part( 'eo-event-loop', true, false );
		endwhile;
		?>

		<!-- Navigate between pages-->
		<?php 
		if( $wp_query->max_num_pages > 1 ) : ?>
			<nav id="nav-below">
				<div class="nav-next events-nav-later"><?php next_posts_link( __( 'Later events <span class="meta-nav">&rarr;</span>' , 'eventorganiser' ) ); ?></div>
				<div class="nav-previous events-nav-newer"><?php previous_posts_link( __( ' <span class="meta-nav">&larr;</span> Newer events', 'eventorganiser' ) ); ?></div>
			</nav><!-- #nav-below -->
		<?php endif; ?>

	<?php else : ?>
		<!-- If there are no events -->
		<article id="post-0" class="post no-results not-found">
			<header class="entry-header">
				<h1 class="entry-title"><?php _e( 'Nothing Found', 'eventorganiser' ); ?></h1>
			</header><!-- .entry-header -->

			<div class="entry-content">
				<p><?php _e( 'Apologies, but no results were found for the requested archive. ', 'eventorganiser' ); ?></p>
			</div><!-- .entry-content -->
		</article><!-- #post-0 -->

	<?php endif; ?>

</div><!-- #primary -->

<!-- Call template sidebar and footer -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>
