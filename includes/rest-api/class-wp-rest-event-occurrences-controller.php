<?php
class WP_REST_Event_Occurrences_Controller extends WP_REST_Controller {
	
	public function __construct() {
		$this->namespace = 'wp/v2';
		$obj = get_post_type_object( 'event' );
		$this->event_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
		
		//Expect .../events/<id>/occurrence
		$this->rest_base = $this->event_base . '/(?P<event_id>[\d]+)/occurrence';
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(),
			)
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<occurrence_id>[\d]+)' , array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => array(),
			)
		) );
	}

	/**
	 * Check if a given request has access to read /posts.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
	
		$post_type = get_post_type_object( 'event' );
	
		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit these posts in this post type' ), array( 'status' => rest_authorization_required_code() ) );
		}
	
		return true;
	}
	
	public function get_collection_params() {
		return array();
	}
	
	/**
	 * Get occurrences for a specific event
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$event_id = (int) $request['event_id'];
		$event    = get_post( $event_id );

		if ( empty( $event_id ) || empty( $event->ID ) || $event->post_type !== 'event' ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post id.' ), array( 'status' => 404 ) );
		}

		$occurrences = eo_get_the_occurrences_of( $event_id );

		$collection = array();		
		foreach ( $occurrences as $occurrence_id => $occurrence ) {
			$item = array(
				'id'       => $occurrence_id,
				'event_id' => $event_id,
				'start'    => $occurrence['start']->format( 'c' ),
				'end'      => $occurrence['end']->format( 'c' ),
			);
			
			$prepared_item = $this->prepare_item_for_response( $item, $request );
			$collection[]  = $this->prepare_response_for_collection( $prepared_item );
		}
		$response = rest_ensure_response( $collection );

		$links = array(
			'self' => array(
				'href'   => rest_url( $this->namespace . '/' . $this->rest_base . '/occurrence' ),
			),
			'event' => array(
				'href'       => rest_url( $this->namespace . '/' . $this->event_base . '/' . $event->ID ),
				'embeddable' => true,
			),
		);
		//$response->add_links( $links );

		return $response;
	}
	
	/**
	 * Prepare a single post output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $data, $request ) {
		// Wrap the data in a response object.
		$response      = rest_ensure_response( $data );
		$event_id      = $data['event_id'];
		$occurrence_id = $data['id'];
		
		$links = array(
			'self' => array(
				'href'   => rest_url( $this->namespace . '/' .  $this->event_base . '/' . $event_id . '/occurrence/' . $occurrence_id ),
			),
			'collection' => array(
				'href'   => rest_url( $this->namespace . '/' .  $this->event_base . '/' . $event_id . '/occurrence' ),
			),
			'event' => array(
				'href'       => rest_url( $this->namespace . '/' . $this->event_base . '/' . $event_id ),
				'embeddable' => true,
			),
		);
		$response->add_links( $links );

		return $response;
	}
	
	/**
	 * Get occurrences for a specific event
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$event_id      = (int) $request['event_id'];
		$occurrence_id = (int) $request['occurrence_id'];
		$event         = get_post( $event_id );
		$occurrence    = eo_get_the_occurrence( $event_id, $occurrence_id );
	
		if ( empty( $event_id ) || empty( $event->ID ) || $event->post_type !== 'event' ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid event ID.' ), array( 'status' => 404 ) );
		}

		if ( empty( $occurrence_id ) || empty( $occurrence ) ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid occurrence ID.' ), array( 'status' => 404 ) );
		}
	
		$data = array(
			'id'       => $occurrence_id,
			'event_id' => $event_id,
			'start'    => $occurrence['start']->format( 'c' ),
			'end'      => $occurrence['end']->format( 'c' ),
		);
		$response = $this->prepare_item_for_response( $data, $request );
		
		return $response;
	}


}