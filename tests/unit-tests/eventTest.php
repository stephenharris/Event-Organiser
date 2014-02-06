<?php

class eventTest extends PHPUnit_Framework_TestCase
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
}

