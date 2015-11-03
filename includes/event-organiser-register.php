<?php
/**
 * Register jQuery scripts and CSS files
 * Hooked on to init
 *
 * @since 1.0.0
 * @ignore
 * @access private
 */
function eventorganiser_register_script() {
	global $wp_locale;
	$version = defined( 'EVENT_ORGANISER_VER' ) ? EVENT_ORGANISER_VER : false;

	$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$rtl = is_rtl() ? '-rtl' : '';

	/* Moment.js */
	wp_register_script( 'eo_momentjs', EVENT_ORGANISER_URL."js/moment{$ext}.js", '2.9.0', true );

	/* FullCalendar */
	wp_register_script( 'eo_fullcalendar', EVENT_ORGANISER_URL."js/fullcalendar{$ext}.js",array(
		'jquery',
		'eo_momentjs',
		'jquery-ui-core',
		'jquery-ui-widget',
		'jquery-ui-button',
	),$version,true);

	/* Google Maps */
	$protocal = is_ssl() ? 'https://' : 'http://';
	wp_register_script( 'eo_GoogleMap', $protocal.'maps.googleapis.com/maps/api/js?sensor=false&language='.substr( get_locale(), 0, 2 ) );

	/* Front-end script */
	wp_register_script( 'eo_front', EVENT_ORGANISER_URL."js/frontend{$ext}.js",array(
		'jquery','eo_qtip2',
		'jquery-ui-core',
		'jquery-ui-widget',
		'jquery-ui-button',
		'jquery-ui-datepicker',
		'eo_fullcalendar',
		'eo-wp-js-hooks',
	),$version,true);

	/* Add js variables to frontend script */
	$category = get_taxonomy( 'event-category' );
	$venue    = get_taxonomy( 'event-venue' );
	$tag      = get_taxonomy( 'event-tag' );
	wp_localize_script( 'eo_front', 'EOAjaxFront', array(
		'adminajax' => admin_url( 'admin-ajax.php' ),
		'locale'    => array(
			'locale'      => substr( get_locale(), 0, 2 ),
			'isrtl'       => $wp_locale->is_rtl(),
			'monthNames'  => array_values( $wp_locale->month ),
			'monthAbbrev' => array_values( $wp_locale->month_abbrev ),
			'dayNames'    => array_values( $wp_locale->weekday ),
			'dayAbbrev'   => array_values( $wp_locale->weekday_abbrev ),
			'ShowMore'    => __( 'Show More', 'eventorganiser' ),
			'ShowLess'    => __( 'Show Less', 'eventorganiser' ),
			'today'       => __( 'today', 'eventorganiser' ),
			'day'         => __( 'day', 'eventorganiser' ),
			'week'        => __( 'week', 'eventorganiser' ),
			'month'       => __( 'month', 'eventorganiser' ),
			'gotodate'    => __( 'go to date', 'eventorganiser' ),
			'cat'         => $category ? $category->labels->view_all_items : false,
			'venue'       => $venue    ? $venue->labels->view_all_items    : false,
			'tag'         => $tag      ? $tag->labels->view_all_items      : false,
			//Allow themes to over-ride juqery ui styling and not use images
			'nextText' => '>',
			'prevText' => '<',
		),
	));

	/* WP-JS-Hooks */
	wp_register_script( 'eo-wp-js-hooks', EVENT_ORGANISER_URL."js/event-manager{$ext}.js",array('jquery'),$version,true);

	/* Q-Tip */
	wp_register_script( 'eo_qtip2', EVENT_ORGANISER_URL.'js/qtip2.js',array('jquery'),$version,true);

	/* Styles */
	eo_register_style( 'eo_calendar-style', EVENT_ORGANISER_URL . "css/fullcalendar{$ext}.css", array(), $version );
	eo_register_style( 'eo_front', EVENT_ORGANISER_URL . "css/eventorganiser-front-end{$rtl}{$ext}.css", array() , $version );
}
add_action( 'init', 'eventorganiser_register_script' );

 /**
 *Register jQuery scripts and CSS files for admin
 *
 * @since 1.0.0
 * @ignore
 * @access private
 */
function eventorganiser_register_scripts(){
	$version = defined( 'EVENT_ORGANISER_VER' ) ? EVENT_ORGANISER_VER : false;
	$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$rtl = is_rtl() ? '-rtl' : '';

	/*  Venue (map) utility script */
	wp_register_script( 'eo-venue-util', EVENT_ORGANISER_URL."js/venue-util{$ext}.js",array(
		'jquery',
		'eo_GoogleMap'
	),$version,true);

	/*  Venue script for venue edit */
	wp_register_script( 'eo-venue-admin', EVENT_ORGANISER_URL."js/venue-admin{$ext}.js",array(
		'jquery',
		'eo-venue-util'
	),$version,true);

	/**
	 * jquery-timepicker
	 *
	 * @license MIT
	 * @link    https://github.com/jonthornton/jquery-timepicker
	 */
	wp_register_script( 'eo-timepicker', EVENT_ORGANISER_URL . "js/jquery.timepicker{$ext}.js", array(
		'jquery',
	), $version, true );

	/**
	 * Datepair.js
	 *
	 * @license MIT
	 * @link    https://github.com/jonthornton/Datepair.js
	 */
	wp_register_script( 'eo-datepair', EVENT_ORGANISER_URL . "js/datepair{$ext}.js", array(), $version, true );
	wp_register_script( 'eo-jquery-datepair', EVENT_ORGANISER_URL . "js/jquery.datepair{$ext}.js", array(
		'eo-datepair',
		'jquery',
	), $version, true );

	wp_register_script( 'eo_event', EVENT_ORGANISER_URL."js/event{$ext}.js",array(
		'jquery',
		'jquery-ui-datepicker',
		'eo-timepicker',
		'eo-jquery-datepair',
		'eo-venue-util',
		'jquery-ui-autocomplete',
		'jquery-ui-widget',
		'jquery-ui-button',
		'jquery-ui-position',
	),$version,true);

	wp_register_script( 'eo-edit-event-controller', EVENT_ORGANISER_URL."js/edit-event-controller{$ext}.js",array(
			'jquery',
			'eo_event',
	),$version,true);

	/*  Script for admin calendar */
	wp_register_script( 'eo_calendar', EVENT_ORGANISER_URL."js/admin-calendar{$ext}.js",array(
		'eo_fullcalendar',
		'jquery-ui-datepicker',
		'jquery-ui-autocomplete',
		'jquery-ui-widget',
		'jquery-ui-button',
		'jquery-ui-dialog',
		'jquery-ui-tabs',
		'jquery-ui-position',
	),$version,true);

	/*  Pick and register jQuery UI style */
	wp_register_style( 'eventorganiser-jquery-ui-style', EVENT_ORGANISER_URL."css/eventorganiser-jquery-ui{$rtl}{$ext}.css", array(), $version );

	// Timepicker CSS
	wp_register_style( 'eo-timepicker', EVENT_ORGANISER_URL . "css/jquery.timepicker{$ext}.css", array(), $version );

	/* Admin styling */
	wp_register_style( 'eventorganiser-style', EVENT_ORGANISER_URL."css/eventorganiser-admin-style{$rtl}{$ext}.css", array(
		'eventorganiser-jquery-ui-style',
		'eo-timepicker'
	), $version );

	/* Inline Help */
	wp_register_script( 'eo-inline-help', EVENT_ORGANISER_URL.'js/inline-help.js',array( 'jquery', 'eo_qtip2' ), $version, true );
}
add_action( 'admin_init', 'eventorganiser_register_scripts', 5 );

/**
 * The "Comprehensive Google Map Plugin" plug-in deregisters all other Google scripts registered
 * by other plug-ins causing these plug-ins not to function. This plug-in removes that behaviour.
 *
 * Of course if two google scripts are loaded there may be problems, but this is better than always having
 * experiencing a 'bug'. At time writing the function responsible `cgmp_google_map_deregister_scripts()`
 * can be found here {@see https://github.com/azagniotov/Comprehensive-Google-Map-Plugin/blob/master/functions.php#L520 }
 *
 * @see https://github.com/stephenharris/Event-Organiser/issues/49
 * @see https://wordpress.org/support/topic/googlemap-doesnt-shown-on-event-detail-page
 * @since 1.7.4
 * @ignore
 * @access private
 */
function eventorganiser_cgmp_workaround(){
	remove_action( 'wp_head', 'cgmp_google_map_deregister_scripts', 200 );
}
add_action( 'wp_head', 'eventorganiser_cgmp_workaround', 1 );

 /**
 * Check the export and event creation (from Calendar view) actions.
 * These cannot be called later. Most other actions are only called when
 * the appropriate page is loading.
 *
 * @since 1.0.0
 * @ignore
 * @access private
 */
function eventorganiser_admin_init(){
	global $EO_Errors;
	$EO_Errors = new WP_Error();
}
add_action( 'admin_init', 'eventorganiser_admin_init', 0 );
add_action( 'load-settings_page_event-settings', array( 'Event_Organiser_Im_Export', 'get_object' ) );

 /**
 * @since 1.0.0
 * @ignore
 * @access private
 */
function eventorganiser_public_export(){
	if( eventorganiser_get_option('feed') ){
		add_feed('eo-events', array('Event_Organiser_Im_Export','get_object'));
	}
}
add_action('init','eventorganiser_public_export');

/**
 * Queues up the javascript / style scripts for Events custom page type
 * Hooked onto admin_enqueue_scripts
 *
 * @since 1.0.0
 * @ignore
 * @access private
 */
function eventorganiser_add_admin_scripts( $hook ) {
	global $post,$current_screen,$wp_locale;

	if ( $hook == 'post-new.php' || $hook == 'post.php') {
		if( $post->post_type == 'event' ) {

			wp_enqueue_script( 'eo-edit-event-controller' );
			wp_localize_script( 'eo_event', 'EO_Ajax_Event', array(
					'ajaxurl'   => admin_url( 'admin-ajax.php' ),
					'wpversion' => get_bloginfo( 'version' ),
					'startday'  => intval( get_option( 'start_of_week' ) ),
					'format'    => eo_php2jquerydate( eventorganiser_get_option( 'dateformat' ) ),
					'current_user_can' => array(
						'manage_venues' => current_user_can( 'manage_venues' ),
					),
					'is24hour' => eventorganiser_blog_is_24(),
					'location' => get_option( 'timezone_string' ),
					'locale'   => array(
						'isrtl'       => $wp_locale->is_rtl(),
						'monthNames'  => array_values( $wp_locale->month ),
						'monthAbbrev' => array_values( $wp_locale->month_abbrev ),
						'dayAbbrev'   => array_values( $wp_locale->weekday_abbrev ),
						'showDates'   => __( 'Show dates', 'eventorganiser' ),
						'hideDates'   => __( 'Hide dates', 'eventorganiser' ),
						'weekDay'     => $wp_locale->weekday,
						'meridian'    => array( $wp_locale->get_meridiem( 'am' ), $wp_locale->get_meridiem( 'pm' ) ),
						'hour'        => __( 'Hour', 'eventorganiser' ),
						'minute'      => __( 'Minute', 'eventorganiser' ),
						'day'         => __( 'day', 'eventorganiser' ),
						'days'        => __( 'days', 'eventorganiser' ),
						'week'        => __( 'week', 'eventorganiser' ),
						'weeks'       => __( 'weeks', 'eventorganiser' ),
						'month'       => __( 'month', 'eventorganiser' ),
						'months'      => __( 'months', 'eventorganiser' ),
						'year'        => __( 'year', 'eventorganiser' ),
						'years'       => __( 'years', 'eventorganiser' ),
						'daySingle'   => __( 'every day', 'eventorganiser' ),
						'dayPlural'   => __( 'every %d days', 'eventorganiser' ),
						'weekSingle'  => __( 'every week on', 'eventorganiser' ),
						'weekPlural'  => __( 'every %d weeks on', 'eventorganiser' ),
						'monthSingle' => __( 'every month on the', 'eventorganiser' ),
						'monthPlural' => __( 'every %d months on the', 'eventorganiser' ),
						'yearSingle'  => __( 'every year on the', 'eventorganiser' ),
						'yearPlural'  => __( 'every %d years on the', 'eventorganiser' ),
						'summary'     => __( 'This event will repeat', 'eventorganiser' ),
						'until'       => __( 'until', 'eventorganiser' ),
						'occurrence'  => array(
							__( 'first', 'eventorganiser' ),
							__( 'second', 'eventorganiser' ),
							__( 'third', 'eventorganiser' ),
							__( 'fourth', 'eventorganiser' ),
							__( 'last', 'eventorganiser' ),
						),
					)
				));
			wp_enqueue_style( 'eventorganiser-style' );
		}
	}elseif( $current_screen->id == 'edit-event' ){
			wp_enqueue_style( 'eventorganiser-style' );
	}
}
add_action( 'admin_enqueue_scripts', 'eventorganiser_add_admin_scripts', 998, 1 );


/**
 * Perform database and WP version checks. Display appropriate error messages.
 * Triggered on update.
 *
 * @since 1.4.0
 * @ignore
 * @access private
 */
function eventorganiser_db_checks(){
	global $wpdb;

	//Check tables exist
	$table_errors = array();
	if($wpdb->get_var("show tables like '$wpdb->eo_events'") != $wpdb->eo_events):
		$table_errors[]= $wpdb->eo_events;
	endif;
	if($wpdb->get_var("show tables like '$wpdb->eo_venuemeta'") != $wpdb->eo_venuemeta):
		$table_errors[]=$wpdb->eo_venuemeta;
	endif;

	if(!empty($table_errors)):?>
		<div class="error"	>
		<p>There has been an error with Event Organiser. One or more tables are missing:</p>
		<ul>
		<?php foreach($table_errors as $table):
			echo "<li>".$table."</li>";
		endforeach; ?>
		</ul>
		<p>Please try re-installing the plugin.</p>
		</div>
	<?php	endif;

	//Check WordPress version
	if(get_bloginfo('version')<'3.8'):?>
		<div class="error"	>
			<p>Event Organiser requires <strong>WordPress 3.8</strong> to function properly. Your version is <?php echo get_bloginfo('version'); ?>. </p>
		</div>
	<?php endif;
}


/**
 * Displays any errors or notices in the global $EO_Errors
 *
 * @since 1.4.0
 * @ignore
 * @access private
 */
function eventorganiser_admin_notices(){
	global $EO_Errors;
	$errors=array();
	$notices=array();
	if(isset($EO_Errors)):
		$errors = $EO_Errors->get_error_messages('eo_error');
		$notices= $EO_Errors->get_error_messages('eo_notice');
		if(!empty($errors)):?>
			<div class="error"	>
			<?php foreach ($errors as $error):?>
				<p><?php echo $error;?></p>
			<?php endforeach;?>
			</div>
		<?php endif;?>
		<?php if(!empty($notices)):?>
			<div class="updated">
			<?php foreach ($notices as $notice):?>
				<p><?php echo $notice;?></p>
			<?php endforeach;?>
			</div>
		<?php	endif;
	endif;
}
add_action('admin_notices','eventorganiser_admin_notices');


 /**
 * Adds link to the plug-in settings on the settings page
 *
 * @since 1.5
 * @ignore
 * @access private
 */
function eventorganiser_plugin_settings_link($links, $file) {

	if( $file == 'event-organiser/event-organiser.php' ) {
		/* Insert the link at the end*/
		$links['settings'] = sprintf('<a href="%s"> %s </a>',
			admin_url('options-general.php?page=event-settings'),
			__('Settings','eventorganiser')
                );
        }
	return $links;
}
add_filter('plugin_action_links', 'eventorganiser_plugin_settings_link', 10, 2);

/**
 * Schedules cron job for automatically deleting expired events
 *
 * @since 1.4.0
 * @ignore
 * @access private
 */
function eventorganiser_cron_jobs(){
	wp_schedule_event(time()+60, 'daily', 'eventorganiser_delete_expired');
}


/**
 * Clears the 'delete expired events' cron job.
 *
 * @since 1.4.0
 * @ignore
 * @access private
 */
function eventorganiser_clear_cron_jobs(){
	wp_clear_scheduled_hook('eventorganiser_delete_expired');
}

/**
 * Returns the time in seconds until a specified cron job is scheduled.
 *
 *@since 1.8
 *@see https://wordpress.stackexchange.com/questions/83270/when-does-next-cron-job-run-time-from-now/83279#83279
 *
 *@param string $cron_name The name of the cron job
 *@return int|bool The time in seconds until the cron job is scheduled. False if
 *it could not be found.
*/
function eventorganiser_get_next_cron_time( $cron_name ){
	if( $timestamp = wp_next_scheduled( $cron_name ) ){
		$timestamp = $timestamp - time();
	}
	return $timestamp;
}


/**
 * Callback for the delete expired events cron job. Deletes events that finished at least 24 hours ago.
 * For recurring events it is only deleted once the last occurrence has expired.
 *
 * @since 1.4.0
 * @ignore
 * @access private
 */
function eventorganiser_delete_expired_events(){
	//Get expired events
	$events = eo_get_events(array('showrepeats'=>0,'showpastevents'=>1,'eo_interval'=>'expired'));

	/**
	 * Filters how long (in seconds) after an event as finished it should be considered expired.
	 *
	 * If enabled in *Settings > Event Organiser > General*, expired events are trashed.
	 *
	 * @param int $time_until_expired Time (in seconds) to wait after an event has finished. Defaults to 24 hours.
	 */
	$time_until_expired = (int) apply_filters( 'eventorganiser_events_expire_time', 24*60*60 );
	$time_until_expired = max( $time_until_expired, 0 );

	if($events):
		$now = new DateTime('now', eo_get_blog_timezone());

		foreach($events as $event):

			$start = eo_get_the_start( DATETIMEOBJ, $event->ID, null, $event->occurrence_id );
			$end = eo_get_the_end( DATETIMEOBJ, $event->ID, null, $event->occurrence_id );

			$expired = round(abs($end->format('U')-$start->format('U'))) + $time_until_expired; //Duration + expire time

			$finished =  eo_get_schedule_last( DATETIMEOBJ, $event->ID );
			$finished->modify("+$expired seconds");//[Expired time] after the last occurrence finishes

			//Delete if [expired time] has passed
			if( $finished <= $now ){
				wp_trash_post((int) $event->ID);
			}

		endforeach;
	endif;
}
add_action('eventorganiser_delete_expired', 'eventorganiser_delete_expired_events');



/**
 * Print CSS for event menu icon
 *
 * @since 1.5.0
 * @ignore
 * @access private
 */
function eventorganiser_screen_retina_icon(){
	?>
	<style>
	#adminmenu #menu-posts-event div.wp-menu-image:before {content: '\f145';}
	#adminmenu #menu-posts-event div.wp-menu-image img { display:none; }
	</style>
	<?php
}
add_action( 'admin_print_styles', 'eventorganiser_screen_retina_icon' );


/**
 * Print generic javascript variables to the page
 * @ignore
 */
function eventorganiser_admin_print_scripts() {
	?>
	<script type="text/javascript">
		var eventorganiser = eventorganiser || {};
		eventorganiser.wp_version = '<?php echo esc_js( get_bloginfo( 'version' ) );?>';
	</script>
	<?php
}
add_action( 'admin_print_styles', 'eventorganiser_admin_print_scripts' );


/**
 * Purge the occurrences cache
 * Hooked onto eventorganiser_save_event and eventorganiser_delete_event
 *
 *@access private
 * @ignore
 *@since 1.6
 */
function _eventorganiser_delete_occurrences_cache($post_id=0){
	wp_cache_delete( 'eventorganiser_occurrences_'.$post_id );
	wp_cache_delete( 'eventorganiser_all_occurrences_'.$post_id );
}
//The following need to trigger the cache clear clearly need to trigger a cache clear
$hooks = array('eventorganiser_save_event', 'eventorganiser_delete_event');
foreach( $hooks as $hook ){
	add_action($hook, '_eventorganiser_delete_occurrences_cache');
}


/**
 * Purge the cached results of get_calendar.
 * Hooked onto eventorganiser_save_event, eventorganiser_delete_event, wp_trash_post,
 * update_option_gmt_offset,update_option_start_of_week,update_option_rewrite_rules
 * and edited_event-category.
 *
 *@access private
 * @ignore
 *@since 1.5
 */
function _eventorganiser_delete_calendar_cache() {
	delete_transient( 'eo_widget_calendar' );
	delete_transient( 'eo_full_calendar_public' );
	delete_transient( 'eo_full_calendar_public_priv' );
	delete_transient( 'eo_full_calendar_admin' );
	delete_transient( 'eo_widget_agenda' );
}

//The following need to trigger the cache
$hooks = array(
	'eventorganiser_save_event', 'eventorganiser_delete_event', 'wp_trash_post','update_option_gmt_offset', /* obvious */
	'update_option_start_of_week', /* Start of week is used for calendars */
	'update_option_rewrite_rules', /* If permalinks updated - links on fullcalendar might now be invalid */
	'delete_option_rewrite_rules',
	'update_option_siteurl',
	'update_option_home',
	'edited_event-category', /* Colours of events may change */
);
foreach( $hooks as $hook ){
	add_action($hook, '_eventorganiser_delete_calendar_cache');
}


/**
 * Handles admin pointers
 *
 * Kick starts the enquing. Rename this to something unique (i.e. include your plugin/theme name).
 *
 *@access private
 *@ignore
 *@since 1.5
 */
function eventorganiser_pointer_load( $hook_suffix ) {

		$screen_id = get_current_screen()->id;

		//Get pointers for this screen
		/**
		 * Filters the user 'pointers' for a specific screen.
		 *
		 * The `$screen_id` part of the hook refers to the screen's ID.
		 *
		 * @param array $pointers Filters to display on this screen.
		 */
		$pointers = apply_filters('eventorganiser_admin_pointers-'.$screen_id, array());

		if( !$pointers || !is_array($pointers) )
			return;

		// Get dismissed pointers
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$valid_pointers =array();

		//Check pointers and remove dismissed ones.
		foreach ($pointers as $pointer_id => $pointer ){

			//Sanity check
			if ( in_array( $pointer_id, $dismissed ) || empty($pointer)  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) )
				continue;

			 $pointer['pointer_id'] = $pointer_id;

			//Add the pointer to $valid_pointers array
			$valid_pointers['pointers'][] =  $pointer;
		}

		//No valid pointers? Stop here.
		if( empty($valid_pointers) )
			return;

		// Add pointers style to queue.
		wp_enqueue_style( 'wp-pointer' );

		// Add pointers script to queue. Add custom script.
		wp_enqueue_script('eventorganiser-pointer',EVENT_ORGANISER_URL.'js/eventorganiser-pointer.js',array('wp-pointer','eo_event'));

		// Add pointer options to script.
		wp_localize_script('eventorganiser-pointer', 'eventorganiserPointer', $valid_pointers);
}
add_action( 'admin_enqueue_scripts', 'eventorganiser_pointer_load',99999);



function _eventorganiser_upgrade_admin_notice() {

	$notice_handler = EO_Admin_Notice_Handler::get_instance();

	$message = sprintf(
		__("<h4>City & State Fields Added</h4>City and state / province fields for venues have now been added. </br> If you'd like, Event Organiser can <a href='%s'>attempt to auto-fill them</a>. You can always manually change the details aftewards.",'eventorganiser'),
		add_query_arg('action','eo-autofillcity',admin_url('admin-post.php'))
	);
	$notice_handler->add_notice( 'autofillvenue17', 'event_page_venues', $message , 'alert');

	$message = __("<h4>The Default Templates Have Changed</h4>Don't panic! If you've set up your own templates in your theme you won't notice any change. </br> If you haven't and want the old templates back, <a href='http://wp-event-organiser.com/blog/new-default-templates-in-1-7'>see this post<a/>.",'eventorganiser');
	$notice_handler->add_notice( 'changedtemplate17', '', $message , 'alert' );

	if ( ! get_option( 'timezone_string' ) && current_user_can( 'manage_options' ) && get_option( 'gmt_offset' ) ) {
		$offset = (float) get_option( 'gmt_offset' );
		$plus   = $offset >= 0 ? '+' : '-';

		if ( floor( abs( $offset ) ) !== abs( $offset ) ) {
			$mins = ( abs( $offset ) - floor( abs( $offset ) ) ) * 60;
			$tzstring  = 'UTC ' . $plus.floor( abs( $offset ) ) . ':' . $mins;
		} else {
			$tzstring  = 'UTC ' . $plus.abs( $offset );
		}

		$message = sprintf(
			"<h4>" . sprintf( esc_html__('Your site timezone is %s','eventorganiser'), "<em>{$tzstring}</em>" ). '</h4>'
			."<p>" . __( "Is this correct? Using a fixed offset from UTC may cause unexpected behaviour, particular if you observe Daylight Savings Time.", 'eventorganiser' ) . '<br>'
					.__( "It is strongly recommended you select a city instead, even if you don't observe Daylight Savings Time.",'eventorganiser') . '</p>'
			."<p>" . __( "You can <a href='%s'>change your timezone settings here</a>.",'eventorganiser') . '</p>',
			admin_url( 'options-general.php' ).'#default_role'
		);
		$notice_handler->add_notice( 'timezone', '', $message , 'alert' );
	}

}
add_action( 'admin_notices', '_eventorganiser_upgrade_admin_notice', 1 );

/**
 * Handles city auto-fill request.
 *
 * Hooked onto admin_post_eo-autofillcity. Triggered when user clicks 'autofill' link.
 * This routine goes through all the venues, reverse geocodes to find their city and
 * autofills the city field (added in 1.7).
 *
 *@ignore
 *@access private
 *@link https://github.com/stephenh1988/Event-Organiser/issues/18
 *@link http://open.mapquestapi.com/nominatim/ Nominatim Search Service
 */
function _eventorganiser_autofill_city(){
	$seen_notices = get_option('eventorganiser_admin_notices',array());

	if( in_array('autofillvenue17', $seen_notices) )
		return;

	EO_Admin_Notice_Handler::dismiss_notice('autofillvenue17');

	$cities =array();
	$venues = eo_get_venues();

	foreach( $venues as $venue ){
		$venue_id = (int) $venue->term_id;
		$latlng =extract(eo_get_venue_latlng($venue_id));

		If( eo_get_venue_meta($venue_id,'_city',true) )
			continue;

		$response=wp_remote_get("http://open.mapquestapi.com/nominatim/v1/reverse?format=json&lat={$lat}&lon={$lng}&osm_type=N&limit=1");
		$geo = json_decode(wp_remote_retrieve_body( $response ));
		if( isset($geo->address->city) ){
			$cities[$venue_id] = $geo->address->city;
			eo_update_venue_meta($venue_id, '_city', $geo->address->city);
		}
		if( isset($geo->address->country_code) && 'gb' == $geo->address->country_code ){
			//For the UK use county not state.
			if( isset($geo->address->county) ){
				$cities[$venue_id] = $geo->address->county;
				eo_update_venue_meta($venue_id, '_state', $geo->address->county);
			}
		}else{
			if( isset($geo->address->state) ){
				$cities[$venue_id] = $geo->address->state;
				eo_update_venue_meta($venue_id, '_state', $geo->address->state);
			}
		}
	}

	wp_safe_redirect(admin_url('edit.php?post_type=event&page=venues'));
}
add_action('admin_post_eo-autofillcity','_eventorganiser_autofill_city');

/**
 * Helper function to clear the cache for number of authors.
 *
 * @private
 */
function _eventorganiser_clear_multi_organiser_cache( $new_status, $old_status, $post ) {
	if( $new_status !== $old_status && 'event' == get_post_type( $post ) ){
		delete_transient( 'eo_is_multi_event_organiser' );
	}
}
add_action('transition_post_status', '_eventorganiser_clear_multi_organiser_cache', 10, 3 );
?>