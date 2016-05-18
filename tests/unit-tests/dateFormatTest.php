<?php

class dateFormatTest extends PHPUnit_Framework_TestCase
{
	
	public function testDateFormat() {
		$date = new DateTime( '2016-05-17 18:43' );
		$this->assertEquals( 'Tuesday 17th May 2016', eo_format_datetime( $date, 'l jS F Y' ) );
	}


	public function testDateFormatNull() {
		$this->setExpectedException( 'Exception', 'Error in formating DateTime object. Expected DateTime, but instead given NULL' );
		eo_format_datetime( null, 'l jS F Y' );
	}

	public function testDateFormatFalse() {
		$this->setExpectedException( 'Exception', 'Error in formating DateTime object. Expected DateTime, but instead given bool' );
		eo_format_datetime( false, 'l jS F Y' );
	}

	public function testDateFormatString() {
		$this->setExpectedException( 'Exception', 'Error in formating DateTime object. Expected DateTime, but instead given string' );
		eo_format_datetime( '', 'l jS F Y' );
	}

}

