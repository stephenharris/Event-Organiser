<?php

class eventFunctionsTest extends EO_UnitTestCase
{
	
	public function setUp() {
		parent::setUp();
		
		$this->event = array(
			'start'	   => new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
			'end'	   => new DateTime( '2014-07-09 14:02:00', eo_get_blog_timezone() ),
			'all_day'  => 0,
			'schedule' => 'once',
		);
		
		$this->event_id      = $this->factory->event->create( $this->event );
		$occurrences         = array_keys( eo_get_the_occurrences( $this->event_id ) );
		$this->occurrence_id = array_pop(  $occurrences );
	}
	
	public function testGetTheStart(){	
		$this->assertEquals( $this->event['start'], eo_get_the_start( DATETIMEOBJ, $this->event_id, $this->occurrence_id ) );
	}

	public function testGetTheEnd(){
		$this->assertEquals( $this->event['end'], eo_get_the_end( DATETIMEOBJ, $this->event_id, $this->occurrence_id ) );
	}
	
	public function testTheStart(){
		ob_start();
		eo_the_start( 'Y-m-d H:i:s', $this->event_id, $this->occurrence_id );
		$actual = ob_get_contents();
		ob_end_clean();		
		$this->assertEquals( '2014-07-09 13:02:00', $actual );
	}
	
	public function testTheEnd(){
		ob_start();
		eo_the_end( 'Y-m-d H:i:s', $this->event_id, $this->occurrence_id );
		$actual = ob_get_contents();
		ob_end_clean();		
		$this->assertEquals( '2014-07-09 14:02:00', $actual );
	}
	
	public function testDeprecatedAPI(){
		$this->assertEquals( $this->event['start'], eo_get_the_start( DATETIMEOBJ, $this->event_id, null, $this->occurrence_id ) );
		$this->assertEquals( $this->event['end'], eo_get_the_end( DATETIMEOBJ, $this->event_id, null, $this->occurrence_id ) );
		
		ob_start();
		eo_the_start( 'Y-m-d H:i:s', $this->event_id, null, $this->occurrence_id );
		$actual = ob_get_contents();
		ob_end_clean();
		
		$this->assertEquals( '2014-07-09 13:02:00', $actual );
		
		ob_start();
		eo_the_end( 'Y-m-d H:i:s', $this->event_id, null, $this->occurrence_id );
		$actual = ob_get_contents();
		ob_end_clean();
			
		$this->assertEquals( '2014-07-09 14:02:00', $actual );
	}
	
	/**
	 * Test that using eo_get_add_to_google_link() does not reset timezone of
	 * start/end date of event
	 * @see https://wordpress.org/support/topic/eo_get_add_to_google_link?replies=1
	 */
	public function testAddToGoogleLink()
	{
		
		$tz              = ini_get('date.timezone');
		$original_tz     = get_option( 'timezone_string' );
		$original_offset = get_option( 'gmt_offset' );
		
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 10 );
		
		$event_id = $this->factory->event->create(
			array(
				'start'		=> new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'		=> new DateTime( '2014-07-09 14:02:00', eo_get_blog_timezone() ),
				'all_day' 	=> 0,
				'schedule'	=> 'once',
			)
		);
		
		$occurrences    = eo_get_the_occurrences( $event_id );
		$occurrence_ids = array_keys( $occurrences );
		$occurrence_id  = array_shift( $occurrence_ids );
	
		$actual = eo_get_the_start( 'Y-m-d H:i:s', $event_id, null, $occurrence_id );
		$this->assertEquals( '2014-07-09 13:02:00', $actual );

		eo_get_add_to_google_link( $event_id, $occurrence_id );
		
		$actual = eo_get_the_start( 'Y-m-d H:i:s', $event_id, null, $occurrence_id );
		$this->assertEquals( '2014-07-09 13:02:00', $actual );
		
		update_option( 'timezone_string', $original_tz );
		update_option( 'gmt_offset', $original_offset );
	}
	
	public function testEventMetaList(){
		
		$event_id = $this->factory->event->create(
			array(
				'start'		=> new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'		=> new DateTime( '2014-07-09 14:02:00', eo_get_blog_timezone() ),
				'all_day' 	=> 0,
				'schedule'	=> 'once',
			)
		);
		
		$tag = wp_insert_term( 'foobar', 'event-tag' );
		wp_set_object_terms( $event_id, (int) $tag['term_id'], 'event-tag' );

		$cat = wp_insert_term( 'hellworld', 'event-category' );
		wp_set_object_terms( $event_id, (int) $cat['term_id'], 'event-category' );
		
		$html = eo_get_event_meta_list( $event_id );
		
		$expected = file_get_contents( EO_DIR_TESTDATA . '/event-functions/event-meta-list.html' );
		
		$this->assertXmlStringEqualsXmlString( $expected, $html );
		
	}

	public function testMicroDataEventFormat(){
		$event_id = $this->factory->event->create(
			array(
				'start'    => new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'      => new DateTime( '2014-07-09 14:02:00', eo_get_blog_timezone() ),
				'all_day'  => 0,
				'schedule' => 'once',
			)
		);
		$occurrence_ids = array_keys( eo_get_the_occurrences_of( $event_id ) ); 
		$occurrence_id = array_shift( $occurrence_ids );
		
		$expected = '<time itemprop="startDate" datetime="2014-07-09T13:02:00+00:00">July 9, 2014 1:02 pm</time>'
					.' &ndash; <time itemprop="endDate" datetime="2014-07-09T14:02:00+00:00">2:02 pm</time>';
		
		$this->assertEquals( $expected, eo_format_event_occurrence( $event_id, $occurrence_id ) );
		
	}

	public function testMicroDataAllDayEventFormat(){
		$event_id = $this->factory->event->create(
			array(
				'start'    => new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'      => new DateTime( '2014-07-09 14:02:00', eo_get_blog_timezone() ),
				'all_day'  => 1,
				'schedule' => 'once',
			)
		);
		$occurrence_ids = array_keys( eo_get_the_occurrences_of( $event_id ) );
		$occurrence_id = array_shift( $occurrence_ids );
	
		$expected = '<time itemprop="startDate" datetime="2014-07-09">July 9, 2014</time>';
	
		$this->assertEquals( $expected, eo_format_event_occurrence( $event_id, $occurrence_id ) );
	
	}
	
	
	public function testMicroDataAllDayLongEventFormat(){
		$event_id = $this->factory->event->create(
			array(
				'start'    => new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'      => new DateTime( '2014-07-10 14:02:00', eo_get_blog_timezone() ),
				'all_day'  => 1,
				'schedule' => 'once',
			)
		);
		$occurrence_ids = array_keys( eo_get_the_occurrences_of( $event_id ) );
		$occurrence_id  = array_shift( $occurrence_ids );
	
		$expected = '<time itemprop="startDate" datetime="2014-07-09">July 9</time>'
		.' &ndash; <time itemprop="endDate" datetime="2014-07-10">10, 2014</time>';
	
		$this->assertEquals( $expected, eo_format_event_occurrence( $event_id, $occurrence_id ) );
	
	}
	
	public function testEventFormat(){
		$event_id = $this->factory->event->create(
			array(
				'start'    => new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'      => new DateTime( '2014-07-09 14:02:00', eo_get_blog_timezone() ),
				'all_day'  => 0,
				'schedule' => 'once',
			)
		);
		$occurrence_ids = array_keys( eo_get_the_occurrences_of( $event_id ) );
		$occurrence_id = array_shift( $occurrence_ids );
	
		$expected = 'July 9, 2014 1:02 pm &ndash; 2:02 pm';
	
		$this->assertEquals( $expected, eo_format_event_occurrence( $event_id, $occurrence_id, false, false, ' &ndash; ', false ) );
	
	}
	
	public function testAllDayEventFormat(){
		$event_id = $this->factory->event->create(
			array(
				'start'    => new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'      => new DateTime( '2014-07-09 14:02:00', eo_get_blog_timezone() ),
				'all_day'  => 1,
				'schedule' => 'once',
				)
		);
		$occurrence_ids = array_keys( eo_get_the_occurrences_of( $event_id ) );
		$occurrence_id = array_shift( $occurrence_ids );
	
		$expected = 'July 9, 2014';
	
		$this->assertEquals( $expected, eo_format_event_occurrence( $event_id, $occurrence_id, false, false, ' &ndash; ', false ) );
	
	}
	
	public function testAllDayLongEventFormat(){
		$event_id = $this->factory->event->create(
			array(
				'start'    => new DateTime( '2014-07-09 13:02:00', eo_get_blog_timezone() ),
				'end'      => new DateTime( '2014-07-10 14:02:00', eo_get_blog_timezone() ),
				'all_day'  => 1,
				'schedule' => 'once',
			)
		);
		$occurrence_ids = array_keys( eo_get_the_occurrences_of( $event_id ) );
		$occurrence_id  = array_shift( $occurrence_ids );
	
		$expected = 'July 9 &ndash; 10, 2014';
	
		$this->assertEquals( $expected, eo_format_event_occurrence( $event_id, $occurrence_id, false, false, ' &ndash; ', false ) );
	
	}
	
	public function testNextOccurrence(){
		
		$now = new DateTime( 'now' );
		
		$start = clone $now;
		$start->modify( '-5 days' );
		$end = clone $start;
		$end->modify( '+1 hour' );

		$tomorrow = clone $now;
		$tomorrow->modify( '+1 day' );
		
		$event_id = $this->factory->event->create(
			array(
				'start'     => $start,
				'end'       => $end,
				'all_day'   => 0,
				'schedule'  => 'daily',
				'frequency' => 2,
				'until'     =>  new DateTime( '+3 days' )
			)
		);
		
		ob_start();
		eo_next_occurrence( 'Y-m-d H:i', $event_id );
		$actual = ob_get_contents();
		ob_end_clean();
		
		$this->assertEquals( $tomorrow->format(  'Y-m-d H:i' ), $actual );
	}
	
}

