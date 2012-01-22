<?php

 /**
 * Register jQuery scripts and CSS files
 *
 * @since 1.0.0
 */
add_action('init', 'eventorganiser_register_script');
function eventorganiser_register_script() {
	wp_register_script( 'eo_front', EVENT_ORGANISER_URL.'js/frontend.js',array('jquery'),'1.1.1',true);
	wp_localize_script( 'eo_front', 'EOAjaxUrl', admin_url( 'admin-ajax.php' ));
	wp_register_style('eo_calendar-style',EVENT_ORGANISER_URL.'css/fullcalendar.css',array(),'1.1.1');
	//Beta
	wp_register_style('eventorganiser-style',EVENT_ORGANISER_URL.'css/eventorganiser-admin-style.css');
}   

 /**
 *Register jQuery scripts and CSS files for admin
 *
 * @since 1.0.0
 */
add_action('admin_enqueue_scripts', 'eventorganiser_register_scripts');
function eventorganiser_register_scripts(){
	wp_register_script( 'eo_GoogleMap', 'http://maps.googleapis.com/maps/api/js?sensor=true');

	wp_register_script( 'eo_venue', EVENT_ORGANISER_URL.'js/venues.js',array(
		'jquery',
		'eo_GoogleMap'
	),'1.1',true);
	
	wp_register_script( 'eo_event', EVENT_ORGANISER_URL.'js/event.js',array(
		'jquery',
		'jquery-ui-datepicker',
		'jquery-ui-autocomplete',
		'jquery-ui-widget',
		'jquery-ui-position'
	),'1.1',true);	

	//Calendar View
	wp_register_script( 'eo_calendar', EVENT_ORGANISER_URL.'js/fullcalendar.js',array(
		'jquery',
		'jquery-ui-core',
		'jquery-ui-widget',
		'jquery-ui-button',
		'jquery-ui-position'),
		'1.1',true);	

	wp_register_style('eventorganiser-style',EVENT_ORGANISER_URL.'css/eventorganiser-admin-style.css');
}

 /**
 * Check the export and event creation (from Calendar view) actions. 
 * These cannot be called later. Most other actions are only called when
 * the appropriate page is loading.
 *
 * @since 1.0.0
 */
add_action('admin_init','eventorganiser_admin_init',0);
function eventorganiser_admin_init(){
	global $EO_Errors;
	$EO_Errors = new WP_Error();
}
add_action('admin_init', array('Event_Organiser_Im_Export', 'get_object'));
add_action('admin_init','eventorganiser_cal_action');

add_action('init','eventorganiser_public_export');
function eventorganiser_public_export(){
	add_feed('eo-events', 'Event_Organiser_Im_Export::get_object');

	if(!isset($_REQUEST['action']) || $_REQUEST['action']!='eventorganiser_calendar_scrape' )
		return;

	require_once("class-event-organiser-im-export.php");
	Event_Organiser_Im_Export::get_object();
}


/**
 * Adds venue, calendar and settings pages to admin
 *
 * @since 1.0.0
 */
add_action('admin_menu', 'eventorganiser_admin_pages');
function eventorganiser_admin_pages() {

	//Add pages
	$calendar_page = add_submenu_page('edit.php?post_type=event', __('Calendar View','eventorganiser'), __('Calendar View','eventorganiser'), 'edit_events', 'calendar', 'eventorganiser_calendar_page');
	$venue_page = add_submenu_page('edit.php?post_type=event',  __('Venues','eventorganiser'),  __('Venues','eventorganiser'), 'manage_venues', 'venues', 'eventorganiser_venues_page');
	$settings_page = add_submenu_page('options-general.php',  __('Event Organiser Settings','eventorganiser'), 'Event Organiser', 'manage_options', 'event-settings', 'eventorganiser_options_page'); 

	//Register actions on venue and settings page
	add_action('admin_print_styles-' . $venue_page, 'eventorganiser_venues_action',9);
	add_action('admin_print_styles-' . $settings_page, 'eventorganiser_update_settings',9);

	//Add styles and scripts
	add_action('admin_print_styles-' . $venue_page, 'eventorganiser_venue_page_admin_styles',10);
	add_action('admin_print_styles-' . $calendar_page, 'eventorganiser_calendar_page_admin_styles',10);
}


/**
 * Queues up the javascript / style scripts for venue page
 *
 * @since 1.0.0
 */
function eventorganiser_venue_page_admin_styles() {
	if(isset($_REQUEST['action']) && ($_REQUEST['action']=='create'||$_REQUEST['action']=='edit'||$_REQUEST['action']=='add' || $_REQUEST['action']=='update' )):
		wp_enqueue_script('eo_venue');
		wp_enqueue_style('eventorganiser-style');
		wp_enqueue_script('post');
		wp_enqueue_script('media-upload');
		add_thickbox();	
	endif;
}


/**
 * Queues up the javascript / style scripts for calendar page
 *
 * @since 1.0.0
 */
function eventorganiser_calendar_page_admin_styles(){
	$eo_settings_array= get_option('eventorganiser_options'); 
	$EO_Venues = new EO_Venues;
	$EO_Venues->query();
	 add_thickbox();
	global $wp_locale;

	$terms =get_terms( 'event-category', array('hide_empty' => 0));
	foreach($terms as $term):
		$term_meta = get_option( "eo-event-category_$term->term_id");
		$colour = (isset($term_meta['colour']) ? $term_meta['colour'] : '');
		$term->colour = $colour;
	endforeach;

	wp_enqueue_script("eo_calendar",true);
	wp_enqueue_script("eo_event",true);
	wp_localize_script( 'eo_event', 'EO_Ajax_Event', array( 
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'startday'=>intval(get_option('start_of_week')),
		'format'=> $eo_settings_array['dateformat'].'-yy'
		));
	wp_localize_script( 'eo_calendar', 'EO_Ajax', array( 
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'startday'=>intval(get_option('start_of_week')),
		'format'=> $eo_settings_array['dateformat'].'-yy',
		'perm_edit'=> current_user_can('edit_events'),
		'categories'=>$terms,
		'venues'=>$EO_Venues->results,		
		'locale'=>array(
			'monthNames'=>array_values($wp_locale->month),
			'monthAbbrev'=>array_values($wp_locale->month_abbrev),
			'dayNames'=>array_values($wp_locale->weekday),
			'dayAbbrev'=>array_values($wp_locale->weekday_abbrev),
			'today'=>__('today','eventorganiser'),
			'day'=>__('day','eventorganiser'),
			'week'=>__('week','eventorganiser'),
			'month'=>__('month','eventorganiser'),
			'gotodate'=>__('go to date','eventorganiser'),
			'cat'=>__('View all categories','eventorganiser'),
			'venue'=>__('View all venues','eventorganiser'),
			)
		));
	wp_enqueue_style('eo_calendar-style');
	wp_enqueue_style('eventorganiser-style');
}


/**
 * Queues up the javascript / style scripts for Events custom page type 
 *
 * @since 1.0.0
 */
add_action( 'admin_enqueue_scripts', 'add_admin_scripts', 10, 1 );
function add_admin_scripts( $hook ) {
	global $post,$current_screen,$wp_locale;

	if ( $hook == 'post-new.php' || $hook == 'post.php') {
		if( $post->post_type == 'event' ) {     
			$eo_settings_array= get_option('eventorganiser_options'); 

			wp_enqueue_script('eo_event');
			wp_localize_script( 'eo_event', 'EO_Ajax_Event', array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'startday'=>intval(get_option('start_of_week')),
					'format'=> $eo_settings_array['dateformat'].'-yy',
					'locale'=>array(
						'monthNames'=>array_values($wp_locale->month),
						'monthAbbrev'=>array_values($wp_locale->month_abbrev),
						'dayAbbrev'=>array_values($wp_locale->weekday_abbrev),
						'weekDay'=>$wp_locale->weekday,
						'hour'=>__('Hour','eventorganiser'),
						'minute'=>__('Minute','eventorganiser'),
						'day'=>__('day','eventorganiser'),
						'days'=>__('days','eventorganiser'),
						'week'=>__('week','eventorganiser'),
						'weeks'=>__('weeks','eventorganiser'),
						'month'=>__('month','eventorganiser'),
						'months'=>__('months','eventorganiser'),
						'year'=>__('year','eventorganiser'),
						'years'=>__('years','eventorganiser'),
						'daySingle'=>__('every day','eventorganiser'),
						'dayPlural'=>__('every %d days','eventorganiser'),
						'weekSingle'=>__('every week on','eventorganiser'),
						'weekPlural'=>__('every %d weeks on','eventorganiser'),
						'monthSingle'=>__('every month on the','eventorganiser'),
						'monthPlural'=>__('every %d months on the','eventorganiser'),
						'yearSingle'=>__('every year','eventorganiser'),
						'yearPlural'=>__('every %d years','eventorganiser'),
						'summary'=>__('This event will repeat','eventorganiser'),
						'until'=>__('until','eventorganiser'),
						'occurrence'=>array(__('first','eventorganiser'),__('second','eventorganiser'),__('third','eventorganiser'),__('fourth','eventorganiser'),__('last','eventorganiser'))
					)
					));
			wp_enqueue_script('eo_venue');
			wp_enqueue_style('eventorganiser-style');
		}
	}elseif($current_screen->id=='edit-event'){
			wp_enqueue_style('eventorganiser-style');
	}
}

/**
 * Notices
 * Display error mesages or other notices on venue and settings pages
 *
 * @since 1.0.0
 */
add_action('admin_notices', 'eo_admin_notices',0);
function eo_admin_notices(){
	global $EO_Errors,$eventorganiser_events_table,$eventorganiser_venue_table,$wpdb;

	//Check tables exist
	$table_errors = array();
	if($wpdb->get_var("show tables like '$eventorganiser_events_table'") != $eventorganiser_events_table):
		$table_errors[]=$eventorganiser_events_table;
	endif;
	if($wpdb->get_var("show tables like '$eventorganiser_venue_table'") != $eventorganiser_venue_table):
		$table_errors[]=$eventorganiser_venue_table;
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

	//Check PHP version
	if (version_compare(PHP_VERSION, '5.3.0') < 0):?>
		<div class="error"	>
			<p>Event Organiser requires <strong>PHP 5.3</strong> to function properly. Your version is <?php echo PHP_VERSION; ?>. </p>
		</div>
	<?php endif;

	//Check WordPress version
	if(get_bloginfo('version')<'3.3'):?>
		<div class="error"	>
			<p>Event Organiser requires <strong>WordPress 3.3</strong> to function properly. Your version is <?php echo get_bloginfo('version'); ?>. </p>
		</div>
	<?php endif; 

	$errors=array();
	$notices=array();
	if(isset($EO_Errors)):
		$errors = $EO_Errors->get_error_messages('eo_error');
		$notices= $EO_Errors->get_error_messages('eo_notice');
		if(!empty($errors)):?>
			<div class="error"	>
			<?php foreach ($errors as $error):?>
				<p><?php	echo $error;?></p>
			<?php endforeach;?>
			</div>
		<?php endif;?>
		<?php if(!empty($notices)):?>
			<div class="updated">
			<?php foreach ($notices as $notice):?>
				<p><?php	echo $notice;?></p>
			<?php endforeach;?>
			</div>
		<?php	endif;
	endif;
}


/*
Cron jobs
*/

function eventorganiser_cron_jobs(){
	wp_schedule_event(time()+60, 'daily', 'eventorganiser_delete_expired');
}
//add_action('init','eo_my_delete_expired');
add_action('eventorganiser_delete_expired', 'eo_my_delete_expired');
function eo_my_delete_expired(){
	$events = eo_get_events(array('showrepeats'=>0,'showpastevents'=>1,'eo_interval'=>'expired'));

	if($events):
		foreach($events as $event):
			$now = new DateTime('now', EO_Event::get_timezone());
			$start = new DateTime($event->StartDate.' '.$event->StartTime, EO_Event::get_timezone());
			$end = new DateTime($event->EndDate.' '.$event->FinishTime, EO_Event::get_timezone());
			$duration = date_diff($start,$end);
			$finished =  new DateTime($event->reoccurrence_end.' '.$event->StartTime, EO_Event::get_timezone());
			$finished->add($duration);
			$finished->modify('+1 day');

			if($finished <= $now):
				wp_trash_post((int) $event->ID);
			endif;
		endforeach;
	endif;
}

function eventorganiser_clear_cron_jobs(){
	wp_clear_scheduled_hook('eventorganiser_delete_expired');
}
?>
