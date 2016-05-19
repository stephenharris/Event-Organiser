<?php

class dateCreateTest extends PHPUnit_Framework_TestCase
{

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
		$est_date = new DateTime( '2013-12-05 22:00:00', $est );
		$utc_date = new DateTime( '2013-12-06 03:00:00', $utc );

		//est_date and utc_date the same point in time but in different timezones
		$this->assertTrue( $utc_date == $est_date );
		
		//Run tests
		$parsed_est_date = eo_check_datetime( 'Y-m-d H:i:s', '2013-12-05 22:00:00', $est );
		
		$this->assertTrue( $utc_date == $parsed_est_date );
		
		$parsed_wrong_timezone = eo_check_datetime( 'Y-m-d H:i:s', '2013-12-05 22:00:00', $utc );
		$this->assertTrue( $utc_date != $parsed_wrong_timezone );
		
		//$est should be ignored as format contains timezone reference 
		//eo_check_datetime doesn't support \T or \Z in php5.2
		if( version_compare( phpversion(), '5.3.0', '>=' ) ){
			$parsed_as_utc = eo_check_datetime( 'Y-m-d\TH:i:s\Z', '2013-12-05T22:00:00Z', $est );
			$expected = new DateTime( '2013-12-05 22:00:00', $utc );
			//$this->assertTrue( $expected == $parsed_as_utc );
		}
		
	}
		
	public function testCheckDatetimeLocale(){
		
		$date = new DateTime( '2013-12-31' );
		$date1 = new DateTime( '2013-12-31 4:30pm' );

		//Set locale
		$original = $this->setLocale();
		$this->setLocale( 'ru_RU' );
			
		//Run test
		$this->assertEquals( $date1, eo_check_datetime( 'Y-m-d g:ia', '2013-12-31 4:30пп' ) );

		//eo_check_datetime doesn't support S in php5.2
		if( version_compare( phpversion(), '5.3.0', '>=' ) ){
			$this->assertEquals( $date1, eo_check_datetime( 'jS F Y g:ia', '31st December 2013 4:30пп' ) );
		}
		
		$this->assertEquals( $date1, eo_check_datetime( 'j F Y g:ia', '31 December 2013 4:30пп' ) );
		
		//Reset locale
		$this->setLocale( $original );		
	}
	
	public function testCheckDatetimeLocaleWithoutMeridian(){

		$date = new DateTime( '2013-12-31 4:30pm' );
	
		//Set locale
		$original = $this->setLocale();
		$this->setLocale( 'ru_RU' );
			
		//Run test
		$this->assertEquals( $date, eo_check_datetime( 'Y-m-d g:ia', '2013-12-31 4:30pm' ) );
	
		//eo_check_datetime doesn't support S in php5.2
		if( version_compare( phpversion(), '5.3.0', '>=' ) ){
			$this->assertEquals( $date, eo_check_datetime( 'jS F Y g:ia', '31st December 2013 4:30пп' ) );
		}
		
		$this->assertEquals( $date, eo_check_datetime( 'j F Y g:ia', '31 December 2013 4:30pm' ) );
	
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

		unload_textdomain( 'default' );

		load_textdomain( 'default', $location );
		$wp_locale->init();
		//Explicilty set text_decoration as -src versions are set to ltr, see WP_Locale::init().
		$wp_locale->text_direction = _x( 'ltr', 'text direction' );
		
		$current_locale = $locale;
		
		return $locale;
	}
}

