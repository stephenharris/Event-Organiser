<?php
/**
 * Checks to see if appropriate templates are present in active template directory.
 * Otherwises uses templates present in plugin's template directory.
 *
 * @since 1.0.0
 */
add_filter('template_include', 'eventorganiser_set_template');
function eventorganiser_set_template( $template ){
	$template_dir = get_stylesheet_directory();
	$eo_settings = get_option('eventorganiser_options');

	if(!$eo_settings['templates'])
		return $template;

	if(!is_admin()){
		if (is_venue()) {
			if(file_exists($template_dir.'/venue.php')) return $template_dir.'/venue-template.php';

	 		return EVENT_ORGANISER_DIR.'templates/venue-template.php';
		}

		if(is_post_type_archive('event')){
			if(file_exists($template_dir.'/archive-event.php')) return $template_dir.'/archive-event.php';
	 		return EVENT_ORGANISER_DIR.'templates/archive-event.php';
		}

		if(is_singular('event')){
			if(file_exists($template_dir.'/single-event.php')) return $template_dir.'/single-event.php';
	 		return EVENT_ORGANISER_DIR.'templates/single-event.php';
		}

		if(is_tax('event-category')){
			if(file_exists($template_dir.'/taxonomy-event-category.php')) return $template_dir.'/taxonomy-event-category.php';
	 		return EVENT_ORGANISER_DIR.'templates/taxonomy-event-category.php';
		}

		if(is_tax('event-tag')){
			if(file_exists($template_dir.'/taxonomy-event-tag.php')) return $template_dir.'/taxonomy-event-tag.php';
	 		return EVENT_ORGANISER_DIR.'templates/taxonomy-event-tag.php';
		}
	}

	return $template;
}
?>
