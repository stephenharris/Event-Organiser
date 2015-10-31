<?php

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//" . get_bloginfo('name') . "//NONSGML Events//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "X-WR-CALNAME:" . get_bloginfo('name') . " - Events\r\n";
echo "X-ORIGINAL-URL:" . get_post_type_archive_link('event') . "\r\n";
echo "X-WR-CALDESC:" . get_bloginfo('name') . " - Events\r\n";

// Loop through events
if ( have_posts() ) :

	$now     = new DateTime();
	$dtstamp = eo_format_date( 'now', 'Ymd\THis\Z' );

	//Set $tz if a timezone is specified - this does not include GMT offsets
	$timezone     = ( get_option( 'timezone_string' ) ? eo_get_blog_timezone() : false );
	$utc_timezone = new DateTimeZone( 'UTC' );

	$earliest_date = false;
	$latest_date   = false;

	while ( have_posts() ) : the_post();

		global $post;

		// If event has no corresponding row in events table then skip it
		if ( !isset($post->event_id) || $post->event_id == -1 )
			continue;

		$start = eo_get_the_start(DATETIMEOBJ);
		$end = eo_get_the_end(DATETIMEOBJ);
		$created_date = get_post_time('Ymd\THis\Z',true);
		$modified_date = get_post_modified_time('Ymd\THis\Z',true);
		$schedule_data = eo_get_event_schedule();

		if ( $timezone ) {
			$earliest_date = $earliest_date ? min( eo_get_schedule_start( DATETIMEOBJ ), $earliest_date ) : eo_get_schedule_start( DATETIMEOBJ );
			$latest_date   = $latest_date ? max( eo_get_schedule_last( DATETIMEOBJ ), $latest_date ) : eo_get_schedule_last( DATETIMEOBJ );
		}

		// Generate Event status
		if ( get_post_status(get_the_ID()) == 'publish' )
			$status = 'CONFIRMED';
		else
			$status = 'TENTATIVE';

		// Output event
		echo "BEGIN:VEVENT\r\n";
		echo "UID:" . eo_get_event_uid() . "\r\n";
		echo "STATUS:" . $status . "\r\n";
		echo "DTSTAMP:" . $dtstamp . "\r\n";
		echo "CREATED:" . $created_date . "\r\n";
		echo "LAST-MODIFIED:" . $modified_date . "\r\n";
		
		if ( eo_is_all_day() ) {
			//All day event
			$end->modify('+1 minute');
			echo "DTSTART;VALUE=DATE:" . $start->format( 'Ymd' ) . "\r\n";
			echo "DTEND;VALUE=DATE:" . $end->format( 'Ymd' ) . "\r\n";
		} elseif ( $timezone ) {
			//Non-all-day event with timezone
			echo "DTSTART;TZID=" . eo_get_blog_timezone()->getName().":" . $start->format( 'Ymd\THis' ) . "\r\n";
			echo "DTEND;TZID=" . eo_get_blog_timezone()->getName().":" . $end->format( 'Ymd\THis' ) . "\r\n";
		} else {
			//Non-all-day event without timezone or with GMT offset
			$start->setTimezone( $utc_timezone );
			$end->setTimezone( $utc_timezone );
			echo "DTSTART:" . $start->format( 'Ymd\THis\Z' ) . "\r\n";
			echo "DTEND:" . $end->format( 'Ymd\THis\Z' ) . "\r\n";			
		}
		
		if ( $recurrence_rule = eventorganiser_generate_ics_rrule() ) :
			echo "RRULE:" . $recurrence_rule . "\r\n";
		endif;
		
		if ( !empty($schedule_data['exclude']) ) :
			$exclude_strings = array();
			foreach ( $schedule_data['exclude'] as $exclude ){
				if ( eo_is_all_day() ){
					$param = ';VALUE=DATE';
					$exclude_strings[] = $exclude->format( 'Ymd' );
				} elseif( $timezone ) {
					$param = ';TZID=' . eo_get_blog_timezone()->getName();
					$exclude_strings[] = $exclude->format( 'Ymd\THis' );
				}else{
					$param = '';
					$exclude->setTimezone( $utc_timezone );
					$exclude_strings[] = $exclude->format( 'Ymd\THis\Z' );
				}
			}
			echo "EXDATE" . $param . ":" . implode(',',$exclude_strings) . "\r\n";
		endif;
		
		if ( !empty($schedule_data['include']) ) :
			$include_strings = array();
			foreach ( $schedule_data['include'] as $include ){
				if ( eo_is_all_day() ){
					$param = ';VALUE=DATE';
					$include_strings[] = $include->format( 'Ymd' );
				} elseif( $timezone ) {
					$param = ';TZID=' . eo_get_blog_timezone()->getName();
					$include_strings[] = $include->format( 'Ymd\THis' );
				}else{
					$param = '';
					$include->setTimezone( $utc_timezone );
					$include_strings[] = $include->format( 'Ymd\THis\Z' );
				}
			}
			echo "RDATE" . $param . ":" . implode(',',$include_strings) . "\r\n";
		endif;
		
		echo eventorganiser_fold_ical_text(  
			'SUMMARY: ' . eventorganiser_escape_ical_text( html_entity_decode( get_the_title_rss(), ENT_COMPAT, 'UTF-8' ) ) 
		) . "\r\n";
		
		$description = wp_strip_all_tags( html_entity_decode( get_the_excerpt(), ENT_COMPAT, 'UTF-8' ) );
		$description = ent2ncr( convert_chars( $description ) );
		/**
	 	* Filters the description of the event as it appears in the iCal feed.
	 	*
	 	* @param string $description The event description
	 	*/
		$description = apply_filters( 'eventorganiser_ical_description', $description );
		$description = eventorganiser_escape_ical_text( $description );
		
		if ( !empty( $description ) ) :
			echo eventorganiser_fold_ical_text( "DESCRIPTION: $description" ) . "\r\n";
		endif;
		
		$description = wpautop( html_entity_decode( get_the_content(), ENT_COMPAT, 'UTF-8' ) );
		$description = str_replace( "\r\n", '', $description ); //Remove new lines
		$description = str_replace( "\n", '', $description );
		$description = eventorganiser_escape_ical_text( $description );
		echo eventorganiser_fold_ical_text( "X-ALT-DESC;FMTTYPE=text/html: $description" ). "\r\n";

		$cats = get_the_terms( get_the_ID(), 'event-category' );
		if ( $cats && !is_wp_error( $cats ) ) :
			$cat_names = wp_list_pluck( $cats, 'name' );
			$cat_names = array_map( 'eventorganiser_escape_ical_text', $cat_names );
			echo 'CATEGORIES:' . implode( ',', $cat_names ) . "\r\n";
		endif;
		
		if ( eo_get_venue() ) :
			$venue = eo_get_venue_name( eo_get_venue() );
			echo "LOCATION:" . eventorganiser_fold_ical_text( eventorganiser_escape_ical_text( $venue ) ) . "\r\n";
			echo "GEO:" . implode( ';', eo_get_venue_latlng( $venue ) ) . "\r\n";
		endif;
		
		if( get_the_author_meta( 'ID' ) ){
			$author_name = eventorganiser_escape_ical_text( get_the_author() );
			$author_email = eventorganiser_escape_ical_text( get_the_author_meta( 'user_email' ) );
			echo eventorganiser_fold_ical_text( 'ORGANIZER;CN="' . $author_name . '":MAILTO:' . $author_email ) . "\r\n";
		}
		
		echo eventorganiser_fold_ical_text( 'URL;VALUE=URI:' . get_permalink() ) . "\r\n";
		
		echo "END:VEVENT\r\n";

	endwhile;

	if ( $timezone ) {
		echo eventorganiser_ical_vtimezone( $timezone, $earliest_date->format( 'U' ), $latest_date->format( 'U' ) ) . "\r\n";
	}

endif;

echo "END:VCALENDAR\r\n";
