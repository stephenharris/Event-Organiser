<?php

class dateFormatTest extends PHPUnit_Framework_TestCase
{
	
	public function testDateFormat() {
		$date = new DateTime( '2016-05-17 18:43' );
		$this->assertEquals( 'Tuesday 17th May 2016', eo_format_datetime( $date, 'l jS F Y' ) );
	}

}

