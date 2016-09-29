<?php
//Defines the data location for unit-tests
define( 'EO_DIR_TESTDATA', dirname( __FILE__ ) . '/data' );

//Load the test library...
$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';
echo "Using WordPress test library at ". $_tests_dir . PHP_EOL;

//Install and activate plug-ins
function _manually_load_plugin() {
	require_once dirname( __FILE__ ) . '/../dist/event-organiser/event-organiser.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

function _manually_activate() {
	eventorganiser_install();
}
tests_add_filter( 'init', '_manually_activate' );

require $_tests_dir . '/includes/bootstrap.php';

activate_plugin( 'event-organiser/event-organiser.php' );

require dirname( __FILE__ ) . '/framework/testcase.php';
