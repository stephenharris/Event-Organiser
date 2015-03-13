<?php

class relativeDateQueryTest extends EO_UnitTestCase
{

	public function testChronology(){
		
		$event_ids = array();
		
		//Past event
		$event_ids[] = $this->factory->event->create(
				array(
						'start'		=> new DateTime( '-2 hours' ),
						'end'		=> new DateTime( '-1 hours' ),
						'all_day' 	=> 0,
						'schedule'	=> 'once',
				)
		);
		
		//Running event
		$event_ids[] = $this->factory->event->create(
			array(
				'start'		=> new DateTime( '-30 minutes' ),
				'end'		=> new DateTime( '+30 minutes' ),
				'all_day' 	=> 0,
				'schedule'	=> 'once',
			)
		);
		
		//Future event
		$event_ids[] = $this->factory->event->create(
				array(
						'start'		=> new DateTime( '+1 hours' ),
						'end'		=> new DateTime( '+2 hours' ),
						'all_day' 	=> 0,
						'schedule'	=> 'once',
				)
		);
		

		//Past events
		$past_events = eo_get_events( array(
				'fields' => 'ids',
				'event_end_before' => 'now',
		));
		$this->assertEquals( array( $event_ids[0] ), array_map( 'intval', $past_events ) );
		
		
		//Running events
		$running_events = eo_get_events( array(
			'fields' => 'ids',
			'event_start_before' => 'now',
			'event_end_after' => 'now',
		));
		$this->assertEquals( array( $event_ids[1] ), array_map( 'intval', $running_events ) );
		
		
		//Future events
		$future_events = eo_get_events( array(
				'fields' => 'ids',
				'event_start_after' => 'now',
		));
		$this->assertEquals( array( $event_ids[2] ), array_map( 'intval', $future_events ) );
		
		
		//Events to finish
		$to_finish = eo_get_events( array(
				'fields' => 'ids',
				'event_end_after' => 'now',
		));
		$this->assertEquals( array( $event_ids[1], $event_ids[2] ), array_map( 'intval', $to_finish ) );
		
		
		//Events that have started
		$events_started = eo_get_events( array(
				'fields' => 'ids',
				'event_start_before' => 'now',
		));
		$this->assertEquals( array( $event_ids[0], $event_ids[1]  ), array_map( 'intval', $events_started ) );
		
	}
	

	public function testNoTimeQueries(){
		
		$event_ids = array();
		
		$today = new DateTime( 'today' );
		$today->setTime( 15, 0 );
		
		//Yesterday event
		$start = new DateTime( 'yesterday' );
		$end = new DateTime( 'yesterday' );
		$event_ids[] = $this->factory->event->create(
			array(
				'start'		=> $start->setTime( 15, 0 ),
				'end'		=> $end->setTime( 16, 0 ),
				'all_day' 	=> 0,
				'schedule'	=> 'once',
				)
		);
		
		
		//Today event
		$start = new DateTime( 'today' );
		$end = new DateTime( 'today' );
		$event_ids[] = $this->factory->event->create(
				array(
						'start'		=> $start->setTime( 15, 0 ),
						'end'		=> $end->setTime( 16, 0 ),
						'all_day' 	=> 0,
						'schedule'	=> 'once',
				)
		);
		
		
		//Tomorrow event
		$start = new DateTime( 'tomorrow' );
		$end = new DateTime( 'tomorrow' );
		$event_ids[] = $this->factory->event->create(
				array(
						'start'		=> $start->setTime( 15, 0 ),
						'end'		=> $end->setTime( 16, 0 ),
						'all_day' 	=> 0,
						'schedule'	=> 'once',
				)
		);
	
		//event_[start|end]_[before|after] is inclusive!
		$yesterday = new DateTime( 'yesterday' );
		$events_before_today = eo_get_events( array(
				'fields' => 'ids',
				'event_start_before' => $yesterday->format('Y-m-d'),
		));
		$this->assertEquals( array( $event_ids[0]  ), array_map( 'intval', $events_before_today ) );
		
		
		$tomorrow = new DateTime( 'tomorrow' );
		$events_after_today = eo_get_events( array(
				'fields' => 'ids',
				'event_start_after' => $tomorrow->format('Y-m-d'),
		));
		$this->assertEquals( array( $event_ids[2]  ), array_map( 'intval', $events_after_today ) );
			
	}
	
}

