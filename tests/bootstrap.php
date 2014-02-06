<?php
/**
 * Starting the test 
 */
echo "Welcome to the Event Organiser Test Suite" . PHP_EOL;
echo "Version: 1.0" . PHP_EOL;
echo "Authors: Stephen Harris" . PHP_EOL;

//Defines the data location for unit-tests
define( 'EO_DIR_TESTDATA', dirname( __FILE__ ) . '/data' );

// Activates this plugin in WordPress so it can be tested.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'event-organiser/event-organiser.php' ),
);

// If the develop repo location is defined (as WP_DEVELOP_DIR), use that
// location. Otherwise, we'll just assume that this plugin is installed in a
// WordPress developer repo under wp-content/plugins/
if( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
} else {
	require '../../../../tests/phpunit/includes/bootstrap.php';
}

// Install Event Organiser
echo "Installing Event Organiser...\n";
eventorganiser_install();

//Load our unit test class
require dirname( __FILE__ ) . '/framework/testcase.php';
