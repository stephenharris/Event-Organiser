<?php

class eventQueryTest extends EO_UnitTestCase
{
	protected function setUp(): void {
		parent::setUp();
	
		$this->event_ids = array();
		
		$event_id = $this->factory->event->create(array(
			'start'    => new DateTime( '2015-03-21 19:30:00', eo_get_blog_timezone() ),
			'end'      => new DateTime( '2015-03-21 20:30:00', eo_get_blog_timezone() ),
			'schedule' => 'daily',
			'schedule_last'    => new DateTime( '2015-03-24 19:30:00', eo_get_blog_timezone() ),
		));
		
		//Now insert another event before the rest, so that the first (chronological) occurrence is
		//not the first in the database for this series.
		eo_update_event( $event_id, array( 
			'include' => array( new DateTime( '2015-03-20 19:30:00', eo_get_blog_timezone() ) )
		) );
		
		$this->event_ids[] = $event_id;
		
		$this->event_ids[] = $this->factory->event->create(array(
			'start'    => new DateTime( '2015-03-02 14:00:00', eo_get_blog_timezone() ),
			'end'      => new DateTime( '2015-03-02 15:00:00', eo_get_blog_timezone() ),
			'schedule' => 'weekly',
			'schedule_last'    => new DateTime( '2015-03-30 14:00:00', eo_get_blog_timezone() ),
		));
		
		$this->event_ids[] = $this->factory->event->create(array(
			'start'    => new DateTime( '2015-03-23 09:45:00', eo_get_blog_timezone() ),
			'end'      => new DateTime( '2015-03-23 10:00:00', eo_get_blog_timezone() ),
			'schedule' => 'daily',
			'schedule_last'    => new DateTime( '2015-03-27 09:45:00', eo_get_blog_timezone() ),
		));
		
	}
	
	/**
	 * When grouping events by series, the plug-in should use the first date
	 * (chronologicaly) of the series, matching the query 
	 */
    public function testSeriesQuery()
    {
		$events = eo_get_events( array(
			'event_start_after' => '2015-03-01 00:00:00',
			'showpastevents'    => true,		
			'group_events_by'   => 'series',
		));
		
		$actual = array();
		
		foreach( $events as $event ){
			$actual[] = eo_get_the_start( DATETIMEOBJ, $event->ID, null, $event->occurrence_id );
		}
		
		$expected = array(
			new DateTime( '2015-03-02 14:00:00', eo_get_blog_timezone() ),
			new DateTime( '2015-03-20 19:30:00', eo_get_blog_timezone() ),
			new DateTime( '2015-03-23 09:45:00', eo_get_blog_timezone() ),
		);
		
		$this->assertEquals( $expected, $actual );
		
		foreach( $this->event_ids as $event_id ){
			
			//var_dump( eo_get_the_occurrences( $event_id ) );
			
		}
		$events = eo_get_events( array(
			'event_start_after' => '2015-03-22 00:00:00',
			'showpastevents'    => true,
			'group_events_by'   => 'series',
		));
		
		$actual = array();
		
		foreach( $events as $event ){
			$actual[] = eo_get_the_start( DATETIMEOBJ, $event->ID, null, $event->occurrence_id );
		}
		
		$expected = array(
			new DateTime( '2015-03-22 19:30:00', eo_get_blog_timezone() ),
			new DateTime( '2015-03-23 09:45:00', eo_get_blog_timezone() ),
			new DateTime( '2015-03-23 14:00:00', eo_get_blog_timezone() ),
		);
		
		$this->assertEquals( $expected, $actual );
    }

    
    public function testEventCategoryQuery(){
    	    	
    	$term_id = $this->factory->term->create( array( 
    		'slug'     => 'film',
    		'taxonomy' => 'event-category',
    		'name'     => 'Film'
    	) );

    	$e0 = $this->event_ids[0];
    	$e2 = $this->event_ids[2];

    	wp_set_post_terms( $e0, array( $term_id ), 'event-category' );
    	wp_set_post_terms( $e2, array( $term_id ), 'event-category' );
    	    	
    	$actual = eo_get_events( array(
    		'fields'    => 'ids',
    		'tax_query' => array(
    			array(
    				'taxonomy' => 'event-category',
    				'field'    => 'slug',
    				'terms'    => array( 'film' )
    			)
    		)
    	));
    	
    	$this->assertEquals( array( $e0, $e0, $e0, $e2, $e0, $e2,$e0, $e2, $e2, $e2 ), $actual );
    	
    }
  
}

