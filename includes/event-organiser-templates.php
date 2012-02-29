<?php
/**
 * Checks to see if appropriate templates are present in active template directory.
 * Otherwises uses templates present in plugin's template directory.
 *
 * @since 1.0.0
 */
add_filter('template_include', 'eventorganiser_set_template');
function eventorganiser_set_template( $template ){
	$eo_settings = get_option('eventorganiser_options');
	if(!$eo_settings['templates'])
		return $template;

	//TODO Check for multiple queries and use archive 
	if(!is_admin()){
		$template_dir = get_stylesheet_directory();
		$parent_template_dir =get_template_directory();

		if(is_post_type_archive('event')){
			if(file_exists($template_dir.'/archive-event.php')) 
				$template= $template_dir.'/archive-event.php';
			elseif(file_exists($parent_template_dir.'/archive-event.php')) 
				 $template = $parent_template_dir.'/archive-event.php';
			else
		 		$template = EVENT_ORGANISER_DIR.'templates/archive-event.php';
		}

		if(is_singular('event')){
			if(file_exists($template_dir.'/single-event.php')) 
				$template = $template_dir.'/single-event.php';
			elseif(file_exists($parent_template_dir.'/single-event.php')) 
				$template = $parent_template_dir.'/single-event.php';
	 		else
				$template = EVENT_ORGANISER_DIR.'templates/single-event.php';
		}

		if (eo_is_venue()) {
			if(file_exists($template_dir.'/venue-template.php')) 
				$template = $template_dir.'/venue-template.php';
			elseif(file_exists($parent_template_dir.'/venue-template.php')) 
				$template = $parent_template_dir.'/venue-template.php';
	 		else
				$template = EVENT_ORGANISER_DIR.'templates/venue-template.php';
		}

		if(is_tax('event-category')){
			if(file_exists($template_dir.'/taxonomy-event-category.php')) 
				$template = $template_dir.'/taxonomy-event-category.php';
			elseif(file_exists($parent_template_dir.'/taxonomy-event-category.php')) 
				$template = $parent_template_dir.'/taxonomy-event-category.php';
			else
		 		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-category.php';
		}

		if(is_tax('event-tag')&& isset($eo_settings['eventtag']) && $eo_settings['eventtag']==1){
			if(file_exists($template_dir.'/taxonomy-event-tag.php')) 
				$template = $template_dir.'/taxonomy-event-tag.php';
			elseif(file_exists($template_dir.'/taxonomy-event-tag.php')) 
				$template = $template_dir.'/taxonomy-event-tag.php';
			else
	 			$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-tag.php';
		}

	return $template;
	}

	return $template;
}
?>
