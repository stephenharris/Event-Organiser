<?php

class utilityFunctionsTest extends WP_UnitTestCase
{
	
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
	
		$this->assertEquals( $date->modify( $modify ), $date3 );
	
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
	/**
	 * TODO eo_get_blog_timezone(): Why does +10 give Asia/Choibalsan timezone.
	public function testEoGetBlogTimezone()
	{
		$tz = ini_get('date.timezone');
		$original_tz = get_option( 'timezone_string' );
		$original_offset = get_option( 'gmt_offset' );
		
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 10 );
		$tz = eo_get_blog_timezone();
		var_dump( $tz );
		$now = new DateTime( 'now', eo_get_blog_timezone() );
		var_dump($now->format('Y-m-d H:i:s'));
		
		update_option( 'timezone_string', $original_tz );
		update_option( 'gmt_offset', $original_offset );
		
		$this->assertTrue(false);
		wp_die('xx');
		
	}
		 */
}

