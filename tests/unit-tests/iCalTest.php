<?php

class iCalTest extends PHPUnit_Framework_TestCase
{

	public function testSingleEvent(){
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/singleEvent.ics' );
	
		$expected_start = new DateTime( '2013-10-28 19:26:00' );
		$expected_end = new DateTime( '2013-10-28 20:26:00' );
		$expected_until = new DateTime( '2013-10-31 00:00:00' );
	
		$event = $ical->events[0];
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertArrayNotHasKey( 'schedule', $event );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
		$this->assertArrayNotHasKey( 'schedule_last', $event );
	}
	
	public function testDailyEvent(){
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/dailyEvent.ics' );
		
		$expected_start = new DateTime( '2013-10-01 00:00:00' );
		$expected_end = new DateTime( '2013-10-01 23:59:59' );
		$expected_until = new DateTime( '2013-10-31 00:00:00' );
		
		$event = $ical->events[0];
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertEquals( 'daily', $event['schedule'] );
		$this->assertEquals( 3, $event['frequency'] );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
		$this->assertEquals( $expected_until, $event['schedule_last'] );
	}
	
	public function testWeeklyEvent(){
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/weeklyEvent.ics' );
		
		$expected_start = new DateTime( '2012-12-18 16:30:00' );
		$expected_end = new DateTime( '2012-12-18 17:30:00' );
		$expected_until = new DateTime( '2016-12-27 16:30:00' );
		
		$event = $ical->events[0];
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertEquals( 'weekly', $event['schedule'] );
		$this->assertEquals( array( 'TU' ), $event['schedule_meta'] );
		$this->assertEquals( 2, $event['frequency'] );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
		$this->assertEquals( $expected_until, $event['schedule_last'] );
	}
		
	public function testMonthlyByDayEvent(){
		
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/monthlyByDayEvent.ics' );
		
		$expected_start = new DateTime( '2012-12-27 00:00:00' );
		$expected_end = new DateTime( '2012-12-27 23:59:59' );
		$expected_until = new DateTime( '2017-12-28 00:00:00' );
		
		$event = $ical->events[0];
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertEquals( 'monthly', $event['schedule'] );
		$this->assertEquals( 2, $event['frequency'] );
		$this->assertEquals( 'BYDAY=4TH', $event['schedule_meta'] );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
		$this->assertEquals( $expected_until, $event['schedule_last'] );
		
	}
	
	public function testMonthlyByMonthDayEvent(){
	
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/monthlyByMonthDayEvent.ics' );
		
		$expected_start = new DateTime( '2012-12-27 00:00:00' );
		$expected_end = new DateTime( '2012-12-27 23:59:59' );
		$expected_until = new DateTime( '2017-12-28 00:00:00' );
		
		$event = $ical->events[0];
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertEquals( 'monthly', $event['schedule'] );
		$this->assertEquals( 2, $event['frequency'] );
		$this->assertEquals( 'BYMONTHDAY=27', $event['schedule_meta'] );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
		$this->assertEquals( $expected_until, $event['schedule_last'] );
	}
	
	public function testYearlyEvent(){
	
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/yearlyEvent.ics' );
		
		$expected_start = new DateTime( '2012-12-24 23:55:00' );
		$expected_end = new DateTime( '2012-12-24 23:59:00' );
		$expected_until = new DateTime( '2017-12-24 23:55:00' );
		
		$event = $ical->events[0];
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertEquals( 'yearly', $event['schedule'] );
		$this->assertEquals( 1, $event['frequency'] );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
		$this->assertEquals( $expected_until, $event['schedule_last'] );
	
	}
	
	public function testEventWithExcludes(){
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithExcludes.ics' );
		
		$expected_start = new DateTime( '2013-06-09 00:00:00' );
		$expected_end = new DateTime( '2013-06-09 18:39:00' );
		$expected_until = new DateTime( '2017-08-13 17:39:00' );
		$expected_includes = array( new DateTime( '2013-06-02' ), new DateTime( '2013-06-16' ) );
		$expected_excludes = array( new DateTime( '2013-07-14' ) );
		
		$event = $ical->events[0];
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertEquals( 'monthly', $event['schedule'] );
		$this->assertEquals( 1, $event['frequency'] );
		$this->assertEquals( 'BYDAY=2SU', $event['schedule_meta'] );
		$this->assertEquals( $expected_includes, $event['include'] );
		$this->assertEquals( $expected_excludes, $event['exclude'] );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
		$this->assertEquals( $expected_until, $event['schedule_last'] );
	}
	
	public function testEventWithIncludes(){
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithIncludes.ics' );
		
		$expected_start = new DateTime( '2013-06-05 13:25:00' );
		$expected_end = new DateTime( '2013-06-05 14:25:00' );
		$expected_includes = array( new DateTime( '2013-06-07' ), new DateTime( '2013-06-28' ), new DateTime( '2013-06-26' ), new DateTime( '2013-07-05' ) );
		
		$event = $ical->events[0];
		
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		//$this->assertEquals( 'custom', $event['schedule'] );
		//$this->assertEquals( 1, $event['frequency'] );
		$this->assertEquals( $expected_includes, $event['include'] );
		$this->assertArrayNotHasKey( 'exclude', $event );
		$this->assertEquals( $expected_start, $event['start'] );
		$this->assertEquals( $expected_end, $event['end'] );
	} 	
	
	
    public function testForeignAllDayEvent()
    {
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/foreignAllDayEvent.ics' );

		//Check the number of events have imported correctly
		$this->assertEquals( 1, count( $ical->events ) );

		//No errors / warnings
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		
		//Check the event
		$event = $ical->events[0];
		$this->assertInstanceOf('DateTime', $event['start']);
		$this->assertInstanceOf('DateTime', $event['end']);
		$this->assertEquals( $event['all_day'],  1 );
		$this->assertEquals( $event['start']->format( 'Y-m-d H:i' ),  '2013-09-20 00:00' );
		$this->assertEquals( $event['end']->format( 'Y-m-d H:i' ),  '2013-09-20 23:59' );

		$this->assertEquals( $event['start']->getTimezone(), eo_get_blog_timezone() );
		$this->assertEquals( $event['end']->getTimezone(), eo_get_blog_timezone() );
    }

    public function testForeignPartDayEvent()
    {
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/foreignPartDayEvent.ics' );

		//Timezone from the iCal feed
		$tz = new DateTimeZone( 'America/Toronto' );
		
		//Check the number of events have imported correctly
		$this->assertEquals( count( $ical->events ),  1 );

		//No errors / warnings
		$this->assertEquals( 0, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		
		//Check the event
		$event = $ical->events[0];
		$this->assertInstanceOf('DateTime', $event['start']);
		$this->assertInstanceOf('DateTime', $event['end']);
		$this->assertEquals( 0, $event['all_day'] );
		$this->assertEquals( $event['start']->format( 'Y-m-d H:i' ),  '2013-09-20 08:30' );
		$this->assertEquals( $event['end']->format( 'Y-m-d H:i' ),  '2013-09-20 15:30' );

		$this->assertEquals( $event['start']->getTimezone(), $tz );
		$this->assertEquals( $event['end']->getTimezone(), $tz );
    }

    public function testUnknownTimezone()
    {
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/unknownTimeZone.ics' );

		$tz = eo_get_blog_timezone();

		//Check the number of events have imported correctly
		$this->assertEquals( count( $ical->events ),  1 );

		//Test errors / warnings
		$this->assertEquals( 2, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		
		//Check the warning is reported:
		$code = $ical->warnings[0]->get_error_code();
		$message = $ical->warnings[0]->get_error_message( $code );
		$this->assertEquals( 'timezone-parser-warning', $code );
		$this->assertEquals( '[Line 9] Unknown timezone "Some/Unknown/Timezone" interpreted as "UTC".', $message );
	
		//Check that the timezone has "fallen back" to the blog
		$event = $ical->events[0];
		$this->assertInstanceOf('DateTime', $event['start']);
		$this->assertInstanceOf('DateTime', $event['end']);
		$this->assertEquals( $event['start']->getTimezone(), $tz );
		$this->assertEquals( $event['end']->getTimezone(), $tz );
    }
    
    public function testUnknownTimeFormat()
    {
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/unknownTimeFormat.ics' );
    
    	$tz = eo_get_blog_timezone();
    
    	//Check the number of events have imported correctly
    	$this->assertEquals( 0, count( $ical->events ) );
    	
    	$this->assertEquals( 0, count( $ical->warnings ) );
    	
    	$this->assertEquals( 1, count( $ical->errors ) );
    }

    public function testIndefinitelyRecurringEvent()
    {
		$ical = new EO_ICAL_Parser();
		$ical->parse( EO_DIR_TESTDATA . '/ical/indefinitelyRecurringEvent.ics' );

		$tz = eo_get_blog_timezone();

		//Check the number of events have imported correctly
		$this->assertEquals( 1, count( $ical->events ) );
		
		//Check the warning is reported:
		//No errors / warnings
		$this->assertEquals( 1, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		$this->assertEquals( 'indefinitely-recurring-event', $ical->warnings[0]->get_error_code() );

		//Check that the timezone has "fallen back" to the blog
		$event = $ical->events[0];
		$this->assertInstanceOf('DateTime', $event['schedule_last']);
		$this->assertEquals( new DateTime( '2038-01-19 00:00:00' ), $event['schedule_last'] );
    }
    
    public function testRecurringEventWithCount()
    {
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/recurringEventWithCount.ics' );
    
    	$tz = eo_get_blog_timezone();
    
    	//Check the number of events have imported correctly
    	$this->assertEquals( count( $ical->events ),  1 );
    	
    	//No errors / warnings
    	$this->assertEquals( 0, count( $ical->warnings ) );
    	$this->assertEquals( 0, count( $ical->errors ) );
    
    	//Check that the timezone has "fallen back" to the blog
    	$event = $ical->events[0];
    	$this->assertEquals( 4, $event['number_occurrences'] );
    }

    public function testMonthlyEventWithUnsupportedBYDAY()
    {
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/monthlyEventWithUnsupportedBYDAY.ics' );
    
    	//Check the number of events have imported correctly
    	$this->assertEquals( 1, count( $ical->events ) );
    	
    	//Check the warning is reported:
    	$this->assertEquals( 1, count( $ical->warnings ) );
    	$this->assertEquals( 0, count( $ical->errors ) );
    	$this->assertEquals( 'unsupported-recurrence-rule', $ical->warnings[0]->get_error_code() );
    	
    	//Check event
    	$event = $ical->events[0];
    	$this->assertEquals( 'monthly', $event['schedule'] );
    	$this->assertEquals( 'BYDAY=1SU', $event['schedule_meta'] );
    	$this->assertEquals( 2, $event['frequency'] );
    	$this->assertEquals( 10, $event['number_occurrences'] );    	 
    }
    
    public function testMonthlyEventWithUnsupportedBYMONTHDAY()
    {
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/monthlyEventWithUnsupportedBYMONTHDAY.ics' );
    
    	//Check the number of events have imported correctly
    	$this->assertEquals( 1, count( $ical->events ) );
    	 
    	//Check the warning is reported:
    	//No errors / warnings
    	$this->assertEquals( 1, count( $ical->warnings ) );
    	$this->assertEquals( 0, count( $ical->errors ) );
    	$this->assertEquals( 'unsupported-recurrence-rule', $ical->warnings[0]->get_error_code() );
    	 
    	//Check event
    	$event = $ical->events[0];
    	$this->assertEquals( 'monthly', $event['schedule'] );
    	$this->assertEquals( 'BYMONTHDAY=20', $event['schedule_meta'] );
    	$this->assertEquals( 2, $event['frequency'] );
    	$this->assertEquals( 10, $event['number_occurrences'] );
    }
    
    public function testMonthlyEventWithUnsupportedRrule()
    {
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/testMonthlyEventWithUnsupportedRrule.ics' );
    
    	//Check the number of events have imported correctly
		$this->assertEquals( 1, count( $ical->events ) );
		
		//No errors / warnings
		$this->assertEquals( 1, count( $ical->warnings ) );
		$this->assertEquals( 0, count( $ical->errors ) );
		
		//Check the warning is reported:
		$this->assertEquals( 'unsupported-recurrence-rule', $ical->warnings[0]->get_error_code() );
		
    }
    
    
    public function testTimeZoneParsing()
    {
    	$ical = new EO_ICAL_Parser();
    	
    	$tzid = 'GMT';
    	$expected = new DateTimeZone( 'UTC' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	$tzid = "(GMT+01.00) Amsterdam / Berlin / Bern / Rome / Stockholm / Vienna";
    	$expected = new DateTimeZone( 'Europe/Amsterdam' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	$tzid = "America-New_York";
    	$expected = new DateTimeZone( 'America/New_York' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	//Fallback
    	$tzid = "This should fallback to blog timezone";
    	$expected = eo_get_blog_timezone();
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	///mozilla.org/20070129_1/Europe/Berlin
    	$tzid = "/mozilla.org/20070129_1/Europe/Berlin";
    	$expected = new DateTimeZone( 'Europe/Berlin' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	//(GMT +01:00)
    	$tzid = "(GMT +04:00)";
    	$expected = new DateTimeZone( 'Asia/Baghdad' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );

    	$tzid = "(GMT +00:00)";
    	$expected = new DateTimeZone( 'UTC' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	$tzid = "(GMT -04:00)";
    	$expected = new DateTimeZone( 'America/Porto_Acre' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	//Checking TZIDs within quotes
    	$tzid = '"Africa-Algiers"';
    	$expected = new DateTimeZone( 'Africa/Algiers' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	
    	$tzid = "'Africa-Algiers'";
    	$expected = new DateTimeZone( 'Africa/Algiers' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	 
    }
    
    
    public function testTimeZoneFilter()
    {
    	$ical = new EO_ICAL_Parser();
    	     	 
    	$tzid = "Something-we'd like to think was America New York";
    	add_filter( 'eventorganiser_ical_timezone', array( $this, 'returnNewYorkTimeZone' ) );
    	$expected = new DateTimeZone( 'America/New_York' );
    	$tz = $ical->parse_timezone( $tzid );
    	$this->assertEquals( $expected->getName(), $tz->getName() );
    	remove_filter( 'eventorganiser_ical_timezone', array( $this, 'returnNewYorkTimeZone' ) );
    	 
    }
    
    public function returnNewYorkTimeZone( $tz ){
    	return new DateTimeZone( 'America/New_York' );
    }
     
    public function testEventWithGeoTag()
    {
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithGeoTag.ics' );
    
    	//Check the number of events have imported correctly
    	$this->assertEquals( 1, count( $ical->events ) );

    	//No errors / warnings
    	$this->assertEquals( 0, count( $ical->warnings ) );
    	$this->assertEquals( 0, count( $ical->errors ) );
    	
    	$this->assertEquals( 37.386013, $ical->venue_meta['Unit Test Venue with GEO']['latitude'] );
    	$this->assertEquals( -122.082932, $ical->venue_meta['Unit Test Venue with GEO']['longtitude'] );
    }
    
   
    function testEventWithoutUID(){
    	
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithoutUID.ics' );
    	
    	//Check the number of events have imported correctly
    	$this->assertEquals( 1, count( $ical->warnings ) );
    	$this->assertEquals( 0, count( $ical->errors ) );

    	$code = $ical->warnings[0]->get_error_code();
    	$message = $ical->warnings[0]->get_error_message( $code );
    	$this->assertEquals( 'event-no-uid', $code );
    	$this->assertEquals( '[Lines 8-17] Event does not have a unique identifier (UID) property.', $message );
    }

    function testEventWithAlarm(){
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithAlarm.ics' );
    	
    	$event = $ical->events[0];
    	$this->assertEquals( 'Event description', $event['post_content'] );  	
    }
    

    function testEventWithLongDescription(){
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithLongDescription.ics' );
    	
    	$description = "This event has a really long description and goes over the prescribed limit for iCal feeds. Notice the space at the beginning of this line This is a mid-word break; However this line has a space at the end of the line, and it shouldn't be stripped!";
    	 
    	$event = $ical->events[0];
    	$this->assertEquals( $description, $event['post_content'] );
    }
    
    
    function testEventWithAltDescription(){
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithAltDescription.ics' );
    	$event = $ical->events[0];
    	$this->assertEquals( "<strong>This</strong> is<em>a</em> description.", $event['post_content'] );
    }
    
    function testEventWithAltDescriptionNotParsingHtml(){
    	$ical = new EO_ICAL_Parser();
    	$ical->parse_html = false;
    	$ical->parse( EO_DIR_TESTDATA . '/ical/eventWithAltDescription.ics' );
    	$event = $ical->events[0];
    	$this->assertEquals( "This is a description.", $event['post_content'] );
    }
    


	//@TODO
    public function testPartDayForeignRecurringEvent()
    {

    	//@see testPartDayForeignWeeklyEvent()
    	//This shouldn't be fixed here - but in eo_insert/update_event
    	
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/foreignPartDayRecurringEvent.ics' );

    	//Check the number of events have imported correctly
    	//$this->assertEquals( "BYMONTHDAY=4", $ical->events[0]['schedule_meta'] );
    }
    
	//@TODO
    public function testPartDayForeignWeeklyEvent()
    {
    	//A Monday 10pm event in New York would be a Tuesday event in UTC
    	//This shouldn't be fixed here - but in eo_insert/update_event
    	
    	$ical = new EO_ICAL_Parser();
    	$ical->parse( EO_DIR_TESTDATA . '/ical/foreignPartDayWeeklyEvent.ics' );
    
    	//Check the number of events have imported correctly
    	//$this->assertEquals( array( 'TU' ), $ical->events[0]['schedule_meta'] );
    }   
    
}

