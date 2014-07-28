<?php

class timezoneTest extends EO_UnitTestCase
{

	public function setUp() {
		parent::setUp();

		wp_cache_delete( 'eventorganiser_timezone' );
		
	}
	
	
	public function _set_offset( $offset ){
		
 		update_option( 'timezone_string', false );
		update_option( 'gmt_offset', $offset );
		
	}
	
	
	public function testDSTOffsetMinus4()
	{
		$this->assertTrue( true );
		$this->_set_offset( -4 );
		
		$this->assertEquals( 'Etc/GMT-4', eo_get_blog_timezone()->getName() );		
	}
	
	
	public function testDSTOffset0()
	{
		$this->assertTrue( true );
		$this->_set_offset( 0 );
		
		$this->assertEquals( 'UTC', eo_get_blog_timezone()->getName() );		
	}
	
	
	public function testDSTOffset8()
	{
		$this->assertTrue( true );
		$this->_set_offset( 8 );
		
		$this->assertEquals( 'Etc/GMT+8', eo_get_blog_timezone()->getName() );		
	}

}
