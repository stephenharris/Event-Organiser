<?php

class venueTest extends EO_UnitTestCase
{
	/**
	 * When an event is saved, if a new venue fails to create because it already exists
	 * we assign the event the ID of the pre-existing venue. To identify if a duplicate 
	 * venue is created we use the returned error code (returned by wp_insert_term()). 
	 * This unit test is to check if that error code ever changes!
	 *  
	 * @see https://github.com/stephenharris/Event-Organiser/issues/202
	 */
	public function testExistingVenue()
	{
		$venue = array(
			'name'        => 'Test Venue',
		 	'description' => 'Description',
			'address'     => '1 Test Road',
			'city'        => 'Testville',
			'state'       => 'Testas',
			'country'     => 'United States of Tests',
 			'latitude'    => 0,
			'longtitude'  => 0,
		);
		
		$venue_ids = eo_insert_venue( $venue['name'], $venue );
		$this->assertFalse( is_wp_error( $venue_ids ) );
		
		$venue_ids = eo_insert_venue( $venue['name'], $venue );
		$this->assertTrue( is_wp_error( $venue_ids ) );
		$this->assertEquals( 'term_exists', $venue_ids->get_error_code() );
		
	}
	
	/**
	 * This function is that the callback to the split_shared_term
	 * hook (handles venues being split is doing its job.
	 */
	public function testPreSplitTerms() {
		global $wpdb;
		
		if( version_compare( get_bloginfo( 'version' ), '4.2-alpha-31007-src', '<' ) ){
			$this->markTestSkipped(
				sprintf( 'This test applies only to 4.2-alpha-31007-src+, running %s', get_bloginfo( 'version' ) )
			);
			return;
		}
	
		register_taxonomy( 'wptests_tax', 'event' );

		$t1 = wp_insert_term( 'Foo', 'wptests_tax' );
		$t2 = eo_insert_venue( 'Foo', array(
			'address' => 'Edinburgh Castle',
			'city'    => 'Edinburgh',
			'country' => 'UK'
		) );
 
		// Manually modify because split terms shouldn't naturally occur.
		$wpdb->update( $wpdb->term_taxonomy,
			array( 'term_id'          => $t2['term_id'] ),
			array( 'term_taxonomy_id' => $t1['term_taxonomy_id'] ),
			array( '%d' ),
			array( '%d' )
		);
		
		$events = $this->factory->event->create_many( 2 );
		wp_set_object_terms( $events[0], array( 'Foo' ), 'wptests_tax' );
		wp_set_object_terms( $events[1], array( 'Foo' ), 'event-venue' );
		
		// Verify that the terms are shared.
		$t1_terms = wp_get_object_terms( $events[0], 'wptests_tax' );
		$t2_terms = wp_get_object_terms( $events[1], 'event-venue' );
		$this->assertSame( $t1_terms[0]->term_id, $t2_terms[0]->term_id );
				
		//Split by updating venue
		eo_update_venue(  $t2_terms[0]->term_id, array(
			'name' => 'New Foo',
		) );
		
		$t1_terms = wp_get_object_terms( $events[0], 'wptests_tax' );
		$t2_terms = wp_get_object_terms( $events[1], 'event-venue' );
		$this->assertNotEquals( $t1_terms[0]->term_id, $t2_terms[0]->term_id );
		
		$address = eo_get_venue_address( $t2_terms[0]->term_id );
		$this->assertEquals( 'Edinburgh', $address['city'] );
		
	}
}

