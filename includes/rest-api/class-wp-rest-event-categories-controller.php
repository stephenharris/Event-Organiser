<?php

/**
 * Access terms associated with a taxonomy
 */
class WP_REST_Event_Categories_Controller extends WP_REST_Terms_Controller {

	protected $taxonomy;

	protected function add_additional_fields_to_object( $data, $request ) {
		
		$data = parent::add_additional_fields_to_object( $data, $request );

		$term_id = (int) $data['id'];
		$data = array_merge(
			$data,
			array( 'color' => eo_get_category_color( $term_id ) )
		);
		
		return $data;
		
	}
}
