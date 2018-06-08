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

	/**
	 * @ignore
	 */
	do_action( "get_template_part_{$slug}", $slug, $name );

	$templates = array();
	if ( isset( $name ) ) {
		$templates[] = "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	eo_locate_template( $templates, true, false );
}


/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches the child theme first, then the parent theme before checking the plug-in templates folder.
 * So parent themes can override the default plug-in templates, and child themes can over-ride both.
 *
 * Behaves almost identically to `{@see locate_template()}`
 *
 * @since 1.7
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function eo_locate_template( $template_names, $load = false, $require_once = true ) {
	$located = '';

	$template_dir = get_stylesheet_directory(); //child theme
	$parent_template_dir = get_template_directory(); //parent theme
	$stack = array( $template_dir, $parent_template_dir, EVENT_ORGANISER_DIR . 'templates' );

	/**
	 * Filters the template stack: an array of directories the plug-in looks for
	 * for templates.
	 *
	 * The directories are checked in the order in which they appear in this array.
	 * By default the array includes (in order)
	 *
	 *  - child theme directory
	 *  - parent theme directory
	 *  - `event-organiser/templates`
	 *
	 * @param array $stack Array of directories (absolute path).
	 */
	$stack = apply_filters( 'eventorganiser_template_stack', $stack );
	$stack = array_unique( $stack );

	foreach ( (array) $template_names as $template_name ) {
		if ( ! $template_name ) {
			continue;
		}
		foreach ( $stack as $template_stack ) {
			if ( file_exists( trailingslashit( $template_stack ) . $template_name ) ) {
				$located = trailingslashit( $template_stack ) . $template_name;
				break 2;
			}
		}
	}

	if ( $load && '' != $located ) {
		load_template( $located, $require_once );
	}

	return $located;
}

/**
 * A wrapper for {@see wp_enqueue_style()}. Filters the stylesheet url so themes can
 * replace a stylesheet with their own.
 *
 * @uses wp_register_style()
 * @since 3.0.0
 *
 * @param string      $handle Name of the stylesheet.
 * @param string|bool $src    Path to the stylesheet from the WordPress root directory. Example: '/css/mystyle.css'.
 * @param array       $deps   An array of registered style handles this stylesheet depends on. Default empty array.
 * @param string|bool $ver    String specifying the stylesheet version number. Used to ensure that the correct version
 *                            is sent to the client regardless of caching. Default 'false'. Accepts 'false', 'null', or 'string'.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 *                            Default 'all'. Accepts 'all', 'aural', 'braille', 'handheld', 'projection', 'print',
 *                            'screen', 'tty', or 'tv'.
 */
function eo_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	$src = apply_filters( 'eventorganiser_stylesheet_src', $src, $handle );
	$src = apply_filters( 'eventorganiser_stylesheet_src_' . $handle, $src );
	return wp_register_style( $handle, $src, $deps, $ver, $media );
}

/**
 * Enqueues a stylesheet
 * Respects the 'disable css' setting. It calls the `eventorganiser_stylesheet_{handle}`
 * hook so that the handle can be mapped to another registered stylesheet
 * @since 3.0.0
 * @param string $handle
 */
function eo_enqueue_style( $handle ) {

	if ( eventorganiser_get_option( 'disable_css' ) || get_theme_support( 'event-organiser' ) ) {
		return;
	}

	/**
	 * Filters the handle for a stylesheet
	 *
	 * This can be used to swap a stylesheet for another one. While you can deregister a handle
	 * and re-register it with a different source, this also allow multiple handles to be mapped
	 * to one 'common' styelsheet. This is useful for themes that wish to amalgamate a stylesheet
	 * into it's style.css.
	 *
	 * **This is currently only used with front-end stylsheets**
	 *
	 * @param string $handle The stylesheet handle
	 */
	$handle = apply_filters( 'eventorganiser_stylesheet_handle_' . $handle, $handle );

	wp_enqueue_style( $handle );

}

/**
 * Whether an event archive is being viewed
 *
 * By specifying the type of archive ( e.g. 'day', 'month' or 'year'), we can check if its
 * a day, month or year archive. By default, it will just return `is_post_type_archive('event')`
 *
 * You can get the date of the archive via {@see `eo_get_event_archive_date()`}
 *
 *@uses is_post_type_archive()
 *@since 1.7
 *@param string $type The type archive. 'day', 'month', or 'year'
 *@return bool Whether an event archive is being viewed, where type is specified, if its an event archive of that type.
*/
function eo_is_event_archive( $type = false ) {

	if ( ! is_post_type_archive( 'event' ) ) {
		return false;
	}

	$ondate = str_replace( '/', '-', trim( get_query_var( 'ondate' ) ) );

	switch ( $type ) {
		case 'year':
			return ( preg_match( '/\d{4}$/', $ondate ) && eo_check_datetime( 'Y-m-d', $ondate . '-01-01' ) );
			break;

		case 'month':
			return ( preg_match( '/^\d{4}-\d{2}$/', $ondate ) && eo_check_datetime( 'Y-m-d', $ondate . '-01' ) );
			break;

		case 'day':
			return ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ondate ) && eo_check_datetime( 'Y-m-d', $ondate ) );
			break;

		default:
			return true;
	}
}

/**
 * Returns formatted date of an event archive.
 *
 * Returns the formatted date of an event archive. E.g. for date archives, returns that date,
 * for year archives returns 1st January of that year, for month archives 1st of that month.
 * The date is formatted according to `$format` via {@see `eo_format_datetime()`}
 *
 * <code>
 * 	<?php
 *	 if( eo_is_event_archive('day') )
 *      //Viewing date archive: "Events: 3rd June 2013"
 *      echo __('Events: ','eventorganiser').' '.eo_get_event_archive_date('jS F Y');
 *	 elseif( eo_is_event_archive('month') )
 *      //Viewing month archive: "Events: June 2013"
 *      echo __('Events: ','eventorganiser').' '.eo_get_event_archive_date('F Y');
 *   elseif( eo_is_event_archive('year') )
 *      //Viewing year archive: "Events: 2013"
 *      echo __('Events: ','eventorganiser').' '.eo_get_event_archive_date('Y');
 *   else
 *      _e('Events','eventorganiser');
 *   ?>
 * </code>
 * @since 1.7
 * @uses is_post_type_archive()
 * @uses eo_format_datetime()
 * @link https://php.net/manual/en/function.date.php Formatting dates
 * @param string|constant $format How to format the date, see https://php.net/manual/en/function.date.php  or DATETIMEOBJ constant to return the datetime object.
 * @return string|dateTime The formatted date
*/
function eo_get_event_archive_date( $format = DATETIMEOBJ ) {

	if ( ! is_post_type_archive( 'event' ) ) {
		return false;
	}

	$ondate = str_replace( '/', '-', get_query_var( 'ondate' ) );

	if ( empty( $ondate ) ) {
		return false;
	}

	$parts = count( explode( '-', $ondate ) );

	if ( 1 == $parts && is_numeric( $ondate ) ) {
		//Numeric - interpret as year
		$ondate .= '-01-01';
	} elseif ( 2 == $parts ) {
		// 2012-01 format: interpret as month
		$ondate .= '-01';
	}

	$ondate = eo_check_datetime( 'Y-m-d', $ondate );
	return eo_format_datetime( $ondate, $format );
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
function eventorganiser_is_event_template( $templatepath, $context = '' ) {

	$template = basename( $templatepath );

	switch ( $context ) {
		case 'event';
			return 'single-event.php' == $template;

		case 'archive':
			return 'archive-event.php' == $template;

		case 'event-venue':
			return ( (1 == preg_match( '/^taxonomy-event-venue((-(\S*))?).php/', $template ) || 'venues-template.php' == $template ) );

		case 'event-category':
			return (1 == preg_match( '/^taxonomy-event-category((-(\S*))?).php/', $template ));

		case 'event-tag':
			return (1 == preg_match( '/^taxonomy-event-tag((-(\S*))?).php/', $template ));
	}

	return false;
}

function _eventorganiser_single_event_content( $content ) {

	//Sanity check!
	if ( ! is_singular( 'event' ) ) {
		return $content;
	}

	//Check we are an event!
	if ( get_post_type( get_the_ID() ) !== 'event' ) {
		return $content;
	}

	/*
	 * This was introduced to fix an obscure bug with including pages
	 * in another page via shortcodes.
	 * But it breaks yoast SEO.
	global $eo_event_parsed;
	if( !empty( $eo_event_parsed[get_the_ID()] ) ){
		return $content;
	}else{
		$eo_event_parsed[get_the_ID()] = 1;
	}*/

	//Object buffering
	ob_start();
	eo_get_template_part( 'event-meta','event-single' );
	//include(EVENT_ORGANISER_DIR.'templates/event-meta-event-single.php');
	$event_details = ob_get_contents();
	ob_end_clean();

	/**
	 * Filters the event details automatically appended to the event's content
	 * when single-event.php is not present in the theme.
	 *
	 * If template handling is enabled and the theme does not have `single-event.php`
	 * template, Event Organiser uses `the_content` filter to add prepend the content
	 * with event details. This filter allows you to change the prepended details.
	 *
	 * Unless you have a good reason, it's strongly recommended to change the templates
	 * rather than use this filter.
	 *
	 * @param string $event_details The event details to be added.
	 * @param string $content       The original event content
	 */
	$event_details = apply_filters( 'eventorganiser_pre_event_content', $event_details, $content );

	return $event_details . $content;
}
