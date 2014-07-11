<?php

class eventFunctionsTest extends EO_UnitTestCase
{
	
	/**
	 * Test that using eo_get_add_to_google_link() does not reset timezone of
	 * start/end date of event
	 * @see http://wordpress.org/support/topic/eo_get_add_to_google_link?replies=1
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
	
	
}

