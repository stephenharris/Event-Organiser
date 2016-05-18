<?php

class dateFormatTest extends PHPUnit_Framework_TestCase
{

	public function setUp(){
		parent::setUp();
		$this->original_locale = $this->setLocale();
	}

	public function tearDown(){
		$this->setLocale( $this->original_locale );
		parent::tearDown();
	}

	public function testDateFormat() {
		$date = new DateTime( '2016-05-17 18:43' );
		$this->assertEquals( 'Tuesday 17th May 2016', eo_format_datetime( $date, 'l jS F Y' ) );
	}

	public function testDateFormatAll() {
		$date = new DateTime( '2016-08-07 18:43', new DateTimeZone( 'Europe/London' ) );

		$this->assertEquals( '07 Sun 7th Sunday 7 0 219', eo_format_datetime( $date, 'd D jS l N w z' ) );
		$this->assertEquals( '31', eo_format_datetime( $date, 'W' ) );
		$this->assertEquals( 'August 08 Aug 8 31', eo_format_datetime( $date, 'F m M n t' ) );
		$this->assertEquals( '1 2016 2016 16', eo_format_datetime( $date, 'L o Y y' ) );
		$this->assertEquals( 'pm PM 6 18 06 18 43 00', eo_format_datetime( $date, 'a A g G h H i s' ) );
		$this->assertEquals( 'Europe/London 1 +0100 +01:00 BST 3600', eo_format_datetime( $date, 'e I O P T Z' ) );
		$this->assertEquals( '2016-08-07T18:43:00+01:00 Sun, 07 Aug 2016 18:43:00 +0100 1470591780', eo_format_datetime( $date, 'c r U' ) );
	}

	public function testDateFormatAllLocale() {
		$this->markTestIncomplete( 'This test fails on some WP installs. Probably due to an out-of sync .mo file in the test data' );
		$date = new DateTime( '2016-08-07 18:43', new DateTimeZone( 'Europe/London' ) );
		$this->setLocale( 'fr_FR' );
		$this->assertEquals( '07 dim 7th dimanche 7 0 219', eo_format_datetime( $date, 'd D jS l N w z' ) );
		$this->assertEquals( '31', eo_format_datetime( $date, 'W' ) );
		$this->assertEquals( 'août 08 Août 8 31', eo_format_datetime( $date, 'F m M n t' ) );
		$this->assertEquals( '1 2016 2016 16', eo_format_datetime( $date, 'L o Y y' ) );
		$this->assertEquals( 'pm PM 6 18 06 18 43 00', eo_format_datetime( $date, 'a A g G h H i s' ) );
		$this->assertEquals( 'Europe/London 1 +0100 +01:00 BST 3600', eo_format_datetime( $date, 'e I O P T Z' ) );
		$this->assertEquals( '2016-08-07T18:43:00+01:00 Sun, 07 Aug 2016 18:43:00 +0100 1470591780', eo_format_datetime( $date, 'c r U' ) );
	}

	public function testDateFormatLocale() {
		//Set locale
		$this->setLocale( 'ru_RU' );
		$date = new DateTime( '2016-05-17 18:43' );
		$this->assertEquals( 'Вторник 17th Май 2016', eo_format_datetime( $date, 'l jS F Y' ) );
	}

	public function testDateFormatLocaleRTL() {
		//Set locale
		$this->setLocale( 'he_IL' );
		$date = new DateTime( '2016-05-17 18:43' );
		$this->assertEquals( 'יום שלישי 17th מאי 2016', eo_format_datetime( $date, 'l jS F Y' ) );
	}

	public function testDateFormatLocaleWithNoAMoPM() {
		//Set locale
		$this->setLocale( 'fr_FR' );
		$date = new DateTime( '2016-05-17 18:43' );
		$this->assertEquals( 'mardi 17th mai 2016 6:43pm', eo_format_datetime( $date, 'l jS F Y g:ia' ) );
	}

	public function testDateFormatNull() {
		try{
			eo_format_datetime( null, 'l jS F Y' );
			$this->fail( 'Exception expected. No exception thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Exception', get_class( $e ), 'Failed asserting exception of Exception type' );
			$this->assertEquals( 'Error in formating DateTime object. Expected DateTime, but instead given NULL', $e->getMessage() );
		}
	}

	public function testDateFormatFalse() {
		try{
			eo_format_datetime( false, 'l jS F Y' );
			$this->fail( 'Exception expected. No exception thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Exception', get_class( $e ), 'Failed asserting exception of Exception type' );
			$this->assertEquals( 'Error in formating DateTime object. Expected DateTime, but instead given boolean', $e->getMessage() );
		}
	}

	public function testDateFormatString() {
		try{
			eo_format_datetime( '', 'l jS F Y' );
			$this->fail( 'Exception expected. No exception thrown' );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Exception', get_class( $e ), 'Failed asserting exception of Exception type' );
			$this->assertEquals( 'Error in formating DateTime object. Expected DateTime, but instead given string', $e->getMessage() );
		}
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

