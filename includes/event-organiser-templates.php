<?php
/**
 * Template functions
 *
 * @package template-functions
*/


/**
 * Load a template part into a template
 *
 * Identical to {@see `get_template_part()`} except that it uses {@see `eo_locate_template()`}
 * instead of {@see `locate_template()`}.
 *
 * Makes it easy for a theme to reuse sections of code in a easy to overload way
 * for child themes. Looks for and includes templates {$slug}-{$name}.php
 *
 * You may include the same template part multiple times.
 *
 * @uses eo_locate_template()
 * @since 1.7
 * @uses do_action() Calls `get_template_part_{$slug}` action.
 *
 * @param string $slug The slug name for the generic template.
 * @param string $name The name of the specialised template.
 */
function eo_get_template_part( $slug, $name = null ) {
	do_action( "get_template_part_{$slug}", $slug, $name );

	$templates = array();
	if ( isset($name) )
		$templates[] = "{$slug}-{$name}.php";

	$templates[] = "{$slug}.php";

	eo_locate_template($templates, true, false);
}


/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches the child theme first, then the parent theme before checking the plug-in templates folder.
 * So parent themes can override the default plug-in templates, and child themes can over-ride both.
 *
 * Behaves almost identically to {@see locate_template()} 
 *
 * @since 1.7
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function eo_locate_template($template_names, $load = false, $require_once = true ) {
	$located = '';

	$template_dir = get_stylesheet_directory(); //child theme
	$parent_template_dir = get_template_directory(); //parent theme

	$stack = apply_filters( 'eventorganiser_template_stack', array( $template_dir, $parent_template_dir, EVENT_ORGANISER_DIR . 'templates' ) );

	foreach ( (array) $template_names as $template_name ) {
		if ( !$template_name )
			continue;
		foreach ( $stack as $template_stack ){
			if ( file_exists( trailingslashit( $template_stack ) . $template_name ) ) {
				$located = trailingslashit( $template_stack ) . $template_name;
				break;
			}
		} 
	}

	if ( $load && '' != $located )
		load_template( $located, $require_once );

	return $located;
}

/**
 * Whether an event archive is being viewed
 * 
 * My specifying the type of archive ( e.g. 'day', 'month' or 'year'), we can check if its 
 * a day, month or year archive. By default, it will just return `is_post_type_archive('event')`
 *
 * You can get the date of the archive via {@see `eo_get_event_archive_date()`}
 *
 *@uses is_post_type_archive()
 *@since 1.7
 *@param string $type The type archive. 'day', 'month', or 'year'
 *@return bool Whether an event archive is being viewed, where type is specified, if its an event archive of that type.
*/
function eo_is_event_archive( $type = false ){

	if( !is_post_type_archive('event') )
		return false;
	
	$ondate = str_replace('/','-',get_query_var('ondate'));

	switch( $type ){
		case 'year':
			if( _eventorganiser_check_datetime($ondate.'-01-01 00:00',true) )
				return true;
			return false;

		case 'month':
			if( _eventorganiser_check_datetime($ondate.'-01 00:00',true) )
				return true;
			return false;

		case 'day':
			if( _eventorganiser_check_datetime($ondate.' 00:00',true) )
				return true;
			return false;

		default:
			return true;
	}
}

/**
 * Returns formatted date of an event archive.
 *
 * Returns the formatted ate of an event archive. E.g. for date archives, returns that date, 
 * for year archives returns 1st January of that year, for month archives 1st of that month.
 * The date is formatted according to `$format` via {@see `eo_format_datetime()`}
 *
 * @since 1.7
 * @uses is_post_type_archive()
 * @uses eo_format_datetime()
 * @link http://php.net/manual/en/function.date.php Formatting dates
 * @param string|constant $format How to format the date, see http://php.net/manual/en/function.date.php  or DATETIMEOBJ constant to return the datetime object.
 * @return string|dateTime The formatted date
*/
function eo_get_event_archive_date( $format = DATETIMEOBJ ){

	if( !is_post_type_archive('event') )
		return false;
	
	$ondate = str_replace('/','-',get_query_var('ondate'));
	$parts = count(explode('-',$ondate));

	if( $parts == 1 && is_numeric($ondate) ){
		//Numeric - interpret as year
		$ondate .= '-01-01';
	}elseif( $parts == 2 ){
		// 2012-01 format: interpret as month
		$ondate .= '-01';
	}
		
	$ondate =  _eventorganiser_check_datetime($ondate.' 00:00',true);
	return eo_format_datetime($ondate, $format);
}

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

	if( ( is_tax('event-venue') || eo_is_venue() ) && !eventorganiser_is_event_template($template,'event-venue'))
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-venue.php';

	if( is_tax('event-category')  && !eventorganiser_is_event_template($template,'event-category'))
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-category.php';

	if( is_tax('event-tag') && eventorganiser_get_option('eventtag') && !eventorganiser_is_event_template($template,'event-tag') )
		$template = EVENT_ORGANISER_DIR.'templates/taxonomy-event-tag.php';

	/*
	 * In view of theme compatibility, if an event template isn't found
	 * rather than using our own single-event.php, we use ordinary single.php and
	 * add content in via the_content
	*/
	if( is_singular('event') && !eventorganiser_is_event_template($template,'event') ){	
		//Viewing a single event
		
		//Hide next/previous post link
		add_filter("next_post_link",'__return_false');
		add_filter("previous_post_link",'__return_false');

		//Prepend our event details
		add_filter('the_content','_eventorganiser_single_event_content');
	}		

	return $template;
}
add_filter('template_include', 'eventorganiser_set_template');


function _eventorganiser_single_event_content( $content ){

	//Sanity check!
	if( !is_singular('event') )
		return $content;

	//Object buffering				
	ob_start();
	eo_get_template_part('event-meta','event-single');
	//include(EVENT_ORGANISER_DIR.'templates/event-meta-event-single.php');
	$event_content = ob_get_contents();
	ob_end_clean();

	$event_content = apply_filters('eventorganiser_pre_event_content', $event_content, $content);

	return $event_content.$content;
}
?>
