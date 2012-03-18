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

	$post_id = (isset($post->ID) ? $post->ID : 0);

	if(!empty($event_id)) 
		$post_id = $event_id;

	$venue = get_the_terms($post_id,'event-venue');

	if ( empty($venue) || is_wp_error( $venue ) )
		return false;

	$venue = array_pop($venue);

	return $venue->term_id;	
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

	$post_id = (isset($post->ID) ? $post->ID : 0);

	if(!empty($event_id)) 
		$post_id = $event_id;

	$venue = get_the_terms($post_id,'event-venue');

	if ( empty($venue) || is_wp_error( $venue ) )
		return false;

	$venue = array_pop($venue);

	return $venue->slug;
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
	global $post;
	$venue = $venue_slug_or_id;
	if(empty($venue)){
		$venue = get_the_terms($post->ID,'event-venue');

		$venue = ($venue && is_array($venue) ? array_pop($venue) : '');
	}else{
		if(is_numeric($venue)){
			$venue = get_term_by('id', (int) $venue, 'event-venue');
		
		}else{
			$venue = 	get_term_by('slug', $venue, 'event-venue');
		}
		
	}

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
	global $post;

	$venue = $venue_slug_or_id;
	if(empty($venue)){
		$venue = get_the_terms($post->ID,'event-venue');
		$venue = ($venue && is_array($venue) ? array_pop($venue) : '');
	}else{
		if(is_numeric($venue)){
			$venue = get_term_by('id', (int) $venue, 'event-venue');
		
		}else{
			$venue = 	get_term_by('slug', $venue, 'event-venue');
		}
	}

	if ( empty($venue) || is_wp_error( $venue ) )
		return false;

	$description =$venue->venue_description;
	$description = apply_filters('the_content', $description);

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
	global $post;
	$venue = $venue_slug_or_id;
	if(empty($venue)){
		$venue = get_the_terms($post->ID,'event-venue');
		$venue = ($venue && is_array($venue) ? array_pop($venue) : '');
	}else{
		if(is_numeric($venue)){
			$venue = get_term_by('id', (int) $venue, 'event-venue');
		
		}else{
			$venue = 	get_term_by('slug', $venue, 'event-venue');
		}
	}

	if ( empty($venue) || is_wp_error( $venue ) )
		return array('lat'=>0, 'lng'=>0);

	return array('lat'=>$venue->venue_lat,'lng'=>$venue->venue_lng);
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
	$latling =  eo_get_venue_latlng($venue_slug_or_id);
	echo $latling['lat'];
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
	$latling =  eo_get_venue_latlng($venue_slug_or_id);
	echo $latling['lng'];
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
	global $post;

	$venue = $venue_slug_or_id;
	if(empty($venue)){
		$venue = get_the_terms($post->ID,'event-venue');
		$venue = ($venue && is_array($venue) ? array_pop($venue) : '');
	}else{
		if(is_numeric($venue)){
			$venue = get_term_by('id', (int) $venue, 'event-venue');
		
		}else{
			$venue = 	get_term_by('slug', $venue, 'event-venue');
		}
		
	}

	if ( empty($venue) || is_wp_error( $venue ) )
		return '';

	$venue_link = get_term_link( $venue, 'event-venue' );

	return $venue_link;
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
	global $post;

	$venue = $venue_slug_or_id;
	if(empty($venue)){
		$venue = get_the_terms($post->ID,'event-venue');
		$venue = ($venue && is_array($venue) ? array_pop($venue) : '');
	}else{
		if(is_numeric($venue)){
			$venue = get_term_by('id', (int) $venue, 'event-venue');
		
		}else{
			$venue = 	get_term_by('slug', $venue, 'event-venue');
		}
	}

	$address=array();
	$address['address'] = '';
	$address['postcode'] = '';
	$address['country'] = '';

	if ( empty($venue) || is_wp_error( $venue ) )
		return $address;

	$address['address'] = $venue->venue_address;
	$address['postcode'] = $venue->venue_postal;
	$address['country'] = $venue->venue_country;

	return $address;
}

function eo_get_the_venues(){
	global $eventorganiser_venue_table,$wpdb;
	//TODO take care of sanitisation?
	$venues = $wpdb->get_results(" SELECT* FROM $eventorganiser_venue_table");
	return $venues;
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
* Similiar to WordPress' native get_term_by
* Get a (meta data of) venue term (object, or array) by 'name', 'slug' or 'id'.
* Warning: $value is not escaped for 'name' $field. You must do it yourself, if required.
* ID Is kind of depreciated - a new column will eventually hold the term ID
*
* The default $field is 'id', therefore it is possible to also use null for
* field, but not recommended that you do so.
*
* If $value does not exist, the return value will be false. 
* If $field and $value combinations exist, the meta row will be returned.
*
* @param $field (string) the field to query:  'name', 'slug' or 'id'.
* @param string|int $value Search for this term value
* @param $output (Constant) Output format, Object, ARRAY_A, or ARRAY_N
*
* @return Venue term | false - $term as object, array_a or array_nor false
* @since 1.3
*/
function eo_get_venue_by($field,$value,$output = OBJECT){
	global $eventorganiser_venue_table,$wpdb;

	switch($field):
		case 'slug':
			$field = 'venue_slug';
			$value = sanitize_title($value);
			break;

		case 'name':
			$field = 'venue_name';
			break;

		default: 
			$field = 'venue_id';
			$value = intval($value);
	endswitch;

	$term = $wpdb->get_row($wpdb->prepare( "SELECT* FROM {$eventorganiser_venue_table} WHERE {$eventorganiser_venue_table}.$field = %s LIMIT 1", $value));

	if ( !$term )
		return false;

	if ( $output == OBJECT ) {
		return $term;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($term);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($term));
	} else {
		return $term;
	}
}

function eventorganiser_venue_dropdown($post_id=0,$args){
	$venues = get_terms('event-venue', array('hide_empty'=>false));
	$current = get_the_terms($post_id,'event-venue');
	$current = ($current ? array_pop($current) : '');
	$current = ($current ? $current->term_id: 0);

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
?>
