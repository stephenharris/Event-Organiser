<?php

class eventTest extends EO_UnitTestCase
{
    public function testEventEndBeforeStart()
    {
		$tz = eo_get_blog_timezone();

		$event = array(
			'start' => new DateTime( '2013-10-19 15:30:00', $tz ),
			'end' => new DateTime( '2013-10-19 14:30:00', $tz ),
		);
		
		$response = eo_insert_event($event);

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'Start date occurs after end date.',  $response->get_error_message( $response->get_error_code() ) );
    }

    public function testEventHasNoDates()
    {
		$tz = eo_get_blog_timezone();

		$event = array(
			'start' => new DateTime( '2013-10-19 15:30:00', $tz ),
			'end' => new DateTime( '2013-10-19 15:45:00', $tz ),
			'exclude' => array( new DateTime( '2013-10-19 15:30:00', $tz ) ),
		);
		
		$response = eo_insert_event($event);

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'Event does not contain any dates.',  $response->get_error_message( $response->get_error_code() ) );
    }
    
    public function testDateDifference()
    {

    	$tz = eo_get_blog_timezone();

		$event = array(
			'start'              => new DateTime( '2013-10-19 15:30:00', $tz ),
			'end'                => new DateTime( '2013-10-19 15:45:00', $tz ),
			'frequeny'           => 1,
			'schedule'           => 'weekly',
			'number_occurrences' => 4,
		);

		//Create event and store occurrences
		$event_id = eo_insert_event( $event );

		//Event dates are 19th Oct, 26th Oct, 2nd Nov, 9th Nov
		$original_occurrences = eo_get_the_occurrences( $event_id );
		
		//Update event
		$new_event_data = $event;
		$new_event_data['include']            = array( new DateTime( '2013-10-20 15:30:00', $tz ) );
		$new_event_data['schedule_last']      = false;
		$new_event_data['number_occurrences'] = 2;
		eo_update_event( $event_id, $new_event_data );
		
		//Get new occurrences
		//Event dates are 19th Oct, 26th Oct, 20th Nov
		$new_occurrences = eo_get_the_occurrences( $event_id ); 

		//Compare
		$added   = array_udiff( $new_occurrences, $original_occurrences, '_eventorganiser_compare_dates' );
		$removed = array_udiff( $original_occurrences, $new_occurrences, '_eventorganiser_compare_dates' );
		$kept    = array_intersect_key( $original_occurrences, $new_occurrences );
		
		$added   = array_map( 'eo_format_datetime', $added, array_fill(0, count($added), 'Y-m-d H:i:s' ) );
		$removed = array_map( 'eo_format_datetime', $removed, array_fill(0, count($removed), 'Y-m-d H:i:s' ) );
		$kept    = array_map( 'eo_format_datetime', $kept, array_fill(0, count($kept), 'Y-m-d H:i:s' ) );
		
		$this->assertEquals( array( '2013-10-20 15:30:00' ), $added );
		$this->assertEquals( array( '2013-11-02 15:30:00', '2013-11-09 15:30:00' ), $removed );
		$this->assertEquals( array( '2013-10-19 15:30:00', '2013-10-26 15:30:00' ), $kept );
    }
    

    /**
     * Tests that updating the start time of an event keeps the occurrence IDs.
     * This *may* change as occurrences may be able to share the same date
     * @see https://github.com/stephenharris/Event-Organiser/issues/240 
     * 
     * @see https://wordpress.org/support/topic/all-events-showing-1200-am-as-start-and-end-time
     * @see https://github.com/stephenharris/Event-Organiser/issues/195
     */
    public function testUpdateTimeOnly()
    {

    	$tz = eo_get_blog_timezone();

		$event = array(
			'start'              => new DateTime( '2013-10-19 15:30:00', $tz ),
			'end'                => new DateTime( '2013-10-19 15:45:00', $tz ),
			'frequeny'           => 1,
			'schedule'           => 'weekly',
			'number_occurrences' => 4,
		);
		
		//Create event and store occurrences
		$event_id = eo_insert_event( $event );
		$original_occurrences    = eo_get_the_occurrences( $event_id );
		
		//Update event
		$new_event_data = $event;
		$new_event_data['start'] = new DateTime( '2013-10-19 14:30:00', $tz );
		eo_update_event( $event_id, $new_event_data );
		
		//Get new occurrences
		$new_occurrences    = eo_get_the_occurrences( $event_id );
				
		
		//Compare
		$added     = array_udiff( $new_occurrences, $original_occurrences, '_eventorganiser_compare_dates' );
		$removed   = array_udiff( $original_occurrences, $new_occurrences, '_eventorganiser_compare_dates' );
		$updated   = array_intersect_key( $new_occurrences, $original_occurrences );
		$updated_2 = array_intersect_key( $original_occurrences, $new_occurrences );
		
		$updated   = $this->array_map_assoc( 'eo_format_datetime', $updated, array_fill(0, count($updated), 'Y-m-d H:i:s' ) );
		$updated_2 = $this->array_map_assoc( 'eo_format_datetime', $updated_2, array_fill(0, count($updated_2), 'Y-m-d H:i:s' ) );

		
		//Check added/removed/update dates are as expected: all dates should just be updated
		$this->assertEquals( array(), $added );
		$this->assertEquals( array(), $removed );
		$this->assertEquals( array( 
			'2013-10-19 14:30:00',
			'2013-10-26 14:30:00',
			'2013-11-02 14:30:00', 
			'2013-11-09 14:30:00',  
		), array_values( $updated ) );
		
		
		//Now check that dates have been updated as expected (i.e. there have been no 'swapping' of IDs). 		
		//First: Sanity check, make sure IDs agree.
    	$diff = array_diff_key( $updated, $updated_2 );
    	$this->assertTrue(  empty( $diff ) && count( $updated ) == count( $updated_2 ) );
		ksort( $updated );
    	ksort( $updated_2 );
		$updated_map = array_combine( $updated_2, $updated );

		//Now check that the dates have been updated as expected: original => new 
		$this->assertEquals( array( 
			'2013-10-19 15:30:00' => '2013-10-19 14:30:00',
			'2013-10-26 15:30:00' => '2013-10-26 14:30:00',
			'2013-11-02 15:30:00' => '2013-11-02 14:30:00', 
			'2013-11-09 15:30:00' => '2013-11-09 14:30:00',  
		), $updated_map );
			
    }
    
    
    /**
     * Tests that updating the end date/time of an event keeps the occurrence IDs
     * 
     * @see https://wordpress.org/support/topic/all-events-showing-1200-am-as-start-and-end-time
     * @see https://github.com/stephenharris/Event-Organiser/issues/195
     */
    public function testUpdateEndDateTime()
    {

    	$tz = eo_get_blog_timezone();

		$event = array(
			'start'   => new DateTime( '2013-10-19 15:30:00', $tz ),
			'end'     => new DateTime( '2013-10-19 15:45:00', $tz ),
			'all_day' => false,
		);
		
		//Create event and store occurrences
		$event_id = eo_insert_event( $event );
		$original_occurrences    = eo_get_the_occurrences( $event_id );
		$original_occurrence_ids = array_keys( $original_occurrences );
		$original_occurrence_id  = array_shift( $original_occurrence_ids );
		
		//Update event
		$new_event_data = $event;
		$new_event_data['end'] = new DateTime( '2013-10-20 16:45:00', $tz );
		eo_update_event( $event_id, $new_event_data );
		
		//Get new occurrences
		$new_occurrences    = eo_get_the_occurrences( $event_id );
		$new_occurrence_ids = array_keys( $new_occurrences );
		$new_occurrence_id  = array_shift( $new_occurrence_ids );  
		
		
		$this->assertTrue( $original_occurrence_id == $new_occurrence_id );

		$this->assertEquals( '2013-10-20 16:45:00', eo_get_the_end( 'Y-m-d H:i:s', $event_id, null, $new_occurrence_id ) );
		
    }
    
    function testEventSchedule(){
    	
    	$tz    = eo_get_blog_timezone();
    	$start = new DateTime( '2014-06-17 14:45:00', $tz );
    	$end   = new DateTime( '2014-06-17 15:45:00', $tz );
    	$inc   = array( new DateTime( '2014-08-16 14:45:00', $tz ) );
    	$exc   = array( new DateTime( '2014-06-19 14:45:00', $tz ),  new DateTime( '2014-07-03 14:45:00', $tz ) );
    	$event = array(
			'start'         => $start,
			'end'           => $end,
			'frequency'     => 2,
			'schedule'      => 'weekly',
    		'schedule_meta' => array( 'TU', 'TH' ),
    		'include'       => $inc,
    		'exclude'       => $exc,
			'schedule_last' => new DateTime( '2014-08-15 14:45:00', $tz ),
		);
		
		$event_id = $this->factory->event->create( $event );
		$schedule = eo_get_event_schedule( $event_id );
		
		$this->assertEquals( $start, $schedule['start'] );
		$this->assertEquals( $end, $schedule['end'] );
		$this->assertEquals( false, $schedule['all_day'] );
		
		$this->assertEquals( 'weekly', $schedule['schedule'] );
		$this->assertEquals( array( 'TU', 'TH' ), $schedule['schedule_meta'] );
		$this->assertEquals( 2, $schedule['frequency'] );
		
		$duration        = eo_date_interval( $start, $end, '+%y year +%m month +%d days +%h hours +%i minutes +%s seconds' );
		$schedule_last   = new DateTime( '2014-08-16 14:45:00', $tz );
		$schedule_finish = clone $schedule_last;
		$schedule_finish->modify( $duration );
		
		$this->assertEquals( $start, $schedule['schedule_start'] );
		$this->assertEquals( $schedule_last, $schedule['schedule_last'] );
		$this->assertEquals( $schedule_finish, $schedule['schedule_finish'] );
		
		$this->assertEquals( $inc, $schedule['include'] );
		$this->assertEquals( $exc, $schedule['exclude'] );
		
		
		$occurrences = array( 
			new DateTime( '2014-06-17 14:45:00', $tz ),
			//new DateTime( '2014-06-19 14:45:00', $tz ),
			new DateTime( '2014-07-01 14:45:00', $tz ),
			//new DateTime( '2014-07-03 14:45:00', $tz ),
			new DateTime( '2014-07-15 14:45:00', $tz ),
			new DateTime( '2014-07-17 14:45:00', $tz ),
			new DateTime( '2014-07-29 14:45:00', $tz ),
			new DateTime( '2014-07-31 14:45:00', $tz ),
			new DateTime( '2014-08-12 14:45:00', $tz ),
			new DateTime( '2014-08-14 14:45:00', $tz ),
			new DateTime( '2014-08-16 14:45:00', $tz ),
			
		);
		
		$this->assertEquals( $occurrences, array_values( $schedule['_occurrences'] ) );	
    }

    
    
	function array_map_assoc( $callback, $arr1 ) { 

		$results = array(); 
		$args    = array();
		 
		if( func_num_args() > 2 ){
			$args = func_get_args();
			$args = array_slice( $args, 2 );
			$args = (array) array_shift( $args );
		}

		foreach( $arr1 as $key => $value ) { 
			$arg = array_shift( $args );
			$callback_args = array( $value, $arg );
			$results[$key] = call_user_func_array( $callback, $callback_args );
		} 

		return $results; 
	}
	
	
	/**
	 * @see https://github.com/stephenharris/Event-Organiser/issues/205
	 * Tests event end date is created successfully. 
	 */
	public function testEventAtEndOfMonth()
    {
		
		$original_tz     = get_option( 'timezone_string' );
		$original_offset = get_option( 'gmt_offset' );
		
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 10 );
		
		$tz = eo_get_blog_timezone();
		
		$event = array(
			'post_title' => 'Test event',
			'start'      => new DateTime( '2014-07-01 00:00:00', $tz ),
			'end'        => new DateTime( '2014-07-31 23:59:00', $tz ),
			'all_day'    => 1, 
		);
		
		$event_id       = eo_insert_event( $event );
		$occurrences    = eo_get_the_occurrences( $event_id );
		$occurrence_ids = array_keys( $occurrences );
		$occurrence_id  = array_shift( $occurrence_ids );

		$this->assertEquals( '2014-07-31',  eo_get_the_end( 'Y-m-d', $event_id, null, $occurrence_id ) );
		
		update_option( 'timezone_string', $original_tz );
		update_option( 'gmt_offset', $original_offset );
    }

    /**
     * @see https://github.com/stephenharris/Event-Organiser/issues/224
     */
    public function testEventSpanningDSTBoundary(){
    	
    	$original_tz = get_option( 'timezone_string' );
		
		update_option( 'timezone_string', 'Europe/Berlin' );
		$tz = eo_get_blog_timezone();
		
    	$event = array(
			'start'         => new DateTime( '2013-10-25 00:00:00', $tz ),
			'end'           => new DateTime( '2013-10-28 23:59:00', $tz ),
			'frequency'     => 1,
    		'all_day'       => true,
    	    'schedule'      => 'weekly',
    	    'schedule_last' => new DateTime( '2013-11-01 00:00:00', $tz ),
		);
		
		//Create event and store occurrences
		$event_id = eo_insert_event( $event );
		
		$occurrences = eo_get_the_occurrences_of( $event_id );
		$occurrence  = array_shift( $occurrences );
		$this->assertEquals( $event['end'], $occurrence['end'] );
		
		//The second occurrence doesn't across DST boundary 
		$occurrence  = array_shift( $occurrences );
		$expected    = new DateTime( '2013-11-04 23:59:00', $tz );
		$this->assertEquals( $expected, $occurrence['end'] );

		update_option( 'timezone_string', $original_tz );
    	
    }
    
    /**
     * This check is here to ensure that updating an event without
     * changing any details does not cause the events to be deleted
     * and recreated.
     */
    function testUpdatingExistingEvent(){
    	 
    	$tz    = eo_get_blog_timezone();
    	$start = new DateTime( '2015-02-13 14:45:00', $tz );
    	$end   = new DateTime( '2015-02-13 15:45:00', $tz );
    	 
    	$event = array(
    		'start'         => $start,
    		'end'           => $end,
    		'frequency'     => 1,
    		'schedule'      => 'weekly',
    		'schedule_meta' => array( 'FR' ),
    		'schedule_last' => new DateTime( '2015-02-27 14:45:00', $tz ),
    	);
    	 
    	$event_id   = $this->factory->event->create( $event );
  
    	$expected = eo_get_the_occurrences( $event_id );
   	
    	eo_update_event( $event_id );

    	$actual = eo_get_the_occurrences( $event_id );

    	//$actual and $expected contain the occurrence IDs as keys.
    	$this->assertEquals( $expected, $actual );
    	 
    }
    
    /**
     * This check is here to ensure that updating an event when
     * changing details in an irrelevant way
     */
    function testUpdatingExistingEventIrrelevantDetails(){
    
    	$tz    = eo_get_blog_timezone();
    	$start = new DateTime( '2015-02-13 14:45:00', $tz );
    	$end   = new DateTime( '2015-02-13 15:45:00', $tz );
    
    	$event = array(
    		'start'         => $start,
    		'end'           => $end,
    		'frequency'     => 1,
    		'schedule'      => 'weekly',
    		'schedule_last' => new DateTime( '2015-02-27 14:45:00', $tz ),
    	);
    
    	$event_id   = $this->factory->event->create( $event );
    
    	$expected = eo_get_the_occurrences( $event_id );
    
    	eo_update_event( $event_id, array(
    		'post_title'    => "Title of event", //Doesn't affect dates
    		'start'         => $start,
    		'end'           => $end,
    		//'frequency'     => 1, //Implicitly set to 1
    		'schedule'      => 'weekly',
    		'schedule_meta' => array( 'FR' ), //Not required. 
    		'schedule_last' => new DateTime( '2015-02-27 14:45:00', $tz ),
    	));
    
    	$actual = eo_get_the_occurrences( $event_id );

    	//$actual and $expected contain the occurrence IDs as keys.    
    	$this->assertEquals( $expected, $actual );
    
    }
    
    /**
     * Event Organiser generates start dates and uses the duration of the first
     * event to calculate the other occurrences. So the end date of a recurring event  
     * may vary.
     * @see https://wordpress.org/support/topic/last-day-missing-because-of-the-leap-year-2016?replies=3
     */
    public function testOverLeapYear()
    {
    	$event = array(
    		'start'         => new DateTime( '2015-02-28 00:00:00', eo_get_blog_timezone() ), //Tuesday
    		'end'           => new DateTime( '2015-03-01 00:00:00', eo_get_blog_timezone() ),
    		'schedule'      => 'yearly',
    		'schedule_last' => new DateTime( '2017-03-01 00:00:00', eo_get_blog_timezone() ),
    	);
    	
    	$event_id   =  eo_insert_event( $event );
    	
    	$actual   = array_values( eo_get_the_occurrences_of( $event_id ) );
    	$expected = array(
    		array(
    			'start' => new DateTime( '2015-02-28 00:00:00', eo_get_blog_timezone() ),
    			'end'   => new DateTime( '2015-03-01 00:00:00', eo_get_blog_timezone() ),
    		),
    		array(
    			'start' => new DateTime( '2016-02-28 00:00:00', eo_get_blog_timezone() ),
    			'end'   => new DateTime( '2016-02-29 00:00:00', eo_get_blog_timezone() ),
    		),
    		array(
    			'start' => new DateTime( '2017-02-28 00:00:00', eo_get_blog_timezone() ),
    			'end'   => new DateTime( '2017-03-01 00:00:00', eo_get_blog_timezone() ),
			),
    	);
    	
    	//$actual and $expected contain the occurrence IDs as keys.
    	$this->assertEquals( $expected, $actual );
    	 
    }

    /**
     * Event Organiser generates start dates and uses the duration of the first
     * event to calculate the other occurrences. So the end date of a recurring event
     * may vary.
     * @see https://wordpress.org/support/topic/last-day-missing-because-of-the-leap-year-2016?replies=3
     */
    public function testOverVarythingMonths()
    {
    	$event = array(
    		'start'         => new DateTime( '2015-02-28 00:00:00', eo_get_blog_timezone() ), //Tuesday
    		'end'           => new DateTime( '2015-03-03 00:00:00', eo_get_blog_timezone() ),
    		'schedule'      => 'monthly',
    		'schedule_meta' => 'BYMONTHDAY=28',
    		'schedule_last' => new DateTime( '2015-04-28 00:00:00', eo_get_blog_timezone() ),
    	);
    	 
    	$event_id   =  eo_insert_event( $event );
    	 
    	$actual   = array_values( eo_get_the_occurrences_of( $event_id ) );
    	$expected = array(
    		array(
    			'start' => new DateTime( '2015-02-28 00:00:00', eo_get_blog_timezone() ),
    			'end'   => new DateTime( '2015-03-03 00:00:00', eo_get_blog_timezone() ),
    		),
    		array(
    			'start' => new DateTime( '2015-03-28 00:00:00', eo_get_blog_timezone() ),
    			'end'   => new DateTime( '2015-03-31 00:00:00', eo_get_blog_timezone() ),
    		),
    		array(
    			'start' => new DateTime( '2015-04-28 00:00:00', eo_get_blog_timezone() ),
    			'end'   => new DateTime( '2015-05-01 00:00:00', eo_get_blog_timezone() ),
    		),
    	);
    	 
    	//$actual and $expected contain the occurrence IDs as keys.
    	$this->assertEquals( $expected, $actual );
    
    }
    
    /**
     * Check that the 'until' date is unaffected by included events.
     * Note until != schedule_last (see https://github.com/stephenharris/Event-Organiser/issues/259). 
     */
    public function testUntil()
    {
    
    	$tz = eo_get_blog_timezone();
    	
    	$include = new DateTime( '2015-05-23 15:30:00', $tz );
    	$until   = new DateTime( '2015-05-09 15:30:00', $tz );
    	$event   = array(
    		'start'    => new DateTime( '2015-04-18 15:30:00', $tz ),
    		'end'      => new DateTime( '2015-04-18 15:45:00', $tz ),
    		'frequeny' => 1,
    		'schedule' => 'weekly',
    		'until'    => $until,
    		'include'  => array( $include ),
    	);
    
    	//Create event and store occurrences
    	$event_id = eo_insert_event( $event );
    	
    	$schedule = eo_get_event_schedule( $event_id );
    	
    	$this->assertEquals( $include, eo_get_schedule_last( DATETIMEOBJ, $event_id ) );
    	$this->assertEquals( $include, $schedule['schedule_last'] );
    	$this->assertEquals( $until, $schedule['until'] );
    	
    }
    
    
    /**
     * @see https://github.com/stephenharris/Event-Organiser/issues/242 
     */
    function testDuplicatePostCompatability(){
    	
    	$tz    = eo_get_blog_timezone();
    	$start = new DateTime( '2015-02-12 14:45:00', $tz );
    	$end   = new DateTime( '2015-02-12 15:45:00', $tz );
    	
    	$event = array(
    		'start'         => $start,
    		'end'           => $end,
    		'frequency'     => 1,
    		'schedule'      => 'weekly',
    		'schedule_meta' => array( 'TH' ),
    		'schedule_last' => new DateTime( '2015-02-26 14:45:00', $tz ),
    	);
    	
    	$event_id   = $this->factory->event->create( $event );
    	$event_post = get_post( $event_id );
    	
    	$duplicated_event_id = $this->duplicate( $event_post );
    	$this->duplicate_metadata( $duplicated_event_id, $event_post );
    	eo_update_event( $duplicated_event_id );
    	
    	//Check occurrences of duplicate event exist
    	$expected = array(
    		new DateTime( '2015-02-12 14:45:00', $tz ),
    		new DateTime( '2015-02-19 14:45:00', $tz ),
    		new DateTime( '2015-02-26 14:45:00', $tz ),
    	);
    	
    	$actual = eo_get_the_occurrences( $duplicated_event_id );
    	$actual = array_values( $actual );
    	
    	$this->assertEquals( $expected, $actual );
    	
    }
    
    /**
     * Create a duplicate from a post
     * @see https://wordpress.org/plugins/duplicate-post/
     */
    function duplicate($post, $status = 'draft', $parent_id = '') {

    	// We don't want to clone revisions
    	if ( $post->post_type == 'revision' ){
    		return;
    	}

    	$new_post = array(
    		'menu_order'     => $post->menu_order,
    		'comment_status' => $post->comment_status,
    		'ping_status'    => $post->ping_status,
    		'post_author'    => $post->ID,
    		'post_content'   => $post->post_content,
    		'post_excerpt'   => (get_option('duplicate_post_copyexcerpt') == '1') ? $post->post_excerpt : "",
    		'post_mime_type' => $post->post_mime_type,
    		'post_parent'    => $new_post_parent = empty($parent_id)? $post->post_parent : $parent_id,
    		'post_password'  => $post->post_password,
    		'post_status'    => $new_post_status = (empty($status))? $post->post_status: $status,
    		'post_title'     => $post->post_title,
    		'post_type'      => $post->post_type,
    	);
    	
    	$new_post_id = wp_insert_post( $new_post );
    	
    	// If the copy is published or scheduled, we have to set a proper slug.
    	if( $new_post_status == 'publish' || $new_post_status == 'future' ){
    		$post_name = wp_unique_post_slug( $post->post_name, $new_post_id, $new_post_status, $post->post_type, $new_post_parent );
    		$new_post = array(
    			'ID'        => $new_post_id,
    			'post_name' => $post_name,
    		);

    		// Update the post into the database
    		wp_update_post( $new_post );
    	}

    	// If you have written a plugin which uses non-WP database tables to save
    	// information about a post you can hook this action to dupe that data.
    	if( $post->post_type == 'page' || is_post_type_hierarchical( $post->post_type ) ){
    		do_action( 'dp_duplicate_page', $new_post_id, $post );
    	}else{
    		do_action( 'dp_duplicate_post', $new_post_id, $post );
    	}
    	
    	delete_post_meta( $new_post_id, '_dp_original' );
    	add_post_meta( $new_post_id, '_dp_original', $post->ID );
    	
    	return $new_post_id;
    }
    
    function duplicate_metadata( $new_id, $post ) {
    	$post_meta_keys = get_post_custom_keys( $post->ID );
    	if ( empty( $post_meta_keys ) ){
    		return;
    	}
    	$meta_blacklist = array();
    	$meta_keys      = array_diff( $post_meta_keys, $meta_blacklist );
    	
    	foreach( $meta_keys as $meta_key ) {
    		$meta_values = get_post_custom_values( $meta_key, $post->ID );
    		foreach ( $meta_values as $meta_value ) {
    			$meta_value = maybe_unserialize($meta_value);
    			add_post_meta( $new_id, $meta_key, $meta_value );
    		}
    	}
    }
}

