<?php
/**
 * Checks if provided template path points to an 'event' template recognised by EO, given the context.
 * This will one day ignore context, and if only the event archive template is present in theme folder
 * it will use that  regardless. If no event-archive tempate is present the plug-in will pick the most appropriate
 * option, first from the theme/child-theme directory then the plugin.
 *
 * @ignore
 * @since 1.3.1
 *
 * @param string $templatePath absolute path to template or filename (with .php extension)
 * @param string $context What the template is for ('event','archive-event','event-venue', etc).
 * @return (true|false) return true if template is recognised as an 'event' template. False otherwise.
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

		case 'event-tag':
			return (1 == preg_match('/^taxonomy-event-tag((-(\S*))?).php/',$template));
	endswitch;

	return false;
}

/**
 * Checks to see if appropriate templates are present in active template directory.
 * Otherwises uses templates present in plugin's template directory.
 * Hooked onto template_include'
 *
 * @ignore
 * @since 1.0.0
 * @param string $template Absolute path to template
 * @return string Absolute path to template
 */
function eventorganiser_set_template( $template ){

	//Has EO template handling been turned off?
	if( !eventorganiser_get_option('templates') )
		return $template;

	//If WordPress couldn't find an 'event' template use plug-in instead:

	if( is_post_type_archive('event') && !eventorganiser_is_event_template($template,'archive'))
		$template = EVENT_ORGANISER_DIR.'templates/archive-event.php';
		
	if( is_singular('event') && !eventorganiser_is_event_template($template,'event'))
		$template = EVENT_ORGANISER_DIR.'templates/single-event.php';

	if( ( is_tax('event-venue') || eo_is_venue() ) && !eventorganiser_is_event_template($template,'event-venue'))
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-venue.php';

	if( is_tax('event-category')  && !eventorganiser_is_event_template($template,'event-category'))
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-category.php';

	if( is_tax('event-tag') && eventorganiser_get_option('eventtag') && !eventorganiser_is_event_template($template,'event-tag') )
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-tag.php';

	return $template;
}
add_filter('template_include', 'eventorganiser_set_template');
?>
