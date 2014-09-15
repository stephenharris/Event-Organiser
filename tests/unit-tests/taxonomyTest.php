<?php

class taxonomyTest extends EO_UnitTestCase
{
	
	public function setUp() {
		parent::setUp();
	}
	
	
	function testFilterEventVenueLabel(){
		
		add_filter( 'eventorganiser_register_taxonomy_event-venue', array( $this, '_filterVenueLabels' ) );
		eventorganiser_create_event_taxonomies();
		remove_filter( 'eventorganiser_register_taxonomy_event-venue', array( $this, '_filterVenueLabels' ) );
		
		$tax        = get_taxonomy( 'event-venue' );
		$tax_labels = get_taxonomy_labels( $tax );
		
		$this->assertEquals( 'Event Locations', $tax_labels->name );
		
	}
	
	function _filterVenueLabels( $args ){
		
		$new_venue_labels = array(
			'name'                       => 'Event Locations',
  		); 	
  		$args['labels'] = $new_venue_labels;
  		return $args;
	}
	
	
	function testFilterEventCategoryLabel(){
		
		add_filter( 'eventorganiser_register_taxonomy_event-category', array( $this, '_filterCatLabels' ) );
		eventorganiser_create_event_taxonomies();
		remove_filter( 'eventorganiser_register_taxonomy_event-category', array( $this, '_filterCatLabels' ) );
		
		$tax        = get_taxonomy( 'event-category' );
		$tax_labels = get_taxonomy_labels( $tax );
		
		$this->assertEquals( 'Event Types', $tax_labels->name );
		
	}
	
	function _filterCatLabels( $args ){
		
		$new_venue_labels = array(
			'name'                       => 'Event Types',
  		); 
  		$args['labels'] = $new_venue_labels;
  		return $args;
	}
	
}

