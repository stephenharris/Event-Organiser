=== Event Organiser ===
Contributors: stephenh1988
Donate link: http://www.harriswebsolutions.co.uk/event-organiser/
Tags: events, event, event categories, event organiser, event calendar, events calendar, event management, ical, locations, google map, widget, venues, maps, gigs, shows,
Requires at least: 3.3
Tested up to: 3.3.1
Stable tag: 1.1.1

Create and maintain events, including complex reoccurring patterns, venue management (with Google maps), calendars and customisable event lists

== Description ==

Event Organiser adds event management that integrates well with your WordPress site. By using WordPress' in-built 'custom post type', this plug-in allows you to create events that have the same functionality as posts, while adding further features that allow you to manage your events. This includes the possibility of repeating your event according to complex schedules and assign your events to venues. This can all be done through an intuitive user interface, which allows your to view your events in the familiar WordPress list or view all occurrences of your events in a calendar page in the amin area.

Requires **WordPress 3.3** and **PHP 5.3** (or higher)

= New Features =
* Public version of the admin calendar (shortcode).
* 'mini-calendar' navigation for the admin calendar.
* Import events from an ICAL file.
* 'Fully featured' content editor for the venue admin page: include media content from your library.
* Venue descriptions can now understand shortcodes.

= Features =
* Adds an **event custom post type** that fits naturally into WordPress and allows for all the functionality of 'posts'.
* Create one-time events or reoccuring events
* Allows complex reoccuring patterns for events. You can create events that last an arbirtary time, and repeat over a specified period. Events can repeat daily through to yearly, allowing complex schedules such as 'On the third Tuesday of every fourth month' or 'Every month on the 16th'
* **Venue admin page** to add and maintain the venues for your events. (With Google map support to display a map of the venue)
* The **Calendar widget**  displays a calendar (identical to the standard WordPress Calendar) that highlights events with links to the events archive page, listing events occuring that day.
* The **Event List widget**  outputs a list of events, and allows you to specify the number of events, restrict to event categories or venues and their order etc.
* The **Calendar and Event List shortcodes**, similiar to their respective widgets, for use in themes or in posts and pages.
* The **Venue map shortcodes** to display a map of a venue
*  **Custom permissions** allow to specifiy which roles have the ability to create, edit and delete events or manage venues.
* **Template** pages included in the plug-in for 'quick-start'. These can be over-ridden by including the appropriately named template files in your theme folder 
* **Event functions** available which extend the post functions (e.g. `the_title()`,`get_the_author()`, `the_author()`) to ouput or return event data (the start date-time, the venue etc). For examples of their use see the [documentation](http://www.harriswebsolutions.co.uk/event-organiser/documentation/) or the included template files.
* Assign events to categories, and view events by category
* Venue pages, to view events by venue
* Export events to .ics file
* Supports 'pretty permalinks' for event pages, event archives, event category and venue pages


= Planned Features =
* Public ICAL Feed
* Introducing actions and filters to allow developers or plug-ins to modify and interact with Event Organiser
* Allowing users to exclude or include specific dates in an event's schedule
* Dashboard widgets (upcoming events / expiring events)


== Installation ==

Installation is standard and straight forward. 

1. Upload `event-organiser` folder (and all it's contents!) to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Check the settings sub-page of the Events menu


== Frequently Asked Questions ==

= How to create an event =
Events behave very similarly to standard posts. To create a event, select the 'Add New' submenu from 'Events' menu. The chief difference between events and post is the 'Event Details' metabox which sets the date and venue data related to the event.

You can also add events in the Calendar view in a manner similiar to Google Calendar. By selecting one (or multiple) days (in month mode) or times (in week/day mode) you can create an event that spans the selected period. 


= How do I display events? =

Events are automatically displayed on the events page: try `www.your-wordpress-site.com/?post_type=event` (If you have permalinks enabled these will have 'prettier' versions). Similarly, `?event-category=` would display events of a specfied category, and `?venue=`, events at specified venue. FInally `?event=` will show the specified event.

Each of the above have their own associated template. These template files are present in the template sub-directory of the Event-Organiser plug-in folder. To override the default templates, simply create the appropriately named files in your theme directory.

The plug-in also provides event listing and event calendar widgets and shortocdes that can be used to display events.

Finally, the plug-in provides a function `eo_get_events` which is similiar to WordPress' `get_posts`. The function returns an array of post objects (where the posts are events), and this can be used to display events through editing your theme. The usual WordPress functions for displaying associated information (author, title etc) are still available to you, and the plug-in provides a similar set of functions to display event related data (dates, venues etc). See the [documentation](http://www.harriswebsolutions.co.uk/event-organiser/documentation/function-reference/).

= How do I display a calendar of events? =
There are two shortcodes you can use. 
* `[eo_calendar]` provides a widget-like calendar  
* `[eo_fullcalendar]` provides a calendar, similiar to the admin calendar, with optional month, week and day views.
Both use AJAX to populate the calendars. Documentation of these can be found [here](http://www.harriswebsolutions.co.uk/event-organiser/documentation/shortcodes/).

= What shortcodes does the plug-in provide? =
* `[eo_events]` - lists events in similar way to the 'events' widget, and allows you to filter by venue, categories and dates
* `[eo_venue_map]` - displays a Google map of the current venue, or of a particular venue given as an attribute.
* `[eo_calendar]` - a widget-like calendar  
* `[eo_fullcalendar]` - a calendar, similiar to the admin calendar, with optional month, week and day views.
Documentation of the plug-in shortcodes can be found [here](http://www.harriswebsolutions.co.uk/event-organiser/documentation/shortcodes/).

== Screenshots ==

1. Event admin screen
2. Venue admin screen
3. Event details metabox, showing the full reoccurrence options and venue selection
4. Venue editing screen, with Google Maps
5. Calendar View screen
6. View of a venue page on the front-end (in a theme based on WordPress TwentyEleven)

== Changelog ==

= 1.1.1 =
A minor update, fixing a few bugs and improving the (admin and public) calendars' performance. The bug which meant calendars and the calendar widget couldn't be displayed together is now fixed. For a full list of alterations [see here](http://www.harriswebsolutions.co.uk/event-organiser/uncategorized/2012/bug-fixing-update-due-1-1-1/).

= 1.1 =
Improved admin calendar navigation, with category/venue filters. Public version of the 'admin calendar' now available as a shortcode. You can now import events from an ICAL file. Further details included in the ics export file. The venue content editor is now fully featured. Venue descriptions now understand shortcodes. Fixed a few bugs.

= 1.0.5 =
Fixed export bug.

= 1.0.4 =
Introduced warning messages for unsupported PHP / WP versions and missing tables. Updated templates to work with more themes. Updated event table install.

= 1.0.3 =
Fixed 'blank screen of death' for unsupported versions (WP < 3.3). The plug-in will still not operate correctly for versions before 3.3.

= 1.0.2 =
Fixed ics exporter and deactivation/uninstall

= 1.0.1 =
Minor bug fixes and readme update.

= 1.0.0 =
Initial release

== Upgrade Notice ==

= 1.0.4 =
The templates have been adapted to work as is in for more themes. Error messages now display for unsupported versions.
