<?php
/**
* Venue related functions
*/

/**
* Returns the id of the venue of an event.
* Can be used inside the loop to output the 
* venue id of the current event.
*
* @param (int) post id
* @return (int) venue id
*
* @since 1.0.0
 */
function eo_get_venue($event_id=''){
	global $post;
	$event = $post;

	if( !empty($event_id) ){
		$post_id = $event_id;
	}else{
		$post_id = (isset($post->ID) ? $post->ID : 0);
	}

	if( empty($post_id) )
		return false;

	$venue = get_the_terms($post_id,'event-venue');

	if ( empty($venue) || is_wp_error( $venue ) )
		return false;

	$venue = array_pop($venue);

	return (int) $venue->term_id;	
}

/**
* Returns the slug of the venue of an event.
* Can be used inside the loop to output the 
* venue slug of the current event.
*
* @param (int) post id
* @return (string) venue slug
*
* @since 1.0.0
 */
function eo_get_venue_slug($event_id=''){
	global $post;
	$event = $post;

	if( !empty($event_id) ){
		$post_id = $event_id;
	}else{
		$post_id = (isset($post->ID) ? $post->ID : 0);
	}

	$venue = get_the_terms($post_id,'event-venue');

	if ( empty($venue) || is_wp_error( $venue ) )
		return false;

	$venue = array_pop($venue);

	return $venue->slug;
}



/*
*  Venue Utility Functions
*/
function eo_get_venue_id_by_slugorid($venue_slug_or_id=''){

	$venue = $venue_slug_or_id;

	if( empty($venue) )
		return eo_get_venue();

	if( is_int($venue) )
		return (int) $venue;

	$venue = get_term_by('slug', $venue, 'event-venue');

	if( $venue )
		return (int) $venue->term_id;
	
	return false;
}

// Calls WordPress' get_term_by
function eo_get_venue_by($field,$value,$output = OBJECT){
	$venue = get_term_by($field, $value, 'event-venue');
	return $venue;
}


/*
*  Venue meta
*/
function eo_get_venue_meta($venue_id, $key, $single=true){	
	return get_metadata('eo_venue', $venue_id, $key, $single); 
}

function eo_add_venue_meta($venue_id, $key, $value, $unique = false ){
	return add_metadata('eo_venue',$venue_id, $key, $value, $unique);
}

function eo_update_venue_meta($venue_id, $key, $value, $prev_value=''){
	return update_metadata('eo_venue', $venue_id, $key, $value, $prev_value);
}

function eo_delete_venue_meta($venue_id, $key, $value = '', $delete_all = false ){
	return delete_metadata('eo_venue',$venue_id, $key, $value, $delete_all);
}

/**
* Returns the name of the venue of the event.
* Can be used inside the loop to output the 
* venue's name of the current event.
*
* @param (int) venue id or (string) venue slug
*
* @since 1.0.0
 */
function eo_get_venue_name($venue_slug_or_id=''){
	$venue_id =  eo_get_venue_id_by_slugorid($venue_slug_or_id);
	$venue = get_term($venue_id,'event-venue');
	
	if ( empty($venue) || is_wp_error( $venue ) )
		return false;

	return $venue->name;
}

/**
* Echos the venue of the event
*
* @uses eo_get_venue_name
* @param (int) venue id or (string) venue slug
*
 * @since 1.0.0
 */
function eo_venue_name($venue_slug_or_id=''){
	echo  eo_get_venue_name($venue_slug_or_id);
}



/**
* Returns the description of the venue
* Can be used inside the loop to output the 
* venue's description of the current event.
*
* @param (int) venue id or (string) venue slug
*
* @since 1.0.0
 */
function eo_get_venue_description($venue_slug_or_id=''){
	$venue_id =  eo_get_venue_id_by_slugorid($venue_slug_or_id);
	$description = eo_get_venue_meta($venue_id,'_description');
	$description = wptexturize($description);
	$description = convert_chars($description);
	$description = wpautop($description);
	$description = shortcode_unautop($description);
	$description = do_shortcode($description);
	return $description;
}


/**
* Echos the description of the venue
* Can be used inside the loop to output the 
* venue's description of the current event.
*
* @param (int) venue id or (string) venue slug
*
* @uses eo_get_venue_description
 * @since 1.0.0
 */
function eo_venue_description($venue_slug_or_id=''){
	echo  eo_get_venue_description($venue_slug_or_id);
}



/**
* Returns an array (latitude, longitude) of the venue
* Can be used inside the loop to return an array of the 
* latitude and longitude of the venue of the current event.
*
* @param (int) venue id or (string) venue slug
* @return (array of floats) (latitude,longitude)
*
 * @since 1.0.0
 */

function eo_get_venue_latlng($venue_slug_or_id=''){	
	$lat = eo_get_venue_lat($venue_slug_or_id);
	$lng = eo_get_venue_lng($venue_slug_or_id);	
	return array('lat'=>$lat,'lng'=>$lng);
}

function eo_get_venue_lat($venue_slug_or_id=''){
	$venue_id =  eo_get_venue_id_by_slugorid($venue_slug_or_id);
	$lat = eo_get_venue_meta($venue_id,'_lat');
	$lat =  ! empty($lat) ? $lat : 0;
	return $lat;
}
function eo_get_venue_lng($venue_slug_or_id=''){
	$venue_id =  eo_get_venue_id_by_slugorid($venue_slug_or_id);
	$lng = eo_get_venue_meta($venue_id,'_lng');
	$lng =  ! empty($lng) ? $lng : 0;
	return $lng;
}


/**
* Echos the latitude of the venue of the event
* Can be used inside the loop to output  the 
* latitude of the venue of the current event.
*
* @param (int) venue id or (string) venue slug
*
 * @since 1.0.0
 */
function eo_venue_lat($venue_slug_or_id=''){
	echo eo_get_venue_lat($venue_slug_or_id);
}



/**
* Echos the longtitude of the venue of the event
* Can be used inside the loop to output  the 
* longitude of the venue of the current event.
*
* @param (int) venue id or (string) venue slug
*
 * @since 1.0.0
 */
function eo_venue_lng($venue_slug_or_id=''){
	echo eo_get_venue_lng($venue_slug_or_id);
}



/**
* Returns the permalink of a  venue
* Can be used inside the loop to output the 
* venue's link of the current event.
*
* @param (int) venue id or (string) venue slug
* @return (string) link of venue
*
 * @since 1.0.0
 */
function eo_get_venue_link($venue_slug_or_id=''){
	$venue_id =  eo_get_venue_id_by_slugorid($venue_slug_or_id);
	return get_term_link( $venue_id, 'event-venue' );
}


/**
* Echos the permalink of the event's venue
* Can be used inside the loop to output the 
* venue's link of the current event.
*
* @param (int) venue id or (string) venue slug
*
 * @since 1.0.0
 */
function eo_venue_link($venue_slug_or_id=''){
	$venue_id =  eo_get_venue_id_by_slugorid($venue_slug_or_id);
	echo  eo_get_venue_link($venue_slug_or_id);
}


/**
* Returns an array with address details of the event's venue
* Can be used inside the loop to return an array of venue address
* of the current event.
*
* @param (int) venue id or (string) venue slug
*
 * @since 1.0.0
 */
function eo_get_venue_address($venue_slug_or_id=''){
	$address=array();	
	$venue_id =  eo_get_venue_id_by_slugorid($venue_slug_or_id);
	$address['address'] = eo_get_venue_meta($venue_id,'_address');
	$address['postcode'] = eo_get_venue_meta($venue_id,'_postcode');
	$address['country'] = eo_get_venue_meta($venue_id,'_country');

	return $address;
}


/**
* Wrapper for get_terms. Maybe depcreciate in favour of eo_get_venues?
 */
function eo_get_the_venues($args=array()){
	return eo_get_venues($args);
}
//Used in ajax file
function eo_get_venues($args=array()){
	$args = wp_parse_args( $args, array('hide_empty'=>0 ) );
	return get_terms('event-venue',$args);
}


function eo_event_venue_dropdown( $args = '' ) {
	$defaults = array(
		'show_option_all' =>'', 
		'echo' => 1,
		'selected' => 0, 
		'name' => 'event-venue', 
		'id' => '',
		'class' => 'postform event-organiser event-venue-dropdown event-dropdown', 
		'tab_index' => 0, 
	);

	$defaults['selected'] =  (is_tax('event-venue') ? get_query_var('event-venue') : 0);
	$r = wp_parse_args( $args, $defaults );
	$r['taxonomy']='event-venue';
	extract( $r );

	$tab_index_attribute = '';
	if ( (int) $tab_index > 0 )
		$tab_index_attribute = " tabindex=\"$tab_index\"";

	$categories = get_terms($taxonomy, $r ); 
	$name = esc_attr( $name );
	$class = esc_attr( $class );
	$id = $id ? esc_attr( $id ) : $name;

	$output = "<select style='width:150px' name='$name' id='$id' class='$class' $tab_index_attribute>\n";
	
	if ( $show_option_all ) {
		$output .= '<option '.selected($selected,0,false).' value="0">'.$show_option_all.'</option>';
	}

	if ( ! empty( $categories ) ) {
		foreach ($categories as $term):
			$output .= '<option value="'.$term->slug.'"'.selected($selected,$term->slug,false).'>'.$term->name.'</option>';
		endforeach; 
	}
	$output .= "</select>\n";

	if ( $echo )
		echo $output;

	return $output;
}




/**
 * Updates new venue in the database. 
 *
 * Calls wp_insert_term to create the taxonomy term
 * Adds venue meta data to database (for 'core' meta keys)
 * 
 * The $args is an array - the same as that accepted by wp_update_term
 * The $args array can also accept the following keys: 
 * *  description, address, postcode, country, latitude, longtitude
 *
 * @since 1.4.0
 *
 * @uses wp_update_term to update venue (taxonomy) term
 * @uses do_action() Calls 'eventorganiser_save_venue' hook with the venue id
 *
 * @param (int) the Term ID of the venue to update
 * @param array $args as accepted by wp_insert_term and including the default meta data
 * @return array|WP_Error array of term ID and term-taxonomy ID or a WP_Error
 */
	function eo_update_venue($venue_id, $args=array()){

		$term_args = array_intersect_key($args, array('name'=>'','term_id'=>'','term_group'=>'','term_taxonomy_id'=>'','alias_of'=>'','parent'=>0,'slug'=>'','count'=>''));
		$meta_args = array_intersect_key($args, array('description'=>'','address'=>'','postcode'=>'','country'=>'','latitude'=>'','longtitude'=>''));
		$venue_id = (int) $venue_id;


		//Update taxonomy table
		$resp = wp_update_term($venue_id,'event-venue', $term_args);

		if( is_wp_error($resp) ){
			return $resp;
		}

		$venue_id = (int) $resp['term_id'];

		foreach( $meta_args as $key => $value ){
			switch($key):
				case 'latitude':
					$meta_key = '_lat';
					break;
				case 'longtitude':
					$meta_key = '_lng';
					break;
				default:
					$meta_key = '_'.$key;
					break;
			endswitch;

			$validated_value = eventorganiser_sanitize_meta($meta_key, $value);

			update_metadata('eo_venue', $venue_id, $meta_key, $validated_value);		
		}
		do_action('eventorganiser_save_venue',$venue_id);

		return array('term_id' => $venue_id, 'term_taxonomy_id' => $resp['term_taxonomy_id']);
	}


/**
 * Adds a new venue to the database. 
 *
 * Calls wp_insert_term to create the taxonomy term
 * Adds venue meta data to database (for 'core' meta keys)
 * 
 * The $args is an array - the same as that accepted by wp_insert_term
 * The $args array can also accept the following keys: 
 * *  description, address, postcode, country, latitude, longtitude
 *
 * @since 1.4.0
 *
 * @uses wp_insert_term to create venue (taxonomy) term
 * @uses do_action() Calls 'eventorganiser_insert_venue' hook with the venue id
 * @uses do_action() Calls 'eventorganiser_save_venue' hook with the venue id
 *
 * @param string $name the venue to insert
 * @param array $args as accepted by wp_insert_term and including the default meta data
 * @return array|WP_Error array of term ID and term-taxonomy ID or a WP_Error
 */
	function eo_insert_venue($name, $args=array()){
		$term_args = array_intersect_key($args, array('name'=>'','term_id'=>'','term_group'=>'','term_taxonomy_id'=>'','alias_of'=>'','parent'=>0,'slug'=>'','count'=>''));
		$meta_args = array_intersect_key($args, array('description'=>'','address'=>'','postcode'=>'','country'=>'','latitude'=>'','longtitude'=>''));

		$resp = wp_insert_term($name,'event-venue',$term_args);

		if(is_wp_error($resp)){
			return $resp;
		}

		$venue_id = (int) $resp['term_id'];

		foreach( $meta_args as $key => $value ){
			switch($key):
				case 'latitude':
					$meta_key = '_lat';
					break;
				case 'longtitude':
					$meta_key = '_lng';
					break;
				default:
					$meta_key = '_'.$key;
					break;
			endswitch;

			$validated_value = eventorganiser_sanitize_meta($meta_key, $value);

			if( !empty($validated_value) )
				add_metadata('eo_venue', $venue_id, $meta_key, $validated_value, true);		
		}
	
		do_action('eventorganiser_insert_venue',$venue_id);
		do_action('eventorganiser_save_venue',$venue_id);

		return array('term_id' => $venue_id, 'term_taxonomy_id' => $resp['term_taxonomy_id']);
	}

/**
 * Deletes a venue in the database. 
 *
 * Calls wp_delete_term to delete the taxonomy term
 * Deletes all the venue's meta 
 * 
 * @since 1.4.0
 *
 * @uses wp_delete_term to delete venue (taxonomy) term
 * @uses do_action() Calls 'eventorganiser_delete_venue' hook with the venue id
 *
 * @param (int) the Term ID of the venue to update
 * @return bool|WP_Error false or error on failure. True after sucessfully deleting the venue and its meta data.
 */
	function eo_delete_venue($venue_id){
		global $wpdb;
		$resp =wp_delete_term( $venue_id, 'event-venue');
		if( is_wp_error($resp) || false === $resp ){
			return $resp;
		}
		$venue_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->eo_venuemeta WHERE eo_venue_id = %d ", $venue_id ));

		if ( !empty($venue_meta_ids) ) {
			$in_venue_meta_ids = "'" . implode("', '", $venue_meta_ids) . "'";
			$wpdb->query( "DELETE FROM $wpdb->eo_venuemeta WHERE meta_id IN($in_venue_meta_ids)" );
		}
		do_action('eventorganiser_delete_venue',$venue_id);

		return true;
	}



	function eventorganiser_sanitize_meta($key,$value){
		switch($key):
			case '_address':
			case '_postcode':
			case '_country':
				$value = sanitize_text_field($value);
				break;

			case '_description':
				$value = wp_filter_post_kses($value);
				break;

			case '_lat':
			case '_lng':
				//Cast as float and then string: make sure string uses . not , for decimal point
				$value = floatval($value);
				$value = number_format($value, 6);
				break;
			default:
				$value = false;
		endswitch;

		return $value;
	}



function eventorganiser_venue_dropdown($post_id=0,$args){
	$venues = get_terms('event-venue', array('hide_empty'=>false));
	$current = (int) eo_get_venue($post_id); 

	$id = (!empty($args['id']) ? 'id="'.esc_attr($args['id']).'"' : '');
	$name = (!empty($args['name']) ? 'name="'.esc_attr($args['name']).'"' : '');
	?>
	<select <?php echo $id.' '.$name; ?>>
		<option><?php _e("Select a venue",'eventorganiser');?></option>
		<?php foreach ($venues as $venue):?>
			<option <?php  selected($venue->term_id,$current);?> value="<?php echo $venue->term_id;?>"><?php echo $venue->name; ?></option>
		<?php endforeach;?>
	</select><?php
}


	function eo_get_venue_map($venue_slug_or_id='', $args=array()){
		$venue_id = eo_get_venue_id_by_slugorid($venue_slug_or_id);
		return EventOrganiser_Shortcodes::get_venue_map($venue_id, $args);
	}
?>
