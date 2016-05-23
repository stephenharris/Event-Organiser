<?php

class dateFormatRangeTest extends PHPUnit_Framework_TestCase
{

	public function testDateRangeFormat(){
		
		$datetime1 = new DateTime( '2015-02-16 13:30:00' );
		$datetime2 = new DateTime( '2015-02-21 14:30:00' );
		$this->assertEquals( '16th February 2015 1:30pm-21st February 2015 2:30pm', eo_format_datetime_range( $datetime1, $datetime2, 'jS F Y g:ia', '-' ) );
		
		$datetime1 = new DateTime( '2015-02-16' );
		$datetime2 = new DateTime( '2015-02-17' );
		$this->assertEquals( '16th-17th February, 2015', eo_format_datetime_range( $datetime1, $datetime2, 'jS F, Y', '-' ) );
		
		$datetime1 = new DateTime( '2015-02-16' );
		$datetime2 = new DateTime( '2015-03-16' );
		$this->assertEquals( '16th February - 16th March, 2015', eo_format_datetime_range( $datetime1, $datetime2, 'jS F, Y', ' - ' ) );
		
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
	
	/**
	 * @see https://github.com/stephenharris/Event-Organiser/issues/359
	 */
	public function testDateSameDateDifferentMonth() {
	
		$datetime1 = new DateTime( '2016-04-09' );
		$datetime2 = new DateTime( '2016-05-09' );
		$this->assertEquals( 'Saturday, April 9–Monday, May 9, 2016', eo_format_datetime_range( $datetime1, $datetime2, 'l, F j, Y', '–' ) );
		
		$datetime1 = new DateTime( '2015-02-11' );
		$datetime2 = new DateTime( '2015-03-11' );
		$this->assertEquals( 'Wednesday, February 11–Wednesday, March 11, 2015', eo_format_datetime_range( $datetime1, $datetime2, 'l, F j, Y', '–' ) );
		
		$datetime1 = new DateTime( '2015-02-11' );
		$datetime2 = new DateTime( '2015-02-18' );
		$this->assertEquals( 'Wednesday 11th – Wednesday 18th, February', eo_format_datetime_range( $datetime1, $datetime2, 'l jS, F', ' – ' ) );
	}
	
	public function testDateRangeFormatTime(){
		
		$datetime1 = new DateTime( '2015-02-16 13:30:00' );
		$datetime2 = new DateTime( '2015-02-16 14:30:00' );
		//1:30pm-2:30pm not 1-2:30pm
		$this->assertEquals( '16th February 1:30pm-2:30pm', eo_format_datetime_range( $datetime1, $datetime2, 'jS F g:ia', '-' ) );
	}
	
	public function testDateRangeFormatLocale(){
	
		//Set locale
		$original = $this->setLocale();
		$this->setLocale( 'he_IL' );

		$datetime1 = new DateTime( '2015-02-16' );
		$datetime2 = new DateTime( '2015-02-17' );
		$this->assertEquals( '2015 ' . __( 'February', 'default' ) . ' 17-16', eo_format_datetime_range( $datetime1, $datetime2, 'Y F j', '-' ) );
			
		//Reset locale
		$this->setLocale( $original );
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

