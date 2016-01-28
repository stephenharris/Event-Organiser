<?php
/**
 * The template for displaying the venue page
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
 * @since 1.0.0
 */

//Call the template header
get_header(); ?>

<div id="primary" role="main" class="content-area">

	<!-- Page header, display venue title-->
	<header class="page-header">
		
		<?php $venue_id = get_queried_object_id(); ?>
		
		<h1 class="page-title">
			<?php printf( __( 'Events at: %s', 'eventorganiser' ), '<span>' .eo_get_venue_name( $venue_id ). '</span>' );?>
		</h1>
	
		<?php
		if ( $venue_description = eo_get_venue_description( $venue_id ) ) {
			echo '<div class="venue-archive-meta">'.$venue_description.'</div>';
		}
		?>

		<!-- Display the venue map. If you specify a class, ensure that class has height/width dimensions-->
		<?php $latlng = eo_get_venue_latlng($venue_id); if (!empty((float)$latlng['lat']) && !empty((float)$latlng['lng'])) : ?>
			<?php echo eo_get_venue_map( $venue_id, array( 'width' => '100%' ) ); ?>
		<?php endif; ?>
	
	</header>
		
	<?php eo_get_template_part( 'eo-loop-events' ); //Lists the events ?>

</div><!-- #primary -->

<!-- Call template sidebar and footer -->
<?php get_sidebar(); ?>
<?php get_footer();
