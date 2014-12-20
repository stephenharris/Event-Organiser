<?php
/**
 * Starting the test 
 */
echo "Welcome to the Event Organiser Test Suite" . PHP_EOL;
echo "Version: 1.0" . PHP_EOL;
echo "Authors: Stephen Harris" . PHP_EOL;

// If the develop repo location is defined (as WP_DEVELOP_DIR), use that
// location. Otherwise, we'll just assume that this plugin is installed in a
// WordPress developer repo under wp-content/plugins/
$_wp_dev_dir = getenv('WP_DEVELOP_DIR');
if ( !$_wp_dev_dir ) $_wp_dev_dir = '../../../..';

echo "Using WordPress installation at ". $_wp_dev_dir . PHP_EOL;

//Defines the data location for unit-tests
define( 'EO_DIR_TESTDATA', dirname( __FILE__ ) . '/data' );

// Activates this plugin in WordPress so it can be tested.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'event-organiser/event-organiser.php' ),
);

require_once $_wp_dev_dir . '/tests/phpunit/includes/functions.php';
require_once $_wp_dev_dir . '/tests/phpunit/includes/bootstrap.php';

// Install Event Organiser
echo "Installing Event Organiser...\n";
eventorganiser_install();

//Load our unit test class
require dirname( __FILE__ ) . '/framework/testcase.php';
