<?php
/**
 * The template for displaying an event-tag page
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
 * @since 1.2.0
 */

//Call the template header
get_header(); ?>

<div id="primary" role="main" class="content-area">

	<!-- Page header, display tag title-->
	<header class="page-header">
		<h1 class="page-title">
			<?php printf( __( 'Event Tag: %s', 'eventorganiser' ), '<span>' . single_cat_title( '', false ) . '</span>' ); ?>
		</h1>

		<!-- If the tag has a description display it-->
		<?php
		$tag_description = category_description();
		if ( ! empty( $tag_description ) ){
			echo apply_filters( 'category_archive_meta', '<div class="category-archive-meta">' . $tag_description . '</div>' );
		}
		?>
	</header>

	<?php eo_get_template_part( 'eo-loop-events' ); //Lists the events ?>

</div><!-- #primary -->

<!-- Call template sidebar and footer -->
<?php get_sidebar(); ?>
<?php get_footer(); ?>