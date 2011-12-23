<?php
/*
Plugin Name: Event Organiser
Plugin URI: http://www.HarrisWebSolutions.co.uk/event-organiser
Description: Creates a custom post type 'events' with features such as reoccurring events, venues, Google Maps, calendar views and events and venue pages
Author: Stephen Harris
Version: 1.0.4
Author URI: http://www.HarrisWebSolutions.co.uk
*/

/*  Copyright 2011 Stephen Harris (stephen@harriswebsolutions.co.uk)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

//The database version
global $eventorganiser_db_version;
$eventorganiser_db_version = "1.0";

global $wpdb;

global $eventorganiser_events_table;
$eventorganiser_events_table = $wpdb->prefix."eo_events";

global $eventorganiser_venue_table;
$eventorganiser_venue_table = $wpdb->prefix."eo_venues";

//Set the wp-content and plugin urls/paths
define('EVENT_ORGANISER_URL',plugin_dir_url(__FILE__ ));
define('EVENT_ORGANISER_DIR',plugin_dir_path(__FILE__ ));

global $eventorganiser_roles;
$eventorganiser_roles = array(
		 'edit_events' =>'Edit Events',
		 'publish_events' =>'Publish Events',
		 'delete_events' => 'Delete Events',
		'edit_others_events' =>'Edit Others\' Events',
		 'delete_others_events' => 'Delete Other\'s Events',
		'read_private_events' =>'Read Private Events',
		 'manage_venues' => 'Manage Venues',
		 'manage_event_categories' => 'Manage Event Categories',
);
			
/****** Install, activation & deactivation******/
require_once(EVENT_ORGANISER_DIR.'includes/event-organiser-install.php');

register_activation_hook(__FILE__,'eventorganiser_install'); 
register_deactivation_hook( __FILE__, 'eventorganiser_deactivate' );
register_uninstall_hook( __FILE__,'eventorganiser_uninstall');

/****** Register event post type and event taxonomy******/
require_once('includes/event-organiser-cpt.php');

/****** Register scripts, styles and actions******/
require_once('includes/event-organiser-register.php');

/****** Deals with the queries******/
require_once('includes/event-organiser-archives.php');

if(is_admin()):
	/****** event editing pages******/
	require_once('event-organiser-edit.php');
	require_once('event-organiser-manage.php');
	
	/****** settings, venue and calendar pages******/
	require_once('event-organiser-settings.php');
	require_once('event-organiser-venues.php');
	require_once('event-organiser-calendar.php');
	require_once("includes/class-event-organiser-im-export.php");
	require_once("classes/class-eo-list-table.php");
endif;

/****** Ajax files ******/
require_once('includes/event-organiser-venue-search.php');
require_once('includes/event-organiser-events-cal.php');

/****** Templates ******/
require_once('includes/event-organiser-templates.php');

/****** Functions ******/
require_once("includes/event-organiser-event-functions.php");
require_once("includes/event-organiser-venue-functions.php");

/****** Event and Venue classes ******/
require_once("classes/class-eo-event.php");
require_once("classes/class-eo-venues.php");
require_once("classes/class-eo-venue.php");

/****** Widgets and Shortcodes ******/
require_once('classes/class-eo-event-list-widget.php');
require_once('classes/class-eo-event-list-shortcode.php');
require_once('classes/class-eo-calendar-widget.php');
require_once('classes/class-eo-calendar-shortcode.php');
require_once('classes/class-eo-venue-map-shortcode.php');

?>
