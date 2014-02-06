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
	
	/*
	public function testOrganizer(){

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
				'post_author' => 1,
				'post_date'	=> '2013-11-01 00:00:00',
				'post_date_gmt' => '2013-11-01 00:00:00',
				'post_status' => 'publish',
		) );
				
		query_posts( array( 'post__in' => array( $event_id ), 'post_type' => 'event', 'group_events_by' => 'series', 'suppress_filters' => false, 'showpastevents' => true ) ); 

		//Get actual feed output
		ob_start();
		include( EVENT_ORGANISER_DIR . 'templates/ical.php' );
		$actual = ob_get_contents();
		ob_end_clean();
		
		//Get expected feed output
		ob_start();
		include(  dirname(__FILE__) . '/src/organizer-iCalOut.txt' );
		$expected = ob_get_contents();
		ob_end_clean();
		 $dtstamp = 'test';
		$expected = trim( str_replace( '%%now%%', $dtstamp, $expected ) );
		
		file_put_contents( dirname(__FILE__) .'/src/organizer-iCalOut-actual.txt', $actual );
		file_put_contents( dirname(__FILE__) .'/src/organizer-iCalOut-expected.txt', $expected );
		 
		//Assert!
		$this->assertEquals( $expected, $actual );
	}
	*/
	
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
		) );;
		
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
    
}
