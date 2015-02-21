<?php
/**
 * Starting the test 
 */
echo "Welcome to the Event Organiser Test Suite" . PHP_EOL;
echo "Version: 1.0" . PHP_EOL;
echo "Authors: Stephen Harris" . PHP_EOL;

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../event-organiser.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

echo "Using WordPress test library at ". $_tests_dir . PHP_EOL;

//Defines the data location for unit-tests
define( 'EO_DIR_TESTDATA', dirname( __FILE__ ) . '/data' );

require $_tests_dir . '/includes/bootstrap.php';

activate_plugin( 'event-organiser/event-organiser.php' );

// Install Event Organiser
echo "Installing Event Organiser...\n";
eventorganiser_install();

//Load our unit test class
require dirname( __FILE__ ) . '/framework/testcase.php';