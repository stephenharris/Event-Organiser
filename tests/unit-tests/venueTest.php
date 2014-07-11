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
	
	
}

