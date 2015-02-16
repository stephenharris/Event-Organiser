<?php

class dateFormatTest extends PHPUnit_Framework_TestCase
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
		//@see http://stackoverflow.com/questions/2532729/daylight-saving-time-and-time-zone-best-practices
		//@see https://bugs.php.net/bug.php?id=51051
		$this->assertEquals( '3', eo_date_interval( $date_1, $date_2, '%h' ) );
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
	    
	
	
	public function testModifyDatePHP52()
	{
		$modify = 'first Monday of +1 Month';
		$date = new DateTime( '2014-01-30' );
		$date2 = new DateTime( '2014-01-30' );
		$this->assertEquals( $date->modify( $modify ), _eventorganiser_php52_modify( $date2, $modify ) );
		
		//Check multiple months
		$modify = 'LaSt FrIdAY oF +5 MoNtH';
		$date = new DateTime( '2014-01-30' );
		$date2 = new DateTime( '2014-01-30' );
		$this->assertEquals( $date->modify( $modify ), _eventorganiser_php52_modify( $date2, $modify ) );
		
		//Check without a +
		$modify = 'LaSt FrIdAY oF 2 MoNtH';
		$date = new DateTime( '2014-01-30' );
		$date2 = new DateTime( '2014-01-30' );
		$this->assertEquals( $date->modify( $modify ), _eventorganiser_php52_modify( $date2, $modify ) );

		//Check wit a -
		$modify = 'third wednesday of -1 month';
		$date = new DateTime( '2014-01-30' );
		$date2 = new DateTime( '2014-01-30' );
		$this->assertEquals( $date->modify( $modify ), _eventorganiser_php52_modify( $date2, $modify ) );
	}
	
	
	public function testCreateDate()
	{
		$date = new DateTime( '2013-12-07' );
		$this->assertEquals( $date, eventorganiser_date_create( '7th December 2013') );
	}
	
	
	public function testCheckDateTime()
	{
		$date = new DateTime( '2013-12-31 15:00' );
		
		$this->assertEquals( $date, _eventorganiser_check_datetime( '2013-12-31 15:00', 'Y-m-d' ) );
		$this->assertEquals( $date, _eventorganiser_check_datetime( '31-12-2013 15:00', 'd-m-Y' ) );
		$this->assertEquals( $date, _eventorganiser_check_datetime( '12-31-2013 15:00', 'm-d-Y' ) );
		
		//Without time
		$this->assertEquals( false, _eventorganiser_check_datetime( '2013-12-31', 'Y-m-d' ) );
		
		//With seconds
		$this->assertEquals( $date, _eventorganiser_check_datetime( '2013-12-31 15:00:00', 'Y-m-d' ) );
		
	}
	
	public function testCheckDatetimeTimezone(){
	
		$est = new DateTimeZone( 'America/New_York' );
		$utc = new DateTimeZone( 'UTC' );
		$est_date = new DateTime( "2013-12-05 22:00:00", $est );
		$utc_date = new DateTime( "2013-12-06 03:00:00", $utc );

		//est_date and utc_date the same point in time but in different timezones
		$this->assertTrue( $utc_date == $est_date );
		
		//Run tests
		$parsed_est_date = eo_check_datetime( 'Y-m-d H:i:s', '2013-12-05 22:00:00', $est );
		
		$this->assertTrue( $utc_date == $parsed_est_date );
		
		$parsed_wrong_timezone = eo_check_datetime( 'Y-m-d H:i:s', '2013-12-05 22:00:00', $utc );
		$this->assertTrue( $utc_date != $parsed_wrong_timezone );
		
		//$est should be ignored as format contains timezone reference 
		//TODO eo_check_datetime doesn't support \T or \Z in php5.2
		$parsed_as_utc = eo_check_datetime( 'Y-m-d\TH:i:s\Z', '2013-12-05T22:00:00Z', $est );
		$this->assertTrue( $utc_date == $parsed_est_date );
	}
		
	public function testCheckDatetimeLocale(){
		
		$date = new DateTime( '2013-12-31' );
		$date1 = new DateTime( '2013-12-31 4:30pm' );

		//Set locale
		$original = $this->setLocale();
		$this->setLocale( 'ru_RU' );
			
		//Run test
		$this->assertEquals( $date1, eo_check_datetime( 'Y-m-d g:ia', '2013-12-31 4:30пп' ) );

		//TODO eo_check_datetime doesn't support S in php5.2
		$this->assertEquals( $date1, eo_check_datetime( 'jS F Y g:ia', '31st December 2013 4:30пп' ) );
		
		//Reset locale
		$this->setLocale( $original );		
	}
	
	public function testCheckDatetimeLocaleWithoutMeridian(){

		$date = new DateTime( '2013-12-31 4:30pm' );
	
		//Set locale
		$original = $this->setLocale();
		$this->setLocale( 'fr_FR' );
			
		//Run test
		$this->assertEquals( $date, eo_check_datetime( 'Y-m-d g:ia', '2013-12-31 4:30pm' ) );
	
		//TODO eo_check_datetime doesn't support S in php5.2
		$this->assertEquals( $date, eo_check_datetime( 'jS F Y g:ia', '31st December 2013 4:30pm' ) );
	
		//Reset locale
		$this->setLocale( $original );
	}
	
	public function testCheckDatetimeNoSpacesPHP52(){
	
		$date_pm = new DateTime( '2013-12-31 15:20' );
		$date_am = new DateTime( '2013-12-31 03:20' );
				
		//Check times (am/pm) are interpretted correctly
		$this->assertEquals( $date_pm, eo_check_datetime( 'Y-m-d g:ia', '2013-12-31 3:20pm' ) );
		$this->assertEquals( $date_am, eo_check_datetime( 'Y-m-d g:ia', '2013-12-31 3:20am' ) );
		$this->assertEquals( $date_pm, eo_check_datetime( 'Y-m-d G:i', '2013-12-31 15:20' ) );
		$this->assertEquals( $date_am, eo_check_datetime( 'Y-m-d G:i', '2013-12-31 3:20' ) );	
	}	
	
	public function setLocale( $locale = false ){
		static $current_locale;
		
		if( is_null( $current_locale ) ){
			$current_locale = get_locale();
		}
		
		if( $locale === false ){
			return $current_locale;
		}
		
		global $wp_locale;
		$location = EO_DIR_TESTDATA . '/languages/'.$locale.'.mo';
		load_textdomain( 'default', $location );
		$wp_locale->init();
		
		$current_locale = $locale;
		
		return $locale;
	}

	
	public function testDateRangeFormat(){
		
		$datetime1 = new DateTime( '2015-02-16 13:30:00' );
		$datetime2 = new DateTime( '2015-02-21 14:30:00' );
		$this->assertEquals( '16th February 2015 1:30pm-21st February 2015 2:30pm', eo_format_datetime_range( $datetime1, $datetime2, 'jS F Y g:ia', '-' ) );
		
		$datetime1 = new DateTime( '2015-02-16' );
		$datetime2 = new DateTime( '2015-02-17' );
		$this->assertEquals( '16th-17th February, 2015', eo_format_datetime_range( $datetime1, $datetime2, 'jS F, Y', '-' ) );
		
		$datetime1 = new DateTime( '2015-02-16' );
		$datetime2 = new DateTime( '2015-03-16' );
		$this->assertEquals( '16th February-March, 2015', eo_format_datetime_range( $datetime1, $datetime2, 'jS F, Y', '-' ) );
		
	}
	
	public function testDateRangeFormatIdenticalDates(){
		$datetime1 = new DateTime( '2015-02-16 13:30:00' );
		$this->assertEquals( '16th February 1:30pm', eo_format_datetime_range( $datetime1, $datetime1, 'jS F g:ia', '-' ) );
	}

	
	public function testDateRangeFormatSameDay(){
		$datetime1 = new DateTime( '2015-02-16 13:30:00' );
		$datetime2 = new DateTime( '2015-02-16 14:30:00' );
		$this->assertEquals( '16th February 1:30pm-2:30pm', eo_format_datetime_range( $datetime1, $datetime2, 'jS F g:ia', '-' ) );
	}
	
	public function testDateRangeFormatOrdinalSuffix(){
	
		$datetime1 = new DateTime( '2015-02-16' );
		$datetime2 = new DateTime( '2015-02-17' );
		//16th-17th not 16-17th 
		$this->assertEquals( '16th-17th February 2015', eo_format_datetime_range( $datetime1, $datetime2, 'jS F Y', '-' ) );
	}
	
	public function testDateRnageFormatTime(){
		
		$datetime1 = new DateTime( '2015-02-16 13:30:00' );
		$datetime2 = new DateTime( '2015-02-16 14:30:00' );
		//1:30pm-2:30pm not 1-2:30pm
		$this->assertEquals( '16th February 1:30pm-2:30pm', eo_format_datetime_range( $datetime1, $datetime2, 'jS F g:ia', '-' ) );
	}
}

