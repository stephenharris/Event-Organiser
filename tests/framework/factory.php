<?php

class EO_UnitTest_Factory extends WP_UnitTest_Factory {

	/**
	 * @var EO_UnitTest_Factory_For_Event
	 */
	public $event;

	/**
	 * @var EO_UnitTest_Factory_For_Venue
	 */
	public $venue;

	public function __construct() {
		parent::__construct();

		$this->event = new EO_UnitTest_Factory_For_Event( $this );
		$this->event_category = new WP_UnitTest_Factory_For_Term( $this, 'event-category' );
	}

}


class EO_UnitTest_Factory_For_Event extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'post_status'  => 'publish',
			'post_title'   => new WP_UnitTest_Generator_Sequence( 'Post title %s' ),
			'post_content' => new WP_UnitTest_Generator_Sequence( 'Post content %s' ),
			'post_excerpt' => new WP_UnitTest_Generator_Sequence( 'Post excerpt %s' ),
		);
	}

	function create_object( $args ) {
		$args = array_merge( array(
			'start' => new DateTime( '2015-02-20 17:54:00' ),
			'end'   => new DateTime( '2015-02-20 18:54:00' ),
		), $args );
		return eo_insert_event( $args );
	}

	function update_object( $post_id, $fields ) {
		$fields['ID'] = $post_id;
		return eo_update_event( $fields );
	}

	function get_object_by_id( $post_id ) {
		return get_post( $post_id );
	}
}
