<?php

class fullcalendarTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Testing the deprecated and new API for specifying categories with  eo_get_event_fullcalendar()
	 */
    public function testCatAttributesDeprecated()
    {
    	eo_get_event_fullcalendar( array(
    		'event_category' => 'foo,bar', //This is the "old" way, it should take precedence over the format
    		'event-category' => array( 'hello', 'world' )
    	));
    	
    	$args = array_pop( EventOrganiser_Shortcodes::$calendars );
	$this->assertEquals( 'foo,bar',  $args['event_category'] );
		
	//Reset
	EventOrganiser_Shortcodes::$calendars = array();
    }
    
    public function testCatAttributes()
    {
    	eo_get_event_fullcalendar( array(
    		'event-category' => array( 'foo', 'bar' )
    	));
    	 
    	$args = array_pop( EventOrganiser_Shortcodes::$calendars );
    	$this->assertEquals( 'foo,bar',  $args['event_category'] );
    
    	//Reset
    	EventOrganiser_Shortcodes::$calendars = array();
    }
    
    public function testCatAttributesShortcode()
    {	
    	do_shortcode( '[eo_fullcalendar event_category="foo,bar"]' );
    	
    	$args = array_pop( EventOrganiser_Shortcodes::$calendars );
    	$this->assertEquals( 'foo,bar',  $args['event_category'] );
    	
    	//Reset
    	EventOrganiser_Shortcodes::$calendars = array();
    } 
}
