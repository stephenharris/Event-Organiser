<?php

class iCalGenerateFeedTest extends EO_UnitTestCase
{

	public function setUp() {
		add_filter( 'eventorganiser_format_datetime_string', array( $this, 'groundhogDay' ), 10, 4 );
		parent::setUp();
	}


	public function tearDown() {
		remove_filter( 'eventorganiser_format_datetime_string', array( $this, 'groundhogDay' ) );
		parent::tearDown();
	}

	public function groundhogDay( $formatted_datetime, $datetime_string, $format ) {
		if ( 'now' == $datetime_string ) {
			//Hardcode 'now' for unit tests.
			$datetime           = new DateTime( '1993-02-02 11:00:00', eo_get_blog_timezone() );
			$formatted_datetime = $datetime->format( $format );
		}
		return $formatted_datetime;
	}


	public function testRRULEWeeklySchedule(){
		$event_id = $this->factory->event->create( array(
				'start'         => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
				'end'           => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
				'schedule_last' => new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
				'frequency'     => 1,
				'all_day'       => 0,
				'schedule'      => 'weekly',
				'schedule_meta' => array( 'MO', 'WE', 'TH' ),
				'post_title'    => 'The Event Title',
		) );

		$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE,TH;UNTIL=20131230T210000Z", eventorganiser_generate_ics_rrule( $event_id ) );
	}

	public function testRRULEMonthlyByDaySchedule(){
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2016-11-25 04:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2016-11-25 09:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2017-09-22 05:00', eo_get_blog_timezone() ),
			'frequency'     => 2,
			'all_day'       => 0,
			'schedule'      => 'monthly',
			'schedule_meta' => 'BYDAY=4FR',
			'post_title'    => 'The Event Title',
		) );

		$this->assertEquals( "FREQ=MONTHLY;INTERVAL=2;BYDAY=4FR;UNTIL=20170922T040000Z", eventorganiser_generate_ics_rrule( $event_id ) );
	}

	public function testRRULEMonthlyByMonthDaySchedule(){
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2016-05-13 09:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2016-05-13 13:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2017-02-13 09:00', eo_get_blog_timezone() ),
			'frequency'     => 3,
			'all_day'       => 0,
			'schedule'      => 'monthly',
			'schedule_meta' => 'BYMONTHDAY=13',
			'post_title'    => 'The Event Title',
		) );

		$this->assertEquals( "FREQ=MONTHLY;INTERVAL=3;BYMONTHDAY=13;UNTIL=20170213T090000Z", eventorganiser_generate_ics_rrule( $event_id ) );
	}

	public function testRRULEYearlySchedule(){
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2016-02-21 09:45', eo_get_blog_timezone() ),
			'end'           => new DateTime('2016-02-21 13:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2020-02-21 09:45', eo_get_blog_timezone() ),
			'schedule'      => 'yearly',
			'post_title'    => 'The Event Title',
		) );

		$this->assertEquals( "FREQ=YEARLY;INTERVAL=1;UNTIL=20200221T094500Z", eventorganiser_generate_ics_rrule( $event_id ) );
	}

	public function testRRULEOnceSchedule(){
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'post_title'    => 'The Event Title',
		) );

		$this->assertEquals( false, eventorganiser_generate_ics_rrule( $event_id ) );
	}

	public function testRRULECustomSchedule(){
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule'      => 'custom',
			'include'       => array ( new DateTime('2014-01-17 21:30', eo_get_blog_timezone() ) ),
			'post_title'    => 'The Event Title',
		) );

		$this->assertEquals( false, eventorganiser_generate_ics_rrule( $event_id ) );
	}


	public function testOrganizer(){

		$event_id = $this->factory->event->create( array(
			'start' => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'  => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule_last'=> new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
			'frequency' => 1,
			'all_day' => 0,
			'schedule'=>'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'=>'The Event Title',
			'post_content'=>'My event content',
			'post_excerpt'=>'My event excerpt',
			'post_author' => 1,
			'post_status' => 'publish',
			'post_date'   => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/organizer.ical' );

		$this->assertEquals( $expected, $actual );
	}

	public function testOrganizerWithSpecialCharacters(){

		$user_id = $this->factory->user->create( array(
			'user_login'   => 'specialcharacteruser',
			'user_pass'    => 'password1',
			'user_email'   => 'specialcharacter@example.org',
			'display_name' => 's;p,e"c\'a:l'
		) );

		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'all_day'       => 0,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'    => 'The Event Title',
			'post_content'  => 'My event content',
			'post_excerpt'  => 'My event excerpt',
			'post_author'   => $user_id,
			'post_status'   => 'publish',
			'post_date'     => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/organizer-special-characters.ical' );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * This test expects the summary to be correctly wrapped in p-tags.
	 * Prior to WordPress 4.7.0 there was a bug which resulted in malformed HTML (unclosed <p> tag).
	 * @requires WordPress >= 4.7-alpha
	 * @link https://core.trac.wordpress.org/ticket/4857
	 */
	public function testSummary(){

		$event_id = $this->factory->event->create( array(
			'start'        => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'          => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'post_title'   => 'The Event Title',
			'post_content' => "This is a <strong>bold line.</strong> \n\n This is a <span style='text-decoration: underline'> underlined line </span>"
								. "<span style='color: #ff0000'>This is red.</span>\n"
								. "<em>This is a new line in italics</em>"
								. "<p style='color:#0000ff;text-align:right;'>Aligned right and blue</p>",
			'post_excerpt' => false,
			'post_status'  => 'publish',
			'post_date'    => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/description.ical' );

		$this->assertEquals( $expected, $actual );
	}

	public function testEscapedCharacters(){

		$event_id = $this->factory->event->create( array(
			'start'        => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'          => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'post_title'   => 'The; Event: Title, contains some \\\n characters which need escaping',
			'post_content' => 'The content contains semi colon; and colons: which are fine. A comma, and new line \\\n which is not a new line And then \\\ a backslash.',
			'post_excerpt' => false,
			'post_status'  => 'publish',
			'post_date'    => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/escaped.ical' );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * If the excerpt contains HTML entities these should be encoded,
	 * @see http://wp-event-organiser.com/forums/topic/ical-feed-and-html-encoding/
	 */
	public function testHTMLEntities(){

		$event_id = $this->factory->event->create( array(
			'start'        => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'          => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'post_title'   => 'A quotation mark &#8216; and an ellipses &#8230;',
			'post_content' => 'A quotation mark &#8216; and an ellipses &#8230;',
			'post_excerpt' => false,
			'post_status'  => 'publish',
			'post_date'    => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/htmlentities.ical' );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * If the excerpt contains ampersands these should not be encoded.
	 */
	public function testAmpersand(){

		$event_id = $this->factory->event->create( array(
			'start'        => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'          => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'post_title'   => 'Testing ampersands & &amp; &#038; and &#38;',
			'post_content' => 'Testing ampersands & &amp; &#038; and &#38;',
			'post_excerpt' => false,
			'post_status'  => 'publish',
			'post_date'    => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/ampersands.ical' );

		$this->assertEquals( $expected, $actual );
	}


	public function testRRULEAllDayWeekly(){
		global $wpdb;
		$wpdb->db_connect();

		wp_cache_set( 'eventorganiser_timezone', 'America/New_York' );

		//Event recurrs every Monday evening in New York but is all day, so day should remain on Monday in UTC
    	$event_id = eo_insert_event( array(
			'start'         => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'all_day'       => 1,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'    => 'The Event Title',
			'post_content'  => 'My event content',
    		'post_date'     => '2015-02-18 17:30:00',
		) );

		$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;UNTIL=20131231T020000Z", eventorganiser_generate_ics_rrule( $event_id ) );

		wp_cache_delete( 'eventorganiser_timezone' );

	}

	/**
	 * Since 3.0.0 https://github.com/stephenharris/Event-Organiser/issues/293 Event Organiser tries to keep the
	 * the start/end dates in the appropriate timezone. The by-day (and other) properties should be be in the timezone
	 * of the start date.
	 *
	 * If we are using a recognisable timezone, we should **not** be converting the BYDAY property. The counterpart to
	 * this is when using a UTC offset, when we do:
	 *
	 * @see https://github.com/stephenharris/Event-Organiser/issues/376
	 * @see testRRULEOffsetTimezone
	 */
	public function testRRULEForeignTimezone(){

		$original_timezone = get_option( 'timezone_string' );
		$original_offset   = get_option( 'gmt_offset' );
		update_option( 'timezone_string', 'America/New_York' );
		update_option( 'gmt_offset', '' );
		wp_cache_delete( 'eventorganiser_timezone' );

		//Event recurs every Monday evening in New York (event recurs every Tuesday in UTC)
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime( '2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'           => new DateTime( '2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime( '2013-12-30 21:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'all_day'       => 0,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'    => 'The Event Title',
			'post_content'  => 'My event content',
		) );

		//BYDAY property should be in New York timezone (i.e. Monday );
		$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;UNTIL=20131231T020000Z", eventorganiser_generate_ics_rrule( $event_id ) );


		//Now try it the other direction....
		update_option( 'timezone_string', 'Australia/Perth' );
		update_option( 'gmt_offset', '' );
		wp_cache_delete( 'eventorganiser_timezone' );

		//Event recurs every Thursday morning in Perth, Australia (event recurs every Wednesday in UTC)
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime( '2015-06-25 07:30:00', eo_get_blog_timezone() ),
			'end'           => new DateTime( '2015-06-25 08:30:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime( '2016-12-29 07:30:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'all_day'       => 0,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'TH' ),
			'post_title'    => 'The Event Title',
			'post_content'  => 'My event content',
		) );

		//BYDAY property should be in Austrial/Perth timezone (i.e. Thursday );
		$this->assertEquals( 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20161228T233000Z', eventorganiser_generate_ics_rrule( $event_id ) );


		update_option( 'timezone_string', $original_timezone );
		update_option( 'gmt_offset', $original_offset );
		wp_cache_delete( 'eventorganiser_timezone' );
	}


	/**
	 * Since 3.0.0 https://github.com/stephenharris/Event-Organiser/issues/293 Event Organiser tries to keep the
	 * the start/end dates in the appropriate timezone. But if an off-set timezone is selected it will convert the
	 * datetimes to UTC. The by-day (and other) properties should be in the timezone of the start date, and so also
	 * converted
	 *
	 * The counterpart to this is when we are using a recognisable timezone, if so we should **not** be converting the
	 * BYDAY property.
	 *
	 * @see https://github.com/stephenharris/Event-Organiser/issues/376
	 * @see testRRULEForeignTimezone
	 */
	public function testRRULEOffsetTimezone(){

		$original_timezone = get_option( 'timezone_string' );
		$original_offset   = get_option( 'gmt_offset' );
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 'UTC-4' );
		wp_cache_delete( 'eventorganiser_timezone' );

		//Event recurrs every Monday evening in UTC-4 (event recurs every Tuesday in UTC)
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'all_day'       => 0,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'    => 'The Event Title',
			'post_content'  => 'My event content',
		) );

		$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=TU;UNTIL=20131231T010000Z", eventorganiser_generate_ics_rrule( $event_id ) );


		//Now try it the other direction....
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 'UTC+3' );
		wp_cache_delete( 'eventorganiser_timezone' );

		//Event recurrs every Monday morning in UTC+3 (event recurs very Sunday in UTC)
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2013-12-02 01:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2013-12-02 02:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2013-12-30 01:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'all_day'       => 0,
			'schedule'      =>'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'    =>'The Event Title',
			'post_content'  =>'My event content',
		) );

		$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=SU;UNTIL=20131229T220000Z", eventorganiser_generate_ics_rrule( $event_id ) );


		update_option( 'timezone_string', $original_timezone );
		update_option( 'gmt_offset', $original_offset );
		wp_cache_delete( 'eventorganiser_timezone' );
	}

	public function testIcalFolding(){

		$str_75 = "ABCDEFGHIJKLMNOPQRSTUVWXYZ012346789abcdefghijklmnopqrstuvwxyz012346789'%CDE";

		$string = $str_75.$str_75;

		//var_dump( eventorganiser_fold_ical_text( $string ) );
		$this->assertEquals( "$str_75\r\n $str_75", eventorganiser_fold_ical_text( $string ) );


	}

	public function testMultibyteFolding(){

		$str_75 = "This string ends in 6 multibyte characters. It has a mb_strlen of 75 žšč£££";
		//but strlen of 81

		//We expect no folding
		$this->assertEquals( $str_75, eventorganiser_fold_ical_text( $str_75 ) );


	}

	public function testFoldingWithNewLines(){

		$str_75 = "ABCDEFGHIJKLMNOPQRSTUVWXYZ012346789abcdefghijklmnopqrstuvwxyz012346789^%CDE";


		//New lines with \r\n
		$string = 'ABCDEF\n abcdef\n'. $str_75.$str_75.'\nHello world';
		$expected = 'ABCDEF\n abcdef\nABCDEFGHIJKLMNOPQRSTUVWXYZ012346789abcdefghijklmnopqrstuvw'
			."\r\n " . 'xyz012346789^%CDEABCDEFGHIJKLMNOPQRSTUVWXYZ012346789abcdefghijklmnopqrstuvw'
			."\r\n " . 'xyz012346789^%CDE\nHello world';

		$this->assertEquals( $expected, eventorganiser_fold_ical_text( $string ) );

	}

	public function testIcalNewLineEscape(){

		//Test with \n
		$intentional_newline = "Foo\nBar";
		$this->assertEquals( 'Foo\nBar', eventorganiser_escape_ical_text( $intentional_newline ) );

		//Test with \r\n
		$intentional_newline = "Foo\r\nBar";
		$this->assertEquals( 'Foo\nBar', eventorganiser_escape_ical_text( $intentional_newline ) );

	}

	public function testIcalEscape(){

		$escape   = 'Backslash \ Semicolon ; Colon : Comma ,';
		$expected = 'Backslash \\\ Semicolon \; Colon : Comma \,'; //colons are safe!
		$this->assertEquals( $expected, eventorganiser_escape_ical_text( $escape ) );

	}

	/**
	 * If the site has a non-offset timezone, then this should displayed in the dstart/dtend and includes/excludes
	 *
	 * VTIMEZONEs are skipped on php5.2 because of performance concerns
	 * @requires PHP 5.3.0
	 */
	public function testEventTimezone(){

		if ( defined( 'HHVM_VERSION' ) ) {
			$this->markTestSkipped(
				'This test is skipped on HHVM because of timezone definition issues'
			);
		}

		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'Europe/Zurich' );
		wp_cache_delete( 'eventorganiser_timezone' );

		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2015-09-05 17:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2015-09-05 18:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2015-09-26 17:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'SA' ),
			'post_title'    => 'Event with timezone',
			'post_content'  => 'Event with timezone content',
			'post_excerpt'  => 'Event with timezone excerpt',
			'include'      => array(
				new DateTime('2015-09-22 17:00', eo_get_blog_timezone() ),
				new DateTime('2015-09-23 17:00', eo_get_blog_timezone() )
			),
			'exclude'      => array(
				new DateTime('2015-09-19 17:00', eo_get_blog_timezone() )
			),
			'post_date'     => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/event-with-timezone.ical' );

		update_option( 'timezone_string', $original_timezone );
		wp_cache_delete( 'eventorganiser_timezone' );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * If the site has an offset timezone, then the event dates should be converted to UTC
	 * @group timezone-flakey
	 */
	public function testEventWithOffsetTimezone(){

		$original_timezone = get_option( 'timezone_string' );
		$original_offset   = get_option( 'gmt_offset' );
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', -1 );
		wp_cache_delete( 'eventorganiser_timezone' );

		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2015-11-05 17:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2015-11-05 18:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2015-11-26 17:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'TH' ),
			'post_title'    => 'Event with offset timezone',
			'post_content'  => 'Post content',
			'post_excerpt'  => 'Post excerpt',
			'include'      => array(
				new DateTime('2015-11-22 17:00', eo_get_blog_timezone() ),
				new DateTime('2015-11-23 17:00', eo_get_blog_timezone() )
			),
			'exclude'      => array(
				new DateTime('2015-11-19 17:00', eo_get_blog_timezone() )
			),
			'post_date'     => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/event-with-offset.ical' );

		update_option( 'timezone_string', $original_timezone );
		update_option( 'gmt_offset', $original_offset );
		wp_cache_delete( 'eventorganiser_timezone' );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * VTIMEZONEs are skipped on php5.2 because of performance concerns
	 * @requires PHP 5.3.0
	 */
	public function testEventWithThumbnail(){

		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2016-03-12 00:40', eo_get_blog_timezone() ),
			'end'           => new DateTime('2016-03-12 01:00', eo_get_blog_timezone() ),
			'all_day'       => 0,
			'frequency'     => 1,
			'post_title'    => 'Event with thumbnail',
			'post_content'  => 'Event content',
			'post_excerpt'  => 'Event excerpt',
			'post_date'     => '2016-01-01 17:30:00',
		) );
		//Get it a predictable UID
		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		//Don't use year/month folders to make uploads directory a known constant
		add_filter ( 'pre_option_uploads_use_yearmonth_folders', '__return_null' );
		wp_upload_dir( null, false, true );//clear cache
		$file = EO_DIR_TESTDATA . '/images/cirali.jpg';
		$attachment_id = $this->create_upload_object( $file, $event_id );
		set_post_thumbnail( $event_id, $attachment_id );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/event-with-thumbnail.ical' );

		$this->assertEquals( $expected, $actual );

		//Delete the event first to ensure that we can fullyl remove the attachment
		wp_delete_post( $event_id, true );
		wp_delete_attachment( $attachment_id, true );
		remove_filter ( 'pre_option_uploads_use_yearmonth_folders', '__return_null' );

	}


	/**
	 * If the event is an all-day event then no timezone information should be present
	 * in the dtstart, dtend, exdate or rdate values.
	 *
	 * VTIMEZONEs are skipped on php5.2 because of performance concerns
	 *
	 * @requires PHP 5.3.0
	 */
	public function testAllDayEvent(){

		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'Europe/Zurich' );
		wp_cache_delete( 'eventorganiser_timezone' );

		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2015-11-05 00:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2015-11-05 23:59', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2015-11-26 00:00', eo_get_blog_timezone() ),
			'all_day'       => 1,
			'frequency'     => 1,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'TH' ),
			'post_title'    => 'All day event',
			'post_content'  => 'Post content',
			'post_excerpt'  => 'Post excerpt',
			'include'      => array(
				new DateTime('2015-11-22 00:00', eo_get_blog_timezone() ),
				new DateTime('2015-11-23 00:00', eo_get_blog_timezone() )
			),
			'exclude'      => array(
				new DateTime('2015-11-19 00:00', eo_get_blog_timezone() )
			),
			'post_date'     => '2015-02-18 17:30:00',
		) );

		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );

		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/all-day-event.ical' );

		update_option( 'timezone_string', $original_timezone );
		wp_cache_delete( 'eventorganiser_timezone' );

		$this->assertEquals( $expected, $actual );

	}

	public function testVTimezone() {
		$timezone = new DateTimeZone( 'Europe/London' );
		$time1 = strtotime( '2015-01-01' );
		$time2 = strtotime( '2015-12-31' );

		$expected = "BEGIN:VTIMEZONE\r
TZID:Europe/London\r
BEGIN:STANDARD\r
TZOFFSETFROM:+0100\r
TZOFFSETTO:+0000\r
DTSTART:20141026T010000\r
TZNAME:GMT\r
END:STANDARD\r
BEGIN:DAYLIGHT\r
TZOFFSETFROM:+0000\r
TZOFFSETTO:+0100\r
DTSTART:20150329T010000\r
TZNAME:BST\r
END:DAYLIGHT\r
BEGIN:STANDARD\r
TZOFFSETFROM:+0100\r
TZOFFSETTO:+0000\r
DTSTART:20151025T010000\r
TZNAME:GMT\r
END:STANDARD\r
END:VTIMEZONE";

		$actual = eventorganiser_ical_vtimezone( $timezone, $time1, $time2 );

		//VTIMEZONEs are skipped on php5.2 because of performance concerns
		if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
			$this->assertEquals( '', $actual );
		} else {
			$this->assertEquals( $expected, $actual );
		}

	}


	public function testVTimezoneNegativeOffset() {
		$timezone = new DateTimeZone( 'America/New_York' );
		$time1 = strtotime( '2015-01-01' );
		$time2 = strtotime( '2015-12-31' );

		$expected = "BEGIN:VTIMEZONE\r
TZID:America/New_York\r
BEGIN:STANDARD\r
TZOFFSETFROM:-0400\r
TZOFFSETTO:-0500\r
DTSTART:20141102T060000\r
TZNAME:EST\r
END:STANDARD\r
BEGIN:DAYLIGHT\r
TZOFFSETFROM:-0500\r
TZOFFSETTO:-0400\r
DTSTART:20150308T070000\r
TZNAME:EDT\r
END:DAYLIGHT\r
BEGIN:STANDARD\r
TZOFFSETFROM:-0400\r
TZOFFSETTO:-0500\r
DTSTART:20151101T060000\r
TZNAME:EST\r
END:STANDARD\r
END:VTIMEZONE";

		$actual = eventorganiser_ical_vtimezone( $timezone, $time1, $time2 );

		//VTIMEZONEs are skipped on php5.2 because of performance concerns
		if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
			$this->assertEquals( '', $actual );
		} else {
			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @group timezone-flakey
	 */
	public function testVTimezoneNonIntegerOffset() {

		if ( defined( 'HHVM_VERSION' ) || version_compare( PHP_VERSION, '7.0', '>=' ) ) {
			$this->markTestSkipped(
				'This test is skipped on HHVM and PHP 7.0+ because of timezone definition issues'
			);
		}

		$timezone = new DateTimeZone( 'Asia/Tehran' );
		$time1 = strtotime( '2015-01-01' );
		$time2 = strtotime( '2015-12-31' );

		$expected = "BEGIN:VTIMEZONE\r
TZID:Asia/Tehran\r
BEGIN:STANDARD\r
TZOFFSETFROM:+0430\r
TZOFFSETTO:+0330\r
DTSTART:20140921T193000\r
TZNAME:IRST\r
END:STANDARD\r
BEGIN:DAYLIGHT\r
TZOFFSETFROM:+0330\r
TZOFFSETTO:+0430\r
DTSTART:20150321T203000\r
TZNAME:IRDT\r
END:DAYLIGHT\r
BEGIN:STANDARD\r
TZOFFSETFROM:+0430\r
TZOFFSETTO:+0330\r
DTSTART:20150921T193000\r
TZNAME:IRST\r
END:STANDARD\r
END:VTIMEZONE";

		$actual = eventorganiser_ical_vtimezone( $timezone, $time1, $time2 );

		//VTIMEZONEs are skipped on php5.2 because of performance concerns
		if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
			$this->assertEquals( '', $actual );
		} else {
			$this->assertEquals( $expected, $actual );
		}
	}


	/**
	 * If a named timezone is used, then we should see a VTIMEZONE which appropriate DAYLIGHT and
	 * STANDARD timezone components spanning the period in which events occur.
	 *
	 * VTIMEZONEs are skipped on php5.2 because of performance concerns
	 *
	 * @requires PHP 5.3.0
	 */
	public function testVTimezonePeriod(){

		if ( defined( 'HHVM_VERSION' ) ) {
			$this->markTestSkipped(
				'This test is skipped on HHVM because of timezone definition issues'
			);
		}

		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'Europe/Paris' );
		wp_cache_delete( 'eventorganiser_timezone' );

		$events = array();

		//A standard recurring event
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2015-10-01 17:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2015-10-01 18:00', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2015-10-29 17:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'schedule'      => 'weekly',
			'schedule_meta' => array( 'TH' ),
			'post_title'    => 'Recurring event',
			'post_content'  => 'Post content',
			'post_excerpt'  => 'Post excerpt',
			'post_date'     => '2015-02-18 17:30:00',
		) );
		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test-1' );
		$events[] = $event_id;

		//A recurring event with some includes to extend the period of interest
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2015-09-01 13:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2015-09-01 14:30', eo_get_blog_timezone() ),
			'schedule_last' => new DateTime('2015-12-01 13:00', eo_get_blog_timezone() ),
			'frequency'     => 1,
			'schedule'      => 'monthly',
			'schedule_meta' => 'BYMONTHDAY=01',
			'include'      => array(
				new DateTime( '2016-12-25 13:00', eo_get_blog_timezone() ),
			),
			'post_title'    => 'Event with outlying included date',
			'post_content'  => 'Post content',
			'post_excerpt'  => 'Post excerpt',
			'post_date'     => '2015-02-18 17:30:00',
		) );
		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test-2' );
		$events[] = $event_id;


		//A single outlying event
		$event_id = $this->factory->event->create( array(
			'start'         => new DateTime('2014-05-01 03:00', eo_get_blog_timezone() ),
			'end'           => new DateTime('2014-05-01 04:00', eo_get_blog_timezone() ),
			'post_title'    => 'Single event',
			'post_content'  => 'Post content',
			'post_excerpt'  => 'Post excerpt',
			'post_date'     => '2015-02-18 17:30:00',
		) );
		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test-3' );
		$events[] = $event_id;

		query_posts( array( 'post__in' => $events, 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) );

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();

		//Get expected feed output
		$expected = $this->_readExpectedIcal( EO_DIR_TESTDATA .'/ical-feed-expected/multiple-events-with-timezone.ical' );

		update_option( 'timezone_string', $original_timezone );
		wp_cache_delete( 'eventorganiser_timezone' );

		$this->assertEquals( $expected, $actual );

	}


	public function _readExpectedIcal( $file ) {
		$now = new DateTime();
		ob_start();
		include( $file );
		$expected = ob_get_contents();
		ob_end_clean();
		return $expected;
	}

}
