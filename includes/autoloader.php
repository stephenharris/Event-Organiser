<?php

/**
 * As 3.5 this autoloader will be used for new classes. Older classes will be
 * manually included until they are moved.
 * A class name prefixed with EO_ maps to a filepath as follows:
 * * The EO prefix is ignored
 * * Each string enclosed with underscores is interpreted as a directory path
 *   relative to the includes directory (lowercased)
 * * The last part is converted from camel case to snake case, and prefixed
 *   with class- to form the filename
 * e.g: EO_Shortcode_EventList maps to includes/shortcode/class-event-list.php
 */
function eventorganiser_autoloader( $class ) {

	if ( 'EO_' !== substr( $class, 0, 3 ) ) {
		return;
	}

	$parts = explode( '_', $class );
	array_shift( $parts );
	$file = array_pop( $parts );
	$parts[] = 'class-' . eventorganiser_camel_case_to_snake_case( $file ) . '.php';
	$path = 'includes/' . implode( '/', $parts );
	$path =  EVENT_ORGANISER_DIR . strtolower( $path );

	if ( file_exists( $path ) ) {
		require_once $path;
	}

}

spl_autoload_register( 'eventorganiser_autoloader' );
