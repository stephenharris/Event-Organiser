<?php

class dateModifyTest extends PHPUnit_Framework_TestCase
{

	public function testModifyDatePHP52()
	{
		$modify = 'first Monday of +1 Month';
		$expected = new DateTime( '2014-02-03' );
		$date     = new DateTime( '2014-01-30' );
		$this->assertEquals( $expected, _eventorganiser_php52_modify( $date, $modify ) );
		
		//Check multiple months
		$modify = 'LaSt FrIdAY oF +5 MoNtH';
		$expected = new DateTime( '2014-06-27' );
		$date = new DateTime( '2014-01-30' );
		$this->assertEquals( $expected, _eventorganiser_php52_modify( $date, $modify ) );
		
		//Check without a +
		$modify = 'LaSt FrIdAY oF 2 MoNtH';
		$expected = new DateTime( '2014-03-28' );
		$date = new DateTime( '2014-01-30' );
		$this->assertEquals( $expected, _eventorganiser_php52_modify( $date, $modify ) );

		//Check wit a -
		$modify = 'third wednesday of -1 month';
		$expected = new DateTime( '2013-12-18' );
		$date = new DateTime( '2014-01-30' );
		$this->assertEquals( $expected, _eventorganiser_php52_modify( $date, $modify ) );
	}
	
}

