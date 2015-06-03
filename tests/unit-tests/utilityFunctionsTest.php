<?php

class utilityFunctionsTest extends WP_UnitTestCase
{
	
	public function testPHPToMoment()
	{
		//Days
		$this->assertEquals( "DD", eo_php_to_moment( "d" ) );
		$this->assertEquals( "ddd", eo_php_to_moment( "D" ) );
		$this->assertEquals( "D", eo_php_to_moment( "j" ) );
		$this->assertEquals( "Do", eo_php_to_moment( "jS" ) );
				
		//Week
		$this->assertEquals( "w", eo_php_to_moment( "W" ) );
		
		//Month
		$this->assertEquals( "MMMM", eo_php_to_moment( "F" ) );
		$this->assertEquals( "MM", eo_php_to_moment( "m" ) );
		$this->assertEquals( "MMM", eo_php_to_moment( "M" ) );
		$this->assertEquals( "M", eo_php_to_moment( "n" ) );
		
		//Year
		$this->assertEquals( "YYYY", eo_php_to_moment( "Y" ) );
		$this->assertEquals( "YY", eo_php_to_moment( "y" ) );
		$this->assertEquals( "gggg", eo_php_to_moment( "o" ) );
		
		//Time
		$this->assertEquals( "a", eo_php_to_moment( "a" ) );
		$this->assertEquals( "A", eo_php_to_moment( "A" ) );
		$this->assertEquals( "h", eo_php_to_moment( "g" ) );
		$this->assertEquals( "H", eo_php_to_moment( "G" ) );
		$this->assertEquals( "hh", eo_php_to_moment( "h" ) );
		$this->assertEquals( "HH", eo_php_to_moment( "H" ) );
		$this->assertEquals( "mm", eo_php_to_moment( "i" ) );
		$this->assertEquals( "ss", eo_php_to_moment( "s" ) );
		
		$this->assertEquals( "YYYY-MM-DD[T]HH:mm:ssZ", eo_php_to_moment( "c" ) );
		
		//Escaping characters
		$this->assertEquals( "[d][a][y]", eo_php_to_moment( "\d\a\y" ) );

	}
	
	public function testPHP2Xdate()
	{

		//Days
		$this->assertEquals( "dd", eo_php2xdate( "d" ) );
		$this->assertEquals( "ddd", eo_php2xdate( "D" ) );
		$this->assertEquals( "d", eo_php2xdate( "j" ) );
		$this->assertEquals( "S", eo_php2xdate( "S" ) );
		
		//Week
		$this->assertEquals( "w", eo_php2xdate( "W" ) );
		
		//Month
		$this->assertEquals( "MMMM", eo_php2xdate( "F" ) );
		$this->assertEquals( "MM", eo_php2xdate( "m" ) );
		$this->assertEquals( "MMM", eo_php2xdate( "M" ) );
		$this->assertEquals( "M", eo_php2xdate( "n" ) );
		
		//Year
		$this->assertEquals( "yyyy", eo_php2xdate( "Y" ) );
		$this->assertEquals( "yy", eo_php2xdate( "y" ) );
		$this->assertEquals( "I", eo_php2xdate( "o" ) );

		//Time
		$this->assertEquals( "tt", eo_php2xdate( "a" ) );
		$this->assertEquals( "TT", eo_php2xdate( "A" ) );
		$this->assertEquals( "h", eo_php2xdate( "g" ) );
		$this->assertEquals( "H", eo_php2xdate( "G" ) );
		$this->assertEquals( "hh", eo_php2xdate( "h" ) );
		$this->assertEquals( "HH", eo_php2xdate( "H" ) );
		$this->assertEquals( "mm", eo_php2xdate( "i" ) );
		$this->assertEquals( "ss", eo_php2xdate( "s" ) );
		
		$this->assertEquals( "u", eo_php2xdate( "c" ) );
		
	}
	
	public function testPHP2jQuerydate()
	{

		//1 January 2015
		$this->assertEquals( "d MM yy", eo_php2jquerydate( "j F Y" ) );
		
		//01-01-2015
		$this->assertEquals( "dd-mm-yy", eo_php2jquerydate( "d-m-Y" ) );
		
		//Thur 1 15
		$this->assertEquals( "D m y", eo_php2jquerydate( "D n y" ) );
		
		//Thursday Jan 15
		$this->assertEquals( "DD M y", eo_php2jquerydate( "l M y" ) );
		
		$this->assertEquals( "@", eo_php2jquerydate( "U" ) );
	
	}

	public function testRemoveDuplicates()
	{
		$array = array(
			new DateTime( '2013-01-01 15:00' ),
			new DateTime( '2013-01-01 15:00' ),
			new DateTime( "2013-12-05 22:00:00", new DateTimeZone( 'America/New_York' ) ),
			new DateTime( "2013-12-06 03:00:00", new DateTimeZone( 'UTC' ) ),
			new DateTime( '2013-12-07 15:00' )
		);
		
		$array_without_duplicates = array(
			0 => new DateTime( '2013-01-01 15:00' ),
			2 => new DateTime( "2013-12-05 22:00:00", new DateTimeZone( 'America/New_York' ) ),
			4 => new DateTime( '2013-12-07 15:00' ),
		);
		
		$this->assertEquals( $array_without_duplicates, _eventorganiser_remove_duplicates( $array ) );
	}
	
	public function testRemoveDuplicatesAssociative()
	{
		$array = array(
			'hello' => new DateTime( '2013-01-01 15:00' ),
			'foo' => new DateTime( '2013-01-02 15:00' ),
			'bar' => new DateTime( '2013-01-01 15:00' ),
		);
	
		$array_without_duplicates = array(
			'hello' => new DateTime( '2013-01-01 15:00' ),
			'foo' => new DateTime( '2013-01-02 15:00' ),
		);
		
		$this->assertEquals( $array_without_duplicates, _eventorganiser_remove_duplicates( $array ) );
	}

	public function testModifyDatePHP52()
	{
		$modify = 'first Monday of +1 Month';
		$date = new DateTime( '2014-01-30' );
		$date2 = new DateTime( '2014-01-30' );
		$date3 = _eventorganiser_php52_modify( $date2, $modify );
	
		$expected = new DateTime( '2014-02-03' );

		$this->assertEquals( $expected, $date3 );
	
	}
	
	public function testIsBlog24Hour()
	{
		//24 hour time formats
		update_option( 'time_format', 'G:i' );
		$this->assertTrue( eo_blog_is_24() );
		
		update_option( 'time_format', 'H:i' );
		$this->assertTrue( eo_blog_is_24() );
		
		//12 hour time formats
		update_option( 'time_format', 'g:i' );
		$this->assertFalse( eo_blog_is_24() );
		
		update_option( 'time_format', 'h:i' );
		$this->assertFalse( eo_blog_is_24() );
		
		update_option( 'time_format', 'ia' );
		$this->assertFalse( eo_blog_is_24() );
		
		//Filters
		add_filter( 'eventorganiser_blog_is_24', '__return_true' );
		$this->assertTrue( eo_blog_is_24() );
		remove_filter( 'eventorganiser_blog_is_24', '__return_true' );
		
		update_option( 'time_format', 'G:i' );
		add_filter( 'eventorganiser_blog_is_24', '__return_false' );
		$this->assertFalse( eo_blog_is_24() );
		remove_filter( 'eventorganiser_blog_is_24', '__return_false' );
			
	}

	public function testWhiteListArrayKey(){
		
		$array = array( 'a' => 1, 'b' => 2, 'c' => 3 );
		$whitelist = array( 'a', 'c' );
		$expected = array( 'a' => 1, 'c' => 3 );
		
		$this->assertEquals( $expected, eo_array_key_whitelist( $array, $whitelist ) );
	}
	
	public function testPluckKeyValue(){
		
		$list = array(
			array( 'id' => 1, 'value' => 'foo' ),
			array( 'id' => 2, 'value' => 'bar' ),
			array( 'id' => 3, 'value' => 'hello' ),
			array( 'id' => 4, 'value' => 'world' ),
		);
		
		$expected = array( 1 => 'foo', 2 => 'bar', 3 => 'hello', 4 => 'world' );
		$this->assertEquals( $expected, eo_list_pluck_key_value( $list, 'id', 'value' ) );
	}
	
	
	public function testCombineArraysAssoc(){
		
		$key_array = array(
			'colour_1' => 'Green',
			'colour_2' => 'Red',
			'colour_3' => 'Purple',
			'colour_4' => 'Blue',
		);
		
		$value_array = array(
			'colour_1' => 'Grass',
			'colour_2' => 'Bus',
			'colour_4' => 'Sky',
			'colour_5' => 'Cloud',
		);
		
		$expected = array(
			'Green' => 'Grass',
			'Red'   => 'Bus',
			'Blue'  => 'Sky',
		);
		
		$this->assertEquals( $expected, eo_array_combine_assoc( $key_array, $value_array ) );
	}
	
	/**
	 * date_diff can cause some unexpected behaviour. This is a simple workaround. 
	 * @see https://github.com/stephenharris/Event-Organiser/issues/205
	 */
	public function testDateDiffFallback()
	{
		
		$timezone = new DateTimeZone( 'Europe/London' );
		$date1 = new DateTime( '2014-07-01 00:00:00', $timezone );
		$date2 = new DateTime( '2014-07-31 23:59:00', $timezone );
		
		//Work around for PHP < 5.3. Also see 
		$seconds      = round( abs( $date1->format('U') - $date2->format('U') ) );
		$days         = floor( $seconds/86400 );// 86400 = 60*60*24 seconds in a normal day
		$sec_diff     = $seconds - $days*86400;
		
		$this->assertEquals( 30, $days );
		$this->assertEquals( 86340, $sec_diff);
		
	}	
	
	public function testDateDiffFallbackDST()
	{
		
		$timezone = new DateTimeZone( 'Europe/London' );
		$date1 = new DateTime( '2014-03-30 00:00:00', $timezone );
		$date2 = new DateTime( '2014-03-30 04:00:00', $timezone );
		
		//Work around for PHP < 5.3. Also see 
		$seconds      = round( abs( $date1->format('U') - $date2->format('U') ) );
		$days         = floor( $seconds/86400 );// 86400 = 60*60*24 seconds in a normal day
		$sec_diff     = $seconds - $days*86400;
		
		//$this->assertEquals( 3, $days );
		$this->assertEquals( 10800, $sec_diff);
		
		$date1 = new DateTime( '2014-10-26 00:00:00', $timezone );
		$date2 = new DateTime( '2014-10-26 04:00:00', $timezone );
		 
		$seconds      = round( abs( $date1->format('U') - $date2->format('U') ) );
		$days         = floor( $seconds/86400 );// 86400 = 60*60*24 seconds in a normal day
		$sec_diff     = $seconds - $days*86400;
		
		$this->assertEquals( 18000, $sec_diff);
		
	}	
	
	public function testCompareDates(){
		
		
		$date1 = new DateTime( '2014-08-13' );
		$date2 = new DateTime( '2014-08-14' );
		$date3 = new DateTime( '2014-08-13' );
		
		$this->assertEquals( 1, _eventorganiser_compare_dates( $date2, $date1 ) );
		$this->assertEquals( -1, _eventorganiser_compare_dates( $date1, $date2 ) );
		$this->assertEquals( 0, _eventorganiser_compare_dates( $date1, $date3 ) );
		
	}
	
	public function testCompareDateTimess(){
		
		
		$date1 = new DateTime( '2014-08-13 14:09:00' );
		$date2 = new DateTime( '2014-08-13 15:09:00' );
		$date3 = new DateTime( '2014-08-13 14:09:00' );
		
		$this->assertEquals( 1, _eventorganiser_compare_datetime( $date2, $date1 ) );
		$this->assertEquals( -1, _eventorganiser_compare_datetime( $date1, $date2 ) );
		$this->assertEquals( 0, _eventorganiser_compare_datetime( $date1, $date3 ) );
		
		
		//Lets check whenthe timezones differ
		$tz = new DateTimeZone( 'Etc/GMT-11' );
		
		$date1 = new DateTime( '2014-08-14 01:09:00', $tz );
		$date2 = new DateTime( '2014-08-13 15:09:00' );
		$date3 = new DateTime( '2014-08-13 14:09:00' );//same as $date1
		
		$this->assertEquals( 1, _eventorganiser_compare_datetime( $date2, $date1 ) );
		$this->assertEquals( -1, _eventorganiser_compare_datetime( $date1, $date2 ) );
		$this->assertEquals( 0, _eventorganiser_compare_datetime( $date1, $date3 ) );
		
	}
	
	function testDateIntervalDST(){

		$timezone = new DateTimeZone( 'Europe/Berlin' );
		$date1 = new DateTime( '2014-10-21 00:00:00', $timezone );
		$date2 = new DateTime( '2014-10-26 23:59:00', $timezone );

		//Check mod is correctly calculated
		$mod = eo_date_interval( $date1, $date2, '+%d days +%h hours +%i minutes +%s seconds' );
		$this->assertEquals( "+5 days +23 hours +59 minutes +0 seconds", $mod );
		
		//Check date modification by mod is as expected (over DST boundary)
		$date3 = clone $date1;
		$date3->modify( $mod );
		$this->assertEquals( "2014-10-26 23:59:00", $date3->format( 'Y-m-d H:i:s' ) );

		//Again with a new date (inside DST period)
		$date4 = new DateTime( '2014-09-21 00:00:00', $timezone );
		$date4->modify( $mod );
		$this->assertEquals( "2014-09-26 23:59:00", $date4->format( 'Y-m-d H:i:s' ) );
		
	}
	
	function testDateIntervalMonthOverflow(){

		$timezone = new DateTimeZone( 'Europe/Berlin' );
		$date1 = new DateTime( '2015-01-30 13:00:00', $timezone );
		$date2 = new DateTime( '2015-01-30 16:30:10', $timezone );

		//Check mod is correctly calculated
		$mod = eo_date_interval( $date1, $date2, '+%d days +%h hours +%i minutes +%s seconds' );
		$this->assertEquals( "+0 days +3 hours +30 minutes +10 seconds", $mod );
	}
	
	function testGetUserIDBy(){
		
		$user_id = $this->factory->user->create(array(
			'user_login' => 'theusername',
			'user_email' => 'theuser@example.com'
		));
		
		$this->assertEquals( $user_id, eo_get_user_id_by( 'id', $user_id ) );
		$this->assertEquals( $user_id, eo_get_user_id_by( 'slug', 'theusername' ) );
		$this->assertEquals( $user_id, eo_get_user_id_by( 'email', 'theuser@example.com' ) );
		
		$this->assertEquals( 0, eo_get_user_id_by( 'slug', 'doesnotexist' ) );
		
	}
	
	public function testEoGetBlogTimezoneOffset()
	{
		
		$original_tz     = get_option( 'timezone_string' );
		$original_offset = get_option( 'gmt_offset' );
		
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', '10' );
		$tz = eo_get_blog_timezone();
		$this->assertEquals( 'Etc/GMT-10', $tz->getName() );
		
		update_option( 'timezone_string', $original_tz );
		update_option( 'gmt_offset', $original_offset );		
	}

	public function testEoGetBlogTimezonePartialOffset()
	{
	
		$original_tz     = get_option( 'timezone_string' );
		$original_offset = get_option( 'gmt_offset' );
	
		update_option( 'gmt_offset', '10.5' );
		wp_cache_delete( 'eventorganiser_timezone' );
		$tz = eo_get_blog_timezone();
		$this->assertEquals( 'UTC', $tz->getName() );
	
		update_option( 'timezone_string', $original_tz );
		update_option( 'gmt_offset', $original_offset );
	}
}

