<?php

/**
 * Access terms associated with a taxonomy
 */
class WP_REST_Event_Venues_Controller extends WP_REST_Terms_Controller {

	protected $taxonomy;

	protected function add_additional_fields_to_object( $data, $request ) {
		
		$data = parent::add_additional_fields_to_object( $data, $request );
		
		$venue_id = (int) $data['id'];
		$data = array_merge(
			$data,
			eo_get_venue_address( $venue_id ),
			array(
				'latitude'   => eo_get_venue_lat( $venue_id ),
				'longtitude' => eo_get_venue_lng( $venue_id ),
			)
		);
		
		return $data;
		
	}
}
