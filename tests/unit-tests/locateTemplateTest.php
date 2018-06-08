<?php

class locateTemplateTest extends EO_UnitTestCase
{

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testNoThemeTemplate(){
		$actual = eo_locate_template( array( 'widget-event-list.php', 'event-list.php' ), false, false );
		$this->assertEquals( EVENT_ORGANISER_DIR . 'templates/widget-event-list.php', $actual);
	}

	public function testThemeTemplate(){
		$parent_template_dir = get_template_directory();
		file_put_contents( $parent_template_dir . '/widget-event-list.php', '');
		$actual = eo_locate_template( array( 'widget-event-list.php', 'event-list.php' ), false, false );
		$this->assertEquals( $parent_template_dir . '/widget-event-list.php', $actual);
		unlink($parent_template_dir . '/widget-event-list.php');
	}

	public function testSpecialisedTemplateOveridesGenericTemplate(){

		$parent_template_dir = get_template_directory();

		//Suppose generic template exists in plugin...
		file_put_contents( EVENT_ORGANISER_DIR . 'templates/event-list.php', '');

		//..and more specific template exists in theme
		file_put_contents( $parent_template_dir . '/widget-event-list.php', '');

		// then we should use the more specific.
		$actual = eo_locate_template( array( 'widget-event-list.php', 'event-list.php' ), false, false );
		$this->assertEquals( $parent_template_dir . '/widget-event-list.php', $actual);

		unlink($parent_template_dir . '/widget-event-list.php');
		unlink(EVENT_ORGANISER_DIR . 'templates/event-list.php');
	}

}
