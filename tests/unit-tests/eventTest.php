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
		$original_occurrences = eo_get_the_occurrences( $event_id );
		
		//Update event
		$new_event_data = $event;
		$new_event_data['include']            = array( new DateTime( '2013-10-20 15:30:00', $tz ) );
		$new_event_data['schedule_last']      = false;
		$new_event_data['number_occurrences'] = 2;
		eo_update_event( $event_id, $new_event_data );
		
		//Get new occurrences
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
    
    function testEventSchedule(){
    	
    	$tz    = eo_get_blog_timezone();
    	$start = new DateTime( '2014-06-17 14:45:00', $tz );
    	$end = new DateTime( '2014-06-17 15:45:00', $tz );
    	$inc = array( new DateTime( '2014-08-16 14:45:00', $tz ) );
    	$exc = array( new DateTime( '2014-06-19 14:45:00', $tz ),  new DateTime( '2014-07-03 14:45:00', $tz ) );
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
		
		
		$duration = $start->diff( $end );
		$schedule_last = new DateTime( '2014-08-16 14:45:00', $tz );
		$schedule_finish = clone $schedule_last;
		$schedule_finish->add( $duration );
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
}

