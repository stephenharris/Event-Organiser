=== Event Organiser ===
Contributors: stephenharris
Donate link: http://www.wp-event-organiser.com/donate
Tags: events, event, event categories, event organizer, events calendar, event management, ical, locations, google map, widget, venues, maps, gigs, shows,
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 2.0.2
License: GPLv3

Create and maintain events, including complex reoccurring patterns, venue management (with Google maps), calendars and customisable event lists

== Description ==

Event Organiser adds event management that integrates well with your WordPress site. By using WordPress' in-built 'custom post type', this plug-in allows you to create events that have the same functionality as posts, while adding further features that allow you to manage your events. This includes the possibility of repeating your event according to complex schedules and assign your events to venues. This can all be done through an intuitive user interface, which allows you to view your events in the familiar WordPress list or in a calendar page in the amin area.

[Documentation](http://wp-event-organiser.com/documentation/) 
| [Function Reference](http://wp-event-organiser.com/documentation/function-reference/) 
| [Forums](http://wp-event-organiser.com/forums/) 
| [Demo](http://wp-event-organiser.com/demo/) 
| [Booking Add-on](http://wp-event-organiser.com/pro-features)

= Features =

* Adds an **event custom post type** that fits naturally into WordPress and allows for all the functionality of 'posts'.
* Create one-time events or reoccuring events.
* Allows complex reoccuring patterns for events. You can create events that last an arbirtary time, and repeat over a specified period. Supports complex schedules such as *On the third Tuesday of every fourth month* or *Every month on the 16th*.
* Ability to add or remove specific dates to an event
* **Venue admin page** to add and maintain the venues for your events, with Google maps support and a fully-featured content editor.
* Custom metaboxes and meta data support for venues.
* The **Calendar widget**  displays a calendar (identical to the standard WordPress Calendar) that highlights events with links to the events archive page, listing events occuring that day.
* The **Event List widget**  outputs a list of events, and allows you to specify the number of events, restrict to event categories or venues and their order etc.
* The **Event Agenda widget**.
* Year, month and day archive pages
* Relative date queries (for example, query events that finished in the last 24 hours, or events starting in the coming week).
* The **Calendar and Event List shortcodes**, similiar to their respective widgets, for use in themes or in posts and pages.
* Shortcode to dislay a public version of the admin **'full calendar'**.
* The **Venue map shortcodes** to display a map of a venue.
*  **Custom permissions** allow to specifiy which roles have the ability to create, edit and delete events or manage venues.
* **Template** pages include in the plug-in for 'quick-start'. These can be over-ridden by including the appropriately named template files in your theme folder.
* **Event functions** available which extend the post functions (e.g. `the_title()`,`get_the_author()`, `the_author()`) to ouput or return event data (the start date-time, the venue etc). For examples of their use see the [documentation](http://www.wp-event-organiser.com/documentation/function-reference/) or the included template files.
* Assign events to categories and tags, and view events by category or tag.
* Color-coded event categories.
* Venue pages, to view events by venue.
* **Export/import** events to and from ICAL files.
* Delete individual occurrences of events.
* **Public events feed:** allow visitors to subscribe to your events.
* Supports 'pretty permalinks' for event pages, event archives, event category and venue pages.
* (Optionally) automatically delete expired events.


= Localisation =
A big thank you to those who have provided translations for Event Organiser

* French - [Remy Perona](http://remyperona.fr/)
* Spanish - Joseba Sanchez
* German - [Martin Grether](http://www.datema.de/) & [Henning Matthaei](http://www.partnerwerk.de/)
* Italian - Emilio Frusciante, Pio Muto
* Norwegian - Erlend Birkedal
* Swedish - Sofia BrÃ¥vander
* Portuguese (Brazilian) - [Rafael Wahasugui](http://www.twitter.com/rafawhs)
* Dutch  - [Ingrid Ekkers](http://www.247design.nl)
* Polish - [Bartosz Arendt](http://digitalfactory.pl/)
* Russian - [Sergei](www.vortexinter.ru)
* Hungarian - Csaba Erdei 
* Estonian - Kristjan Roosipuu
* Finnish - Kari Tolonen

== Installation ==

Installation is standard and straight forward. 

1. Upload `event-organiser` folder (and all it's contents!) to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Check the settings sub-page of the Events menu


== Frequently Asked Questions ==

= How to create an event =

Events behave very similarly to standard posts. To create a event, select the 'Add New' submenu from 'Events' menu. The chief difference between events and post is the 'Event Details' metabox which sets the date and venue data related to the event.

You can also add events in the Calendar view in a manner similiar to Google Calendar. By selecting one (or multiple) days (in month mode) or times (in week/day mode) you can create an event that spans the selected period. 

= The Event Pages Don't Look Right =

Unfortunately its impossible for the default templates to work with *every* theme. Occasionally, they simply don't work well with a given theme - the event content might not appear right, or the sidebar might seem displaced. The solution is to edit the default templates to fit in with your theme (you can use your theme's own templates as a guideline). [You can find more information here](http://wp-event-organiser.com/documentation/editing-the-templates/)


= How do I display events? =

Events are automatically displayed on the events page: try `www.your-wordpress-site.com/?post_type=event` (If you have permalinks enabled these will have 'prettier' versions). Similarly there are venue and event category pages. Each of these pages have their own associated template which are present in the **template** sub-directory of the Event Organiser plug-in folder. To override the default templates, simply create the appropriately named files in your theme directory.

**Widgets**
The plug-in also provides the following widgets ([see widget documentation](http://wp-event-organiser.com/documentation/widgets/)):

* **Event list** - list events allows with options to filter by venue, categories and dates.
* **Calendar** - display a calendar, similiar to the WordPress calendar, that displays your events.
* **Agenda** - displays your events in a list grouped by date and navigated with AJAX.

**Shortcodes**
Among others, the plug-in also provides the following shortcodes ([see shortcode documentation](http://wp-event-organiser.com/documentation/shortcodes/)):

* **Event list & Widget Calendar** - shortcode versions of their widget counterparts.
* **Full calendar** - a calendar, similiar to the admin calendar, with optional month, week and day views and category and venue filters

**Template Functions**
Finally, the plug-in provides a function `eo_get_events()` which is similiar to WordPress' `get_posts()`. It returns an array of post objects (where the posts are events), and this can be used to display events through editing your theme. The usual WordPress functions for display associated information (author, title etc) are still available to you, and the plug-in provides a similar set of functions to display event related data (dates, venues etc). [See the documentation for mmore information](http://www.wp-event-organiser.com/documentation/function-reference/).


= The full calendar doesn't display any events =

The calendar should display all published events. If you find the calendar doesn't appear this is usually caused by the theme you are using, and is verifiable by temporarily switching to the TwentyEleven theme. If the theme is the cause this is normally because:

* The theme de-registers the jQuery / jQuery UI shipped with WordPress and registers an outdated version
* The theme does not call [`wp_footer`](http://codex.wordpress.org/Function_Reference/wp_footer) in the footer

If the calendar *does* appear, but gets stuck loading, the cause is usually the AJAX response. If your site is in 'debug' mode - this can be due to error messages from other plug-ins being printed. You can view the AJAX response in your browsers console (E.g. Firefox's firebug or Chrome's dev tools). If you are still unable to determine the cause of the problem, or how to fix, please use the plug-in forums with a link to your site and I'll take a look.


= I cannot navigate between months on the widget calendar =

If clicking on the 'next' month causes the page to reload - the javascript has not been loaded. This is usually because the theme does not call [`wp_footer`](http://codex.wordpress.org/Function_Reference/wp_footer) in the footer. 

If the calendar simply does not respond this is usually because your theme does not allow widgets to add their own ID and classes. Somewhere in the theme folder your theme will make use of `register_sidebar()` (probably in `functions.php`. It should look something like:

    register_sidebar(array(
      'name' => __( 'Side bar name' ),
      'id' => 'sidebar-id',
      'description' => __( 'Widget area description' ),
      'before_title' => '<h1>',
      'after_title' => '</h1>',
      'before_widget' => '<div id="%1$s" class="widget %2$s">',
      'after_widget' => '</div>',
    ));
    
Notice the `%1$s` and `%2$s` in the `before_widget` argument. These allow the widget to add their own ID and classes to the widget. If your theme does not use `class="widget %2$s"` (they should!) the month navigation for the widget calendar will not work. 

If you are still unable to determine the cause of the problem, or how to fix, please use the plug-in forums with a link to your site and I'll take a look.


= What ShortCodes are available? = 

Event Organiser provides the following shortcodes:

* `[eo_events]`  - displays a list of events allows with options to filter by venue, categories and dates.
* `[eo_calendar]`  - displays a widget-calendar of your events, similiar to WordPress' calendar, and navigated with AJAX.
* `[eo_fullcalendar]`  - displays a calendar, similiar to the admin calendar, with optional month, week and day views and category and venue filters.
* `[eo_venue_map]` - displays a Google map of the current venue, or of a particular venue given as an attribute.
* `[eo_subscribe]` - wraps the content in a link which allows visitors to subscribe to your events; there are two types: 'Google' and 'Webcal'.

More information on shortcodes is [available here](http://wp-event-organiser.com/documentation/shortcodes/)

== Screenshots ==

1. Event admin screen
2. Venue admin screen
3. Event details metabox, showing the full reoccurrence options and venue selection
4. Venue editing screen, with Google Maps
5. Calendar View screen
6. View of a venue page on the front-end (in a theme based on WordPress TwentyEleven)

== Changelog ==

= 2.0.2 =
* Prevent new row being created on single event save

= 2.0.1 =
* Fixed minor bugs related to templates
* Add shortlink to events
* Add 'start date' arguments to eo_get_event_fullcalendar() & fullCalendar shortcode

= 2.0 =
* Launch of [Event Organiser Pro](http://wp-event-organiser.com/pro-features/)
* Added 'system info' page for debugging (visible in the admin menu only when `WP_DEBUG` is set to true)
* Fixes 'events' menu position becoming lost when updating settings
* Configure the event expire option via the `eventorganiser_events_expire_time` filter
* Corrected Spanish translation
* Corrected potential duplication of ID in the event meta list. Props [@fusion2004](https://github.com/stephenharris/Event-Organiser/pull/73)

= 1.8.5 =
* Fixes venue/category 'no events found' error when Events are excluded from search. Introduced in 1.8.
* Fixes uninstall routine typo
* Corrected documentation
* Ensured backwards compatibility for 3.3
* Added filters

= 1.8.4 =
* Fixes bug with the widget calendar (see [this post](http://wp-event-organiser.com/forums/topic/calendar-widget-nextprevious-links-bug/))

= 1.8.3 =
* Fixes bugs relating to cross-post-type queries. (see [#65](https://github.com/stephenharris/Event-Organiser/issues/65)
* Fixes bug with Event feed introduced in 1.8, (see [#69](https://github.com/stephenharris/Event-Organiser/issues/69)
* Resolves conflict with WPML
* Adds filters to event date functions (see [#67](https://github.com/stephenharris/Event-Organiser/issues/67)
* Resolve conflicts with jQuery 1.9

= 1.8.2 =
* Fixes event taxonomy sorting bug introduced in 1.8
* Adds finish translation

= 1.8.1 =
* Fixes fatal error on activation introduce in 1.8

= 1.8 =
* Added venue & category widgets
* Added multi-site support.
* Improved event query handling (see [#58](https://github.com/stephenharris/Event-Organiser/issues/58)
* Added %venue_city% and %venue_shortcode% tags (see [#42](https://github.com/stephenharris/Event-Organiser/issues/42)
* Added fullCalendar title options
* Fixes bug with deleting expired events
* Fixes event importer truncating details with some ics files

= 1.7.4 =
* Adds venue state & city shortcode/widget tags See [#42](https://github.com/stephenharris/Event-Organiser/issues/42).
* Fixes breaking series looses end date. See [#45](https://github.com/stephenharris/Event-Organiser/issues/45).
* Work around for a bug introduced by "Comprehensive Google Map Plugin". See [#49](https://github.com/stephenharris/Event-Organiser/issues/49).
* Fixes category permalink option missing. Fixes See [#50](https://github.com/stephenharris/Event-Organiser/issues/50).
* Work-around for php5.2 cache bug
* Adds 'buffer' function to pre_get_posts. See [#55](https://github.com/stephenharris/Event-Organiser/issues/55).

= 1.7.3 =
* Use home_url instead of site_url.
* Fixes EO not recognising event template in theme. Props James Andrews.
* Fixes bug when event-tag is not registered. Props James Andrews.

= 1.7.2 =
* Fixes template bug introduced in 1.7.1
* Check permissions before adding 'add new venue' button.
* Fixes fullCalendar 'category' attribute bug.

= 1.7.1 =
* Fixes archive bug. See [ticket](https://github.com/stephenharris/Event-Organiser/issues/39)
* Remove 'with_front' from archive links.
* Removes obsolete event_allday column.

= 1.7 =
* [Added city & state fields](https://github.com/stephenh1988/Event-Organiser/pull/7). Props @JoryHogeveen
* Improved default templates ([see ticket](https://github.com/stephenh1988/Event-Organiser/issues/14))
* Improved theme compatibility templates ([see ticket](https://github.com/stephenh1988/Event-Organiser/issues/13))
* Added support for [year, month and day archives](https://github.com/stephenh1988/Event-Organiser/issues/32)
* Added `eo_get_current_occurrence_of()` (see [ticket](https://github.com/stephenh1988/Event-Organiser/issues/31))
* [Localise Google Maps](https://github.com/stephenh1988/Event-Organiser/issues/26)
* (Optionally) Remove 'add to Google' link from agenda (see [ticket](https://github.com/stephenh1988/Event-Organiser/issues/25))
* Hide Google Map when no venue is selected enhancement
* Allow for [customised language file](https://github.com/stephenh1988/Event-Organiser/issues/16).
* Copy custom fields when breaking a series [see ticket](https://github.com/stephenh1988/Event-Organiser/issues/12).
* General UI improvements ( [venues](https://github.com/stephenh1988/Event-Organiser/issues/5), [calendar](https://github.com/stephenh1988/Event-Organiser/issues/28))

= 1.6.3 =
 * Fixes 'EOAjax is not defined' error (see [https://github.com/stephenh1988/Event-Organiser/issues/20](https://github.com/stephenh1988/Event-Organiser/issues/27))

= 1.6. 2 =
* Fixes 'zoom' bug for venue maps (see [https://github.com/stephenh1988/Event-Organiser/issues/20](https://github.com/stephenh1988/Event-Organiser/issues/20))
* Fixes yes/no label error for 'are current events past?' (see [https://github.com/stephenh1988/Event-Organiser/issues/23](https://github.com/stephenh1988/Event-Organiser/issues/23))
* Adds a condensed jQuery UI for frontend css (see [https://github.com/stephenh1988/Event-Organiser/issues/22](https://github.com/stephenh1988/Event-Organiser/issues/22))
* `eo_get_venues()` now automatically casts IDs as integers (see [https://github.com/stephenh1988/Event-Organiser/issues/21](https://github.com/stephenh1988/Event-Organiser/issues/21))
* General code refactoring
* Improved documentation

= 1.6.1 =
* Fixes js bug for weekly events (see [https://github.com/stephenh1988/Event-Organiser/issues/17](https://github.com/stephenh1988/Event-Organiser/issues/17)).
* Fixes recurrence bug for some monthly events (see [https://github.com/stephenh1988/Event-Organiser/issues/10](https://github.com/stephenh1988/Event-Organiser/issues/10)).

= 1.6 =
* You can create venues 'on the fly'
* Adds venue map tooltip
* Extra 'Google Map' options for the venue map shortcode
* Adds further options to the fullCalendar calendar shotcode (`[eo_fullcalendar]`) - [see this page](http://wp-event-organiser.com/documentation/shortcodes/event-full-calendar-short-code/)
* Allows multiple venues on a map
* Added options to widget calendar
* Improved UI
* 'Under the hood' improvements
* Improved documentation & source-code comments
* More tags for shortcode & event list widget template - [see this page](http://wp-event-organiser.com/documentation/shortcodes/event-list-shortcode/)
* Extra hooks available, see: http://wp-event-organiser.com/documentation/developers/hooks/
* Various bug fixes and major code refactoring (especially of javascript).
* Improved default location for venues: https://github.com/stephenh1988/Event-Organiser/issues/3

A special thanks to **kuemerle** and **csaba-erdei**.

= 1.5.7 =
* Fixes ICS related bugs
* Minor UI improvements
* Adds Danish translation

= 1.5.6 =
* Add filter for formatting datetime objects
* Minor UI improvements
* Added Russian translation
* Depreciate use of 'occurrence' for date functions. Use occurrence ID instead. See http://wp-event-organiser.com/documentation/function/eo_get_the_start/
* Custom schedule is considered a recurring event
* Fixed import ICS bug
* Fixed calendar feed posts_per_page_rss bug
* Fixed shortcode bug for tooltip excerpts

= 1.5.5 =
* Fixes IE7/8-fullCalendar bug experienced on some themes
* Fixed timezone bug when creating events in the calendar.
* Corrects tooltip date/time formatting
* Fixes venue bulk/quick edit bug
* Fixes venue link for shortcode/widget tags

= 1.5.4 =
* Fixes monthly recurring event creation bug

= 1.5.3 =
* Fixes 'group by series' bug
* Fixes event creation bug (occurs on some servers)
* Fixes eo_get_schedule_end bug

= 1.5.2 =
* Fixes event widget/shortcode url bug
* Fixes php 5.2 duration bug

= 1.5.1 =
* Fixes permissions not added on new installs

= 1.5 =
* Caching and other performance improvements
* Adding tooltips for the fullcalendar
* Select/deselect individual occurrences
* Options added for event agenda
* Adds classes to event list
* Separate event and archive permalink structure
* Improved UI see http://core.trac.wordpress.org/ticket/18909
* Improved fullCalendar look
* Adds support for hiding/minimizing venue post boxes
* Adds retina support for screen icons (thanks to numeeja (http://cubecolour.co.uk/))
* Fixes 'trying to add extra pimary key' (on activation) bug
* Fixes some locales using comma in floats
* Fixes GROUP BY bug

= 1.4.2 =
* Fixes event list widget past events, and template not saving correctly bugs
* Fixes 'add new venue' error message
* Fixes shortcode error message when retrieving url of non-existant venue

= 1.4.1 =
* Fixes a weekly schedule bug, occurs for some users.

= 1.4 =
* A big update: venue address data migrated to new venue meta table
* Introduces support for venue meta data and custom metaboxes (see )
* Improved venue admin page UI

= 1.3.6 =
* Works with WordPress 3.4

= 1.3.5 =
* Fixed events export bug.
* Cached timezone object, improved performance.


= 1.3.4 =
* `%cat_color%` now works
* Fixed IE8+ calendar and agenda bugs
* Fixed shortcode calendar bug
* Fixed timezone for 'add to google' link


= 1.3.3 =
* Added 'no events' option for event list widget
* Added template tags for widget/shortcode: `%cat_color%` and `%event_excerpt%`
* Added hook `eventorganiser_calendar_event_link` to alter full calendar event link
* Added `eo_has_event_started`, `eo_has_event_finished`, `eo_event_color`,`eo_get_blog_timezone` functions
* Fixed the following bugs
 * Widget calendar (affecting some themes)
 *  Agenda date 'undefined' (affecting some browsers)
 * HTML in widget template breaking form
 * Fullcalendar in IE6/7
 * Event-tag template not loading
 *Other minor bugs


= 1.3.2 =
* Fixes permalink bug introduced in 1.3.1

= 1.3.1 =
* 'Clever' template hierarchy. Recognises templates for specific venues, categories or tags. E.g. `taxonomy-event-venue-myvenueslug.php`
* Fixed menu related bugs
* Fixed bulk/quick edit errors
* Fixed numeric venue slug bug
* Widget calendar - added class 'today' to current date and 'show past events' option
* Fixed calendar key (chrome browser) bug
* Pretty Permalinks can now be turned off

= 1.3 =
* Converted venues to event-venue taxnomy terms
* Improved add events link to menu option
* Import Categories and Venues
* Break a reoccurring event
* Templates for widgets (syntax as for event list shortcode)
* Time format option for full calender shortcode
* Quick/Bulk edit event venue
* Category key option for full calendar shortcode
* Set zoom level on venue map shortcode
* Full calendar shortcode attribute to restrict events to a specific venue / category
* Fixed 'daylight saving' bug for php5.2
* Fixed IE7 Widget calendar bug (thanks to [Fej](http://wordpress.org/support/profile/fej) )

= 1.2.4 =
* Fixed bugs concerning
 * Relatve date formats
 * Child-theme templates
 * Localisation
 * Calendar shortcode on php5.2

= 1.2.3 =
* Corrected potential 'class does not exist' bug

= 1.2.2 =
* Event list shortcode, `[eo_events]`, now supports templates which can be enclosed in the shortcode. [See the documenation](http://www.harriswebsolutions.co.uk/event-organiser/documentation/shortcodes/event-list-shortcode/).
* `eo_get_events` and the event list shortcode now support relative date formats for data parameters (e.g. `event_start_before='+1 week',`event_end_after='now'`). [See the documenation](http://www.harriswebsolutions.co.uk/event-organiser/documentation/relative-date-formats/).
* `eo_format_date` now supports relative date formats
* Added `eo_get_category_color` function.
* Added German and Spanish translations
* Fixed PHP 5.2 related bugs affecting calendars and events export
* Fixed event permissions bug
* Fixed other minor bugs reported here

= 1.2.1 =

* Fixed permalink bug
* Venue map marker can be manually dragged to a specific location
* Event Organiser is now compatible with PHP 5.2+
* Fixed minor calendar icon bug in IE

= 1.2 =
* Public events feed
* Delete individual occurrences
* Color-coded event categories
* Event tags
* (Optionally) automatically delete expired events
* Custom permalink structure
* Added `eo_subscribe` shortcode to create a subscribe link
* Agenda widget 
* Venue descriptions now support shortcodes
* Custom navigation menu title for events
* Option to decide when event is past
* Show all occurrences of an event or 'group occurrences'
* Improved user-interface
* Added template functions: `eo_get_the_occurrences`, `eo_get_the_venues`, `eo_event_venue_dropdown`, `eo_event_category_dropdown`, `eo_is_allday`, `eo_get_the_GoogleLink`, `eo_get_events_feed`. See [template functions documentation](http://www.harriswebsolutions.co.uk/event-organiser/documentation/function-reference/)
* Localisation (currently translations for French and Portugese (Brazil) are included)
* Improved default templates
* Fixed bugs [reported here](http://www.harriswebsolutions.co.uk/event-organiser/forums/forum/bugs/)

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

= 2.0 =
Event Organiser Pro has [launched](http://wp-event-organiser.com/pro-features/)!

= 1.8.3 =
Please note this is an important update, fixing problems related to cross-post-type queries and the event feed introduced in 1.8+.

= 1.8.2 =
If you have upgrade to 1.8 or 1.8.1 please upgrade to 1.8.2. This update includes fixes to bugs introduced in 1.8.

= 1.5 =
1.5 is a big update, so please back-up before upgrading.

= 1.3.2 =
This fixes permalink bug introduced in 1.3.1. If you upgraded to 1.3.1, you should upgrade to 1.3.2. You're advised to 'flush rewrite rules' by simplying visiting your permalinks setting page.

= 1.3 =
This a fairly big update and includes converting venues into a custom taxonomy. As a result some venue slugs *may* change. See the [plug-in website](http://www.harriswebsolutions.co.uk/event-organiser/uncategorized/2012/whats-new-in-1-3/) for more details.

= 1.0.4 =
The templates have been adapted to work as is in for more themes. Error messages now display for unsupported versions.
