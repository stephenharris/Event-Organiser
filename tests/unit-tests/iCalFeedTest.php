<?php

class iCalFeedTest extends EO_UnitTestCase
{

	public function testRRULE(){
	
		$event_id = $this->factory->event->create( array(
				'start'=> new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
				'end'=> new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
				'schedule_last'=> new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
				'frequency' => 1,
				'all_day' => 0,
				'schedule'=>'weekly',
				'schedule_meta' => array( 'MO' ),
				'post_title'=>'The Event Title',
				'post_content'=>'My event content',
		) );
		 
		$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;UNTIL=20131230T210000Z", eventorganiser_generate_ics_rrule( $event_id ) );
		
	}
	

	public function testOrganizer(){

		//Event recurrs every Monday evening in New York (event recurs very Tuesday in UTC)
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
			'post_author' => 1,
			'post_status' => 'publish',
			'post_date'   => '2015-02-18 17:30:00',
		) );
		$now = new DateTime();
		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );
				
		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) ); 

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();
		
		//Get expected feed output
		ob_start();
		include(  EO_DIR_TESTDATA .'/ical-feed-expected/organizer.ical' );
		$expected = ob_get_contents();
		ob_end_clean();
		$expected = str_replace( '%%now%%', $now->format( 'Ymd\THis\Z' ), $expected );
				 
		$this->assertEquals( $expected, $actual );
	}
	
	public function testSummary(){

		//Event recurrs every Monday evening in New York (event recurs very Tuesday in UTC)
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
		$now = new DateTime();
		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );
				
		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) ); 

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();
		
		//Get expected feed output
		ob_start();
		include(  EO_DIR_TESTDATA .'/ical-feed-expected/description.ical' );
		$expected = ob_get_contents();
		ob_end_clean();
		$expected = str_replace( '%%now%%', $now->format( 'Ymd\THis\Z' ), $expected );
		
		$this->assertEquals( $expected, $actual );
	}
	
	public function testEscapedCharacters(){

		//Event recurrs every Monday evening in New York (event recurs very Tuesday in UTC)
		$event_id = $this->factory->event->create( array(
			'start'        => new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'          => new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'post_title'   => 'The; Event: Title, contains some \\\n characters which need escaping',
			'post_content' => 'The content contains semi colon; and colons: which are fine. A comma, and new line \\\n which is not a new line And then \\\ a backslash.',
			'post_excerpt' => false,
			'post_status'  => 'publish',
			'post_date'    => '2015-02-18 17:30:00',
		) );
		$now = new DateTime();
		update_post_meta( $event_id, '_eventorganiser_uid', 'unit-test' );
				
		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) ); 

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();
		
		//Get expected feed output
		ob_start();
		include(  EO_DIR_TESTDATA .'/ical-feed-expected/escaped.ical' );
		$expected = ob_get_contents();
		ob_end_clean();
		$expected = str_replace( '%%now%%', $now->format( 'Ymd\THis\Z' ), $expected );
		
		$this->assertEquals( $expected, $actual );
	}
	
	
	public function testRRULE_all_day(){
		global $wpdb;
		$wpdb->db_connect();
		
		wp_cache_set( 'eventorganiser_timezone', 'America/New_York' );
	
		//Event recurrs every Monday evening in New York but is all day, so day should remain on Monday in UTC
    	$event_id = eo_insert_event( array(
			'start'=> new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'=> new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule_last'=> new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
			'frequency' => 1,
			'all_day' => 1,
			'schedule'=>'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'=>'The Event Title',
			'post_content'=>'My event content',
		) );
		
		$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;UNTIL=20131231T020000Z", eventorganiser_generate_ics_rrule( $event_id ) );

		wp_cache_delete( 'eventorganiser_timezone' );
		
	}
	
    public function testTimezoneChangeRRULE(){
    	
    	//When day changes when start date is converted to UTC timezone (for iCal feed)
    	//Remember to correct [day] the 'reccurs weekly by [day]', so thats true for UTC timezone.
    	wp_cache_set( 'eventorganiser_timezone', 'America/New_York' );

    	//Event recurrs every Monday evening in New York (event recurs very Tuesday in UTC)
	$event_id = $this->factory->event->create( array(
			'start'=> new DateTime('2013-12-02 21:00', eo_get_blog_timezone() ),
			'end'=> new DateTime('2013-12-02 23:00', eo_get_blog_timezone() ),
			'schedule_last'=> new DateTime('2013-12-30 21:00', eo_get_blog_timezone() ),
			'frequency' => 1,
			'all_day' => 0,
			'schedule'=>'weekly',
			'schedule_meta' => array( 'MO' ),
			'post_title'=>'The Event Title',
			'post_content'=>'My event content',
		) );
    	
    	$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=TU;UNTIL=20131231T020000Z", eventorganiser_generate_ics_rrule( $event_id ) );
    	
    	wp_cache_delete( 'eventorganiser_timezone' );
    	
    	
    	//Now try it the other direction....
    	wp_cache_set( 'eventorganiser_timezone', 'Europe/Moscow' );

    	//Event recurrs every Monday morning in Moscow (event recurs very Sunday in UTC)
	$event_id = $this->factory->event->create( array(
    			'start'=> new DateTime('2013-12-02 01:00', eo_get_blog_timezone() ),
    			'end'=> new DateTime('2013-12-02 02:00', eo_get_blog_timezone() ),
    			'schedule_last'=> new DateTime('2013-12-30 01:00', eo_get_blog_timezone() ),
    			'frequency' => 1,
    			'all_day' => 0,
    			'schedule'=>'weekly',
    			'schedule_meta' => array( 'MO' ),
    			'post_title'=>'The Event Title',
    			'post_content'=>'My event content',
    	) );
    	 
    	$this->assertEquals( "FREQ=WEEKLY;INTERVAL=1;BYDAY=SU;UNTIL=20131229T210000Z", eventorganiser_generate_ics_rrule( $event_id ) );
    	 
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
}
