<?php
class templateTest extends WP_UnitTestCase
{

	public function testIsEventArchive(){
	
		$this->go_to("/?post_type=event");
		$this->assertTrue( eo_is_event_archive() );

	}
	
	public function testIsEventYearArchive(){
		
		$this->go_to("/?post_type=event&ondate=2014");
		$this->assertTrue( eo_is_event_archive( 'year' ) );
		$this->assertEquals( '2014-01-01', eo_get_event_archive_date( 'Y-m-d' ) );
	
		$this->go_to("/?post_type=event&ondate=2014abc");
		$this->assertFalse( eo_is_event_archive( 'year' ) );
	
		$this->go_to("/?post_type=event&ondate=abc2014");
		$this->assertFalse( eo_is_event_archive( 'year' ) );
	}

	public function testIsEventMonthArchive(){
		
		$this->go_to("/?post_type=event&ondate=2014-02");
		$this->assertTrue( eo_is_event_archive( 'month' ) );
		$this->assertEquals( '2014-02-01', eo_get_event_archive_date( 'Y-m-d' ) );
		
		$this->go_to("/?post_type=event&ondate=2014/01");
		$this->assertTrue( eo_is_event_archive( 'month' ) );
		
		$this->go_to("/?post_type=event&ondate=2014");
		$this->assertFalse( eo_is_event_archive( 'month' ) );
		
		$this->go_to("/?post_type=event&ondate=2014-01abc");
		$this->assertFalse( eo_is_event_archive( 'month' ) );
		
		$this->go_to("/?post_type=event&ondate=abc2014-01");
		$this->assertFalse( eo_is_event_archive( 'month' ) );
	
	}
	
	public function testIsEventDayArchive(){
	
		$this->go_to("/?post_type=event&ondate=2014-02-03");
		$this->assertTrue( eo_is_event_archive( 'day' ) );
		$this->assertEquals( '2014-02-03', eo_get_event_archive_date( 'Y-m-d' ) );
		
		$this->go_to("/?post_type=event&ondate=2014/01/01");
		$this->assertTrue( eo_is_event_archive( 'day' ) );
		
		$this->go_to("/?post_type=event&ondate=2014");
		$this->assertFalse( eo_is_event_archive( 'day' ) );
		
		$this->go_to("/?post_type=event&ondate=2014-01-01abc");
		$this->assertFalse( eo_is_event_archive( 'day' ) );
		
		$this->go_to("/?post_type=event&ondate=abc2014-01-01");
		$this->assertFalse( eo_is_event_archive( 'day' ) );
		
		$this->go_to("/?post_type=event&ondate=abc2014-02-29");
		$this->assertFalse( eo_is_event_archive( 'day' ) );
	}
	
	public function testIsEventTemplate(){
		
		$this->assertTrue( eventorganiser_is_event_template( 'single-event.php', 'event' ) );
		
		$this->assertTrue( eventorganiser_is_event_template( 'archive-event.php', 'archive' ) );
		
		$this->assertTrue( eventorganiser_is_event_template( 'taxonomy-event-venue.php', 'event-venue' ) );
		$this->assertTrue( eventorganiser_is_event_template( 'taxonomy-event-venue-myvenue.php', 'event-venue' ) );
		
		$this->assertTrue( eventorganiser_is_event_template( 'taxonomy-event-tag.php', 'event-tag' ) );
		$this->assertTrue( eventorganiser_is_event_template( 'taxonomy-event-tag-mytag.php', 'event-tag' ) );
		
		$this->assertTrue( eventorganiser_is_event_template( 'taxonomy-event-category.php', 'event-category' ) );
		$this->assertTrue( eventorganiser_is_event_template( 'taxonomy-event-category-mycategory.php', 'event-category' ) );
		
	}

	public function testEventArchiveLink(){

		$this->go_to( eo_get_event_archive_link( 2014, 03, 13 ) );
		$this->assertTrue( eo_is_event_archive( 'day' ) );
		$this->assertEquals( '2014-03-13', eo_get_event_archive_date( 'Y-m-d' ) );
		
		$this->go_to( eo_get_event_archive_link( new DateTime ( '2014-03-13' ) ) );
		$this->assertTrue( eo_is_event_archive( 'day' ) );
		$this->assertEquals( '2014-03-13', eo_get_event_archive_date( 'Y-m-d' ) );
		
		$this->go_to( eo_get_event_archive_link( 2014, 03 ) );
		$this->assertTrue( eo_is_event_archive( 'month' ) );
		$this->assertEquals( '2014-03-01', eo_get_event_archive_date( 'Y-m-d' ) );
		
		$this->go_to( eo_get_event_archive_link( 2014 ) );
		$this->assertTrue( eo_is_event_archive( 'year' ) );
		$this->assertEquals( '2014-01-01', eo_get_event_archive_date( 'Y-m-d' ) );
		
	}
	
	public function testOndateSlug(){
		
		global $wp_rewrite;
		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
		$options = eventorganiser_get_option( false );
		$options['url_on'] = 'events-on';
		update_option( 'eventorganiser_options', $options );
		eventorganiser_cpt_register();
		$GLOBALS['wp_rewrite']->init();
		flush_rewrite_rules();
		
		$this->go_to( eo_get_event_archive_link( 2014, 03 ) );
		$this->assertTrue( eo_is_event_archive( 'month' ) );
		$this->assertEquals( '2014-03-01', eo_get_event_archive_date( 'Y-m-d' ) );
		$this->assertEquals( 'http://example.org/events/event/events-on/2014/03', eo_get_event_archive_link( 2014, 03 ) );
	
	}
}
