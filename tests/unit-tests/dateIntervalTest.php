<?php

class dateIntervalTest extends PHPUnit_Framework_TestCase
{
	
	public function testDateInterval()
	{
		$date_1 = new DateTime( '2014-03-30 00:00:00' );
		
		$date_2 = clone $date_1;
		$date_2->modify( '+1 year');
		$date_2->modify( '+1 month');
		$date_2->modify( '+1 week');
		$date_2->modify( '+1 day');
		$date_2->modify( '+1 hour');
		$date_2->modify( '+1 minute');
		$date_2->modify( '+1 second');
		
		$this->assertEquals( '1 1 8 1 1 1 404', eo_date_interval( $date_1, $date_2, '%y %m %d %h %i %s %a' ) );
		
		//Test padding
		$this->assertEquals( '01 01 08 01 01 01 404', eo_date_interval( $date_1, $date_2, '%Y %M %D %H %I %S %a' ) );
	}
	
	public function testDateIntervalEscaping()
	{
		$date_1 = new DateTime( '2014-03-30 00:00:00' );
		
		$date_2 = clone $date_1;
		$date_2->modify( '+1 year');
		//var_dump(  eo_date_interval( $date_1, $date_2, '%%' ) );
		

		//Escaping %
		$this->assertEquals( '%', eo_date_interval( $date_1, $date_2, '%' ) );
		$this->assertEquals( '%', eo_date_interval( $date_1, $date_2, '%%' ) );
		
		//Test multiple "%"s
		$this->assertEquals( '%y', eo_date_interval( $date_1, $date_2, '%%y' ) );
		$this->assertEquals( '%1', eo_date_interval( $date_1, $date_2, '%%%y' ) );
		$this->assertEquals( '%%y', eo_date_interval( $date_1, $date_2, '%%%%y' ) );
		$this->assertEquals( '%%1', eo_date_interval( $date_1, $date_2, '%%%%%y' ) );
		
		//"Escaped %a followed by a unescaped %a
		$this->assertEquals( '%a365', eo_date_interval( $date_1, $date_2, '%%a%a' ) );

		//Starting without %
		$this->assertEquals( 'a365', eo_date_interval( $date_1, $date_2, 'a%a' ) );
		
		//General test
		$date_3 = clone $date_1;
		$date_3->modify( '+1 year');
		$date_3->modify( '+2 month');
		$date_3->modify( '+4 week');
		$date_3->modify( '+8 day');
		$date_3->modify( '+16 hour');
		$date_3->modify( '+32 minute');
		$date_3->modify( '+64 second');
		
		$this->assertEquals(
			"%y = 1 years, %m = 3 months, %d = 5 days, %h = 16 hours, %i = 33 min, %s = 4 sec, %a = 462 total days",
			eo_date_interval(
				$date_1,
				$date_3,
				"%%y = %y years, %%m = %m months, %%d = %d days, %%h = %h hours, %%i = %i min, %%s = %s sec, %%a = %a total days"
			)
		);
		
	}
	
	public function testDateIntervalSigns()
	{
		$date_1 = new DateTime( '2014-03-30 00:00:00' );
		$date_2 = clone $date_1;
		$date_2->modify( '+1 year');
		
		$this->assertEquals( '+', eo_date_interval( $date_1, $date_2, '%R' ) );
		$this->assertEquals( '-', eo_date_interval( $date_2, $date_1, '%R' ) );
	
		$this->assertEquals( '', eo_date_interval( $date_1, $date_2, '%r' ) );
		$this->assertEquals( '-', eo_date_interval( $date_2, $date_1, '%r' ) );
	}
	
	
	public function testDateIntervalTotalDayRounded()
	{
		//1.75 days should be rounded to 1
		$date_1 = new DateTime( "2013-07-31 00:00:00");
		$date_2 = clone $date_1;
		$date_2->modify('+42 hours'); //1.75 days difference
		
		$this->assertEquals( '1', eo_date_interval( $date_2, $date_1, '%a' ) );
		
	}


	public function testDateIntervalDST()
	{
		//DST ends 2013-10-27 02:00:00 and clocks turned back to 2013-10-27 01:00:00
		$date_1 = new DateTime( "2013-10-27 00:00:00", new DateTimeZone( "Europe/London" ) ); 
		$date_2 = new DateTime( "2013-10-27 03:00:00 ", new DateTimeZone( "Europe/London" ) );
	
		//Wrong! (But agrees with php's native DateTime Interval which is what is being attempted).
		//@see https://bugs.php.net/bug.php?id=63953
		//@see https://stackoverflow.com/questions/2532729/daylight-saving-time-and-time-zone-best-practices
		//@see https://bugs.php.net/bug.php?id=51051
		$this->assertEquals( '3', eo_date_interval( $date_1, $date_2, '%h' ) );
	}

	public function testDateIntervalDSTDayCount() {

		// Because of DST These dates differ by just over 96 hours, but we would consider
		// it to last 3 days.
		$date_1 = new DateTime( "2013-10-25 00:00:00", new DateTimeZone( "Europe/Berlin" ) );
		$date_2 = new DateTime( "2013-10-28 23:59:00", new DateTimeZone( "Europe/Berlin" ) );

		$this->assertEquals( '3', eo_date_interval( $date_1, $date_2, '%d' ) );
		$this->assertEquals( '3', eo_date_interval( $date_1, $date_2, '%a' ) );

		// Now going the other way... because of DST these dates differ by 71 hours,
		// but we would still consider it to last 3 days.
		$date_1 = new DateTime( "2017-03-25 00:00:00", new DateTimeZone( "Europe/Berlin" ) );
		$date_2 = new DateTime( "2017-03-28 00:00:00 ", new DateTimeZone( "Europe/Berlin" ) );

		$this->assertEquals( '3', eo_date_interval( $date_1, $date_2, '%d' ) );
		$this->assertEquals( '3', eo_date_interval( $date_1, $date_2, '%a' ) );
	}

	/**
	 * @dataProvider datesProvider
	 */
	public function testDurationString($date_1, $date_2) {
		$duration_str = eo_date_interval( $date_1, $date_2, '+%a days +%h hours +%i minutes +%s seconds' );
		$date_1->modify($duration_str);
		$this->assertEquals($date_1, $date_2);
	}

	public function datesProvider() {
		return array(
			array(
				new DateTime( "2013-10-25 00:00:00", new DateTimeZone( "Europe/Berlin" ) ),
				new DateTime( "2013-10-28 23:59:00", new DateTimeZone( "Europe/Berlin" ) )
			),
			array(
				new DateTime( "2017-03-25 00:00:00", new DateTimeZone( "Europe/Berlin" ) ),
				new DateTime( "2017-03-28 00:01:00", new DateTimeZone( "Europe/Berlin" ) )
			),
			array(
				new DateTime( "2018-01-17 00:00:00", new DateTimeZone( "Europe/Berlin" ) ),
				new DateTime( "2018-03-07 23:59:00", new DateTimeZone( "Europe/Berlin" ) )
			),
			array(
				new DateTime( "2017-10-16 16:30:00", new DateTimeZone( "Europe/Berlin" ) ),
				new DateTime( "2017-10-16 17:30:00", new DateTimeZone( "Europe/Berlin" ) )
			),
		);
	}

	public function testDateIntervalLeapYear()
	{
		$date_1 = new DateTime( "2012-02-28 00:00:00");
		$date_2 = new DateTime( "2013-02-28 00:00:00 ");
	
		$this->assertEquals( '366', eo_date_interval( $date_1, $date_2, '%a' ) );
		$this->assertEquals( '1', eo_date_interval( $date_1, $date_2, '%y' ) );
	}
	
	public function testDateIntervalTimeFloat()
	{

		$date_1 = new DateTime( "2013-07-31 10:29:00");
		$date_2 = new DateTime( "2013-08-02 05:32:12 ");
		
		$this->assertEquals( '1 1', eo_date_interval( $date_1, $date_2, '%d %a' ) );
		
		$date_1 = new DateTime( "2013-01-01 15:30:00");
		$date_2 = new DateTime( "2013-01-02 15:30:00 ");
		
		$this->assertEquals( '1 1', eo_date_interval( $date_1, $date_2, '%d %a' ) );
	}
	
	public function testDateIntervalDontModify()
	{
		$date_1 = new DateTime( "2013-01-01 15:30:00");
		$date_2 = new DateTime( "2013-01-02 15:30:00 ");
		
		$prior = $date_1->format('Y-m-d H:i:s');
		$this->assertEquals( '1 1', eo_date_interval( $date_1, $date_2, '%d %a' ) );
		$post = $date_1->format('Y-m-d H:i:s');
		
		$this->assertTrue( $prior == $post, 'Datetime object should not have been modified.' );	
	}

	public function testDateIntervalTimezone()
	{
		//These dates are actually the same
		$date_1 = new DateTime( "2013-12-05 22:00:00", new DateTimeZone( 'America/New_York' ) );
		$date_2 = new DateTime( "2013-12-06 03:00:00", new DateTimeZone( 'UTC' ) );
	
		$this->assertEquals( '0 0 0 0 0', eo_date_interval( $date_1, $date_2, '%d %h %i %s %a' ) );
	}

}

