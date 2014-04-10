BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//<?php  get_bloginfo('name'); ?>//NONSGML Events //EN
CALSCALE:GREGORIAN
X-WR-CALNAME:<?php echo get_bloginfo('name');?> - Events
X-ORIGINAL-URL:<?php echo get_post_type_archive_link('event') . "\n"; ?>
X-WR-CALDESC:<?php echo get_bloginfo('name');?> - Events
<?php
	// Loop through events
	if ( have_posts() ):
		$now = new DateTime();
		$dtstamp =$now->format('Ymd\THis\Z');
		$UTC_tz = new DateTimeZone('UTC');

		while( have_posts() ): the_post();
			global $post;

			//If event has no corresponding row in events table then skip it
			if(!isset($post->event_id) || $post->event_id==-1)
				continue;

			$start = eo_get_the_start(DATETIMEOBJ);
			$end = eo_get_the_end(DATETIMEOBJ);
			$created_date = get_post_time('Ymd\THis\Z',true);
			$modified_date = get_post_modified_time('Ymd\THis\Z',true);
			$schedule_data = eo_get_event_schedule();

			//Set up start and end date times
			if( eo_is_all_day() ){
				$format =	'Ymd';
				$start_date = $start->format($format);
				$end->modify('+1 minute');
				$end_date = $end->format($format);				
			}else{
				$format =	'Ymd\THis\Z';
				$start->setTimezone($UTC_tz);
				$start_date =$start->format($format);
				$end->setTimezone($UTC_tz);
				$end_date = $end->format($format);
			}

			//Generate Event status
			if( get_post_status(get_the_ID()) == 'publish' )
				$status = 'CONFIRMED';
			else
				$status = 'TENTATIVE';

			//Output event
?>
BEGIN:VEVENT
UID:<?php echo eo_get_event_uid();?>

STATUS:<?php echo $status;?>

DTSTAMP:<?php echo $dtstamp;?>

CREATED:<?php echo $created_date;?>

LAST-MODIFIED:<?php echo $modified_date;?>

<?php if( eo_is_all_day() ): ?>
DTSTART;VALUE=DATE:<?php echo $start_date ; ?>

DTEND;VALUE=DATE:<?php echo $end_date; ?>
<?php else: ?>
DTSTART:<?php echo $start_date ; ?>

DTEND:<?php echo $end_date; ?>
<?php endif;?>

<?php if ( $reoccurrence_rule = eventorganiser_generate_ics_rrule() ):?>
RRULE:<?php echo $reoccurrence_rule;?>

<?php endif;?>
<?php if( !empty($schedule_data['exclude']) ):
	$exclude_strings = array();
	foreach ( $schedule_data['exclude'] as $exclude ){
		if( !eo_is_all_day() ){
			$vdate='';
			$exclude->setTimezone($UTC_tz);
			$exclude_strings[] = $exclude->format('Ymd\THis\Z');
		}else{
			$vdate=';VALUE=DATE';
			$exclude_strings[] = $exclude->format('Ymd');
		}
	}?>
EXDATE<?php echo $vdate;?>:<?php echo implode(',',$exclude_strings);?>

<?php endif;?>
<?php if( !empty($schedule_data['include']) ):
	$include_strings = array();
		foreach ( $schedule_data['include'] as $include ){
			if( !eo_is_all_day() ){
				$vdate='';
				$include->setTimezone($UTC_tz);
				$include_strings[] = $include->format('Ymd\THis\Z');
			}else{
				$vdate=';VALUE=DATE';
				$include_strings[] = $include->format('Ymd');
			}
	}?>
RDATE<?php echo $vdate;?>:<?php echo implode(',',$include_strings);?>

<?php endif; ?>
<?php echo eventorganiser_fold_ical_text( html_entity_decode( "SUMMARY: " . eventorganiser_escape_ical_text( get_the_title_rss() ) ) ) . "\n" ;?>
<?php
	$excerpt = get_the_excerpt();
	$excerpt = eventorganiser_escape_ical_text( apply_filters('the_excerpt_rss', $excerpt) );
	if( !empty($excerpt) ):
		echo eventorganiser_fold_ical_text( html_entity_decode( "DESCRIPTION: $excerpt" ) ) . "\n";
	endif; ?>
<?php 
	$description = eventorganiser_escape_ical_text( get_the_content() );
	echo eventorganiser_fold_ical_text( html_entity_decode( "X-ALT-DESC;FMTTYPE=text/html: $description" ) ) . "\n";
?>
<?php 
	$cats = get_the_terms( get_the_ID(), 'event-category' );
if( $cats && !is_wp_error($cats) ):
	$cat_names = wp_list_pluck($cats, 'name');
	$cat_names = array_map( 'eventorganiser_escape_ical_text', $cat_names ); ?>
CATEGORIES:<?php echo implode(',',$cat_names); ?>

<?php endif; ?>
<?php
if( eo_get_venue() ): 
	$venue = eo_get_venue_name( eo_get_venue() );
?>
LOCATION:<?php echo eventorganiser_fold_ical_text( eventorganiser_escape_ical_text( $venue ) ) . "\n";?>
GEO:<?php echo implode( ';', eo_get_venue_latlng( $venue ) ) . "\n";?>
<?php endif; ?>
ORGANIZER:<?php echo eventorganiser_fold_ical_text( eventorganiser_escape_ical_text( get_the_author() ) );?>

END:VEVENT
<?php
		endwhile;

	endif;
?>
END:VCALENDAR