<?php

class categoryTest extends EO_UnitTestCase
{
	
	protected function setUp(): void {
		parent::setUp();
	
		global $wpdb;
		
		//Create category
		$this->term_id = $this->factory->term->create( array(
			'taxonomy' => 'event-category',
			'name'     => 'Festival',
			'slug'     => 'festival'
		));
		
		//Insert meta
		update_option( 'eo-event-category_' . $this->term_id, array(
			'colour' => '#ff0000',
			'icon'   => '/path/to/icon-image.png'
		) );
		
		$this->term = get_term_by( 'id', $this->term_id, 'event-category' );

	}
	
	public function testGetCategoryColour()
	{
		//Test with $this->term object
		$this->assertEquals( '#ff0000', eo_get_category_color( $this->term ) );

		//Test with $this->term_id
		$this->assertEquals( '#ff0000', eo_get_category_color( $this->term_id ) );
	}
	
	public function testGetCategoryMeta()
	{
		//Test with $this->term object
		$this->assertEquals( '#ff0000', eo_get_category_meta( $this->term, 'color' ) );
	
		//Test with $this->term_id
		$this->assertEquals( '#ff0000', eo_get_category_meta( $this->term_id, 'color' ) );

		//Test with slug
		$this->assertEquals( '#ff0000', eo_get_category_meta( 'festival', 'color' ) );
	}
	
	/**
	 * eo_get_category_meta() as never functioned with any key other than 'color'.
	 * When we migrate from storing category colours in the options table to the 
	 * term meta table then this function will become a wrapper for get_term_meta() 
	 * and this test can be removed
	 */
	public function testGetCategoryMetaUnsupportedKey()
	{
		//Test with $this->term object
		$this->assertFalse( eo_get_category_meta( $this->term, 'icon' ) );
	
		//Test with $this->term_id
		$this->assertFalse( eo_get_category_meta( $this->term_id, 'icon' ) );
	
		//Test with slug
		$this->assertFalse( eo_get_category_meta( 'festival', 'icon' ) );
	}
	
}

