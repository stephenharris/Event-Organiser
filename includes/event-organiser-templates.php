<?php
/**
 * Checks if provided template path points to an 'event' template recognised by EO, given the context.
 * This will one day ignore context, and if only the event archive template is present in theme folder
* it will use that  regardless. If no event-archive tempate is present the plug-in will pick the most appropriate
* option, first from the theme/child-theme directory then the plugin.
 * @param $templatePath template path or file name (with .php extension)
 * @return (true|false) return true if template is recognised as an 'event' template. False otherwise.
 *
 * @since 1.3.1
 */
function eventorganiser_is_event_template($templatePath,$context=''){

	$template = basename($templatePath);

	switch($context):
		case 'event';	
			return $template == 'single-event.php';

		case 'archive':
			return $template == 'archive-event.php';

		case 'event-venue':
			if((1 == preg_match('/^taxonomy-event-venue((-(\S*))?).php/',$template) || $template == 'venues-template.php'))
				return true;
			return false;

		case 'event-category':
			return (1 == preg_match('/^taxonomy-event-category((-(\S*))?).php/',$template));

		case 'event-venue':
			return (1 == preg_match('/^taxonomy-event-tag((-(\S*))?).php/',$template));
	endswitch;

	return false;
}

/**
 * Checks to see if appropriate templates are present in active template directory.
 * Otherwises uses templates present in plugin's template directory.
 *
 * @since 1.0.0
 */
add_filter('template_include', 'eventorganiser_set_template');
function eventorganiser_set_template( $template ){

	//Is this necessary?
	if(is_admin())
		return $template;

	//Has EO template handling been turned off?
	$eo_settings = get_option('eventorganiser_options');
	if(!$eo_settings['templates'])
		return $template;

	//WordPress couldn't find an 'event' template. Use plug-in instead:

	if(is_post_type_archive('event') && !eventorganiser_is_event_template($template,'archive'))
		$template = EVENT_ORGANISER_DIR.'templates/archive-event.php';
		
	if(is_singular('event') && !eventorganiser_is_event_template($template,'event'))
		$template = EVENT_ORGANISER_DIR.'templates/single-event.php';

	if( (is_tax('event-venue')|| eo_is_venue()) && !eventorganiser_is_event_template($template,'event-venue'))
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-venue.php';

	if(is_tax('event-category')  && !eventorganiser_is_event_template($template,'event-category'))
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-category.php';

	if( is_tax('event-tag')&& isset($eo_settings['eventtag']) && $eo_settings['eventtag']==1 && !eventorganiser_is_event_template($template,'event-tag') )
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-tag.php';

	return $template;
}
?>
