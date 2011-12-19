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

	if(!empty($event_id)) 
		$event = eo_get_by_postid($event_id);

	if(!empty($event->Venue))
		return (int)$event->Venue;

	return false;
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

	if(!empty($event_id)) 
		$event = eo_get_by_postid($event_id);

	if(empty($event->Venue))
		return false;

	$EO_Venue = new EO_Venue($event->Venue);

	if($EO_Venue->is_found()) 
		return $EO_Venue->slug;

	return false;
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
	$EO_Venue = new EO_Venue();

	if(!empty($venue_slug_or_id)){ 
		$EO_Venue = new EO_Venue($venue_slug_or_id);
	}elseif(!empty($post->Venue)){
		$EO_Venue = new EO_Venue((int) $post->Venue);
	}
	
	if($EO_Venue->is_found()) 
		return $EO_Venue->name;
	
	return false;
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

	if(!empty($venue_slug_or_id)){ 
		$EO_Venue = new EO_Venue($venue_slug_or_id);
	}elseif(!empty($post->Venue)){
		$EO_Venue = new EO_Venue((int) $post->Venue);
	}
	
	if($EO_Venue->is_found()) 
		return $EO_Venue->description;
	
	return false;
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
	$EO_Venue= new EO_Venue();

	if(!empty($venue_slug_or_id)){ 
		$EO_Venue = new EO_Venue($venue_slug_or_id);
	}elseif(!empty($post->Venue)){
		$EO_Venue = new EO_Venue((int) $post->Venue);
	}

	if($EO_Venue->is_found()) 
		return array('lat'=>$EO_Venue->latitude,'lng'=>$EO_Venue->longitude);
	
	return array('lat'=>0, 'lng'=>0);
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

	if(!empty($venue_slug_or_id)){ 
		$EO_Venue = new EO_Venue($venue_slug_or_id);
	}elseif(!empty($post->Venue)){
		$EO_Venue = new EO_Venue((int) $post->Venue);
	}

	if($EO_Venue->is_found()) 
		return $EO_Venue->get_the_link();

	return false; 
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

	if(!empty($venue_slug_or_id)){ 
		$EO_Venue = new EO_Venue($venue_slug_or_id);
	}elseif(!empty($post->Venue)){
		$EO_Venue = new EO_Venue((int) $post->Venue);
	}

	$address['address'] = '';
	$address['postcode'] = '';
	$address['country'] = '';

	if($EO_Venue->is_found()){
		$address['address'] = $EO_Venue->address;
		$address['postcode'] = $EO_Venue->postcode;
		$address['country'] = $EO_Venue->country;
	}

	return $address;
}

?>
