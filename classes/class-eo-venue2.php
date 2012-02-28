<?php
/**
 * Class used to retrieve, create or update a Venue.
 */
class EO_Venue{

	//DB Fields
	var $id = '';
	var $slug = '';
	var $name = '';
	var $address = '';
	var $postcode = '';
	var $country = '';
	var $latitude = '';
	var $longitude = '';
	var $description = '';
	var $owner = '';
	var $isfound = false;

	//Other Vars
	static public $fields = array( 
		'venue_id' => array('name'=>'id','type'=>'int'), 
		'venue_slug' => array('name'=>'slug','type'=>'attr'), 
		'venue_name' => array('name'=>'name','type'=>'attr'), 
		'venue_address' => array('name'=>'address','type'=>'attr'),
		'venue_postal' => array('name'=>'postcode','type'=>'attr'),
		'venue_country' => array('name'=>'country','type'=>'attr'),
		'venue_lat' =>  array('name'=>'latitude','type'=>'float'),
		'venue_lng' => array('name'=>'longitude','type'=>'float'),
		'venue_description' => array('name'=>'description','type'=>'html'),
	);

	/**
	 * Gets data from POST (default), supplied array, or from the database if an ID is supplied
	 * @param $location_data
	 * @return null
	 */
	function EO_Venue($venue = 0 ) {
		//Initialize
		if( !empty($venue) && !is_array($venue) ):
			//Retreiving from the database		
			global $wpdb, $eventorganiser_events_table, $eventorganiser_venue_table;
			if( is_int($venue) ){
				$venue =$wpdb->get_row($wpdb->prepare("SELECT* FROM $eventorganiser_venue_table WHERE venue_id= %d ", $venue), ARRAY_A);
			}else{
				$venue =$wpdb->get_row($wpdb->prepare("SELECT* FROM $eventorganiser_venue_table WHERE venue_slug= %s ", $venue), ARRAY_A);   
			}
			if($venue ){
				$this->to_object($venue);
				$this->isfound= true;
			}
		endif;
	}

	function to_object( $array = array() ){
		//Save core data

		$intvals = array('venue_id');
		$floatvals = array('venue_lat','venue_lng');
		$attrvals = array('vennue_name','venue_slug','venue_address','venue_postal','venue_country');
		$textarea = array('description');

		if(isset($array['venue_lat'])) 
			$this->latitude = floatval($array['venue_lat']);


		if( is_array($array) ):
			foreach ( self::$fields as $key => $val ) :
				if(array_key_exists($key, $array)){
					switch($val['type']):
						case 'intval':
							$this->$val['name'] = intval($array[$key]);
							break;

						case 'float':
							$this->$val['name'] =floatval($array[$key]);
							break;

						case 'attr':
							$this->$val['name'] = esc_attr($array[$key]);
							break;

						case 'html':
							$this->$val['name']  = stripslashes($array[$key]);
							break;

						default:
							$this->$val['name'] = esc_html($array[$key]);
						endswitch;
				}
			endforeach;
		endif;
	}

	function is_found(){
		return $this->isfound;
	}

	function update($venue, $dirtyInput = array()){
		global $eventorganiser_venue_table, $wpdb,$EO_Errors,$EO_Venue;
		$EO_Errors = new WP_Error();
		
		//security check
		if( !check_admin_referer('eventorganiser-edit-venue') || !current_user_can('manage_venues')){
			$EO_Errors = new WP_Error('eo_error', __("You do not have permission to edit this venue.",'eventorganiser'));
			return false;

		}elseif(is_array($dirtyInput) && !empty($dirtyInput)){			
			$V_add= $dirtyInput['Add'];
			$V_postcode= esc_attr($dirtyInput['PostCode']);
			$V_Lat= esc_html($dirtyInput['Lat']);
			$V_Lng= esc_html($dirtyInput['Lng']);
			$V_Country= esc_html($dirtyInput['Country']);
			$description= ( !empty( $dirtyInput['content']) ) ? stripslashes( $dirtyInput['content']):'';
			$name= esc_html($dirtyInput['Name']);
			$venue = esc_attr($venue);
			$V_id = $dirtyInput['id'];

			//Create slug
			$slug = (empty($dirtyInput['slug'])) ? $name : $dirtyInput['slug'];
			$slug =sanitize_title($slug);
			$slug=	$this->slugify($slug, $venue);
	
			if(empty($V_Lng) || empty($V_Lat) ){	
				$address = urlencode($V_add." ".$V_postcode." ".$V_Country);
				$LatLng = generate_LatLng($address);
				$V_Lng=$LatLng['lng'];
				$V_Lat=$LatLng['lat'];
			}
					
			//Venue being updated, do not alter slug
			$clearnInput = array( 
				'venue_name' => $name, 
				'venue_slug' => $slug, 
				'venue_address' => $V_add,
				'venue_postal' => $V_postcode,
				'venue_country' => $V_Country,
				'venue_lng' => $V_Lng,
				'venue_lat' => $V_Lat,
				'venue_description' => $description
			);

			$update = false;
			if($name!=''&& $slug!=''){
						$update = $wpdb->update( $eventorganiser_venue_table,$clearnInput, array( 'venue_slug' => $venue));
			}else{
				$EO_Errors->add('eo_error', __("Venue name or slug is empty",'eventorganiser'));	
			}

			$_REQUEST['action']='edit';

			if($update!==false){
				$EO_Errors->add('eo_notice', __("Venue <strong>updated</strong>",'eventorganiser'));
				return $slug;
			}else{
				$EO_Errors->add('eo_error', __("Venue <strong>was not </strong> updated",'eventorganiser'));	
				return '';
			}
		}		
	}

	function generate_LatLng($address=''){
		global $EO_Errors;
		$lat=0;
		$lng=0;
		$address = trim($address);

		if(empty($address))
			return array('lat'=>$lat,'lng'=>$lng); //No address - no point showing error.

		$geocode=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false');
		$LatLng=false;
		$LatLng= json_decode($geocode);
	
		if(!$LatLng || empty($LatLng->results)){	
			$EO_Errors->add('eo_error', __("There was a problem with locating the latitude and longitude co-ordinates of the venue.",'eventorganiser'));
		}else{
			$lat = esc_html($LatLng->results[0]->geometry->location->lat);
			$lng = esc_html($LatLng->results[0]->geometry->location->lng);
		}
		return array('lat'=>$lat,'lng'=>$lng);
	}

	function insert($args=array()){
		global $eventorganiser_venue_table, $wpdb;
		global $EO_Errors,$current_user;
		
		$defaults = array(
			'Name'=>'',
			'slug'=>'',
			'content'=>'',
			'Add'=>'',
			'PostCode'=>'',
			'Country'=>'',
			'Lat'=>0,
			'Lng'=>0,
		);
		$dirtyinput = wp_parse_args($args,$defaults);

		$V_add= $dirtyinput['Add'];
		$V_postcode= esc_attr($dirtyinput['PostCode']);
		$V_Lat= esc_html($dirtyinput['Lat']);
		$V_Lng= esc_html($dirtyinput['Lng']);
		$V_Country= esc_html($dirtyinput['Country']);
		$name= esc_html($dirtyinput['Name']);
		$description= ( !empty( $dirtyinput['content']) ) ? stripslashes( $dirtyinput['content']):'';
		$slug = (empty($dirtyinput['slug'])) ? $name : $dirtyinput['slug'];
		$slug =sanitize_title($slug);
		$slug=	self::slugify($slug, '');
			
		//Venue being inserted
		$cleaninput = array( 
			'venue_slug' => $slug,
			'venue_name' => $name, 
			'venue_address' => $V_add,
			'venue_postal' => $V_postcode,
			'venue_country' => $V_Country,
			'venue_lng' => $V_Lng,
			'venue_lat' => $V_Lat,
			'venue_description' => $description,
			'venue_owner' => $current_user->ID
		);

		if($name!=''&& $slug!=''){
			$ins = $wpdb->insert($eventorganiser_venue_table,$cleaninput);
			$venue_id = intval($wpdb->insert_id);

			//Inserted in meta table, now insert in taxonomy table
			wp_insert_term($name,'event-venue',
					array(
						'description'=> $description,
						'slug' => $slug
					  )
				);
			return $venue_id;
		}else{
			return false;
		}
	}


	function add($dirtyInput = array()){
		global $EO_Errors;
		$EO_Errors = new WP_Error();
		get_currentuserinfo();
		//security check
		if( !check_admin_referer('eventorganiser-edit-venue')){
			$EO_Errors = new WP_Error('eo_error', __("You do not have permission to create venues",'eventorganiser'));

		}elseif(is_array($dirtyInput) && !empty($dirtyInput)){	

			$V_Lat= esc_html($dirtyInput['Lat']);
			$V_Lng= esc_html($dirtyInput['Lng']);

			$defaults = array(
					'Name'=>'',
					'slug'=>'',
					'content'=>'',
					'Add'=>'',
					'PostCode'=>'',
					'Country'=>'',
					'Lat'=>0,
					'Lng'=>0,
				);
			$dirtyinput = wp_parse_args($dirtyInput,$defaults);

			if(empty($V_Lng) || empty($V_Lat) ){	
				$address = urlencode($dirtyinput['Add']." ".$dirtyinput['PostCode']." ".$dirtyinput['Country']);
				$LatLng = generate_LatLng($address);
				$V_Lng=$LatLng['lng'];
				$V_Lat=$LatLng['lat'];
			}

			$dirtyInput['Lat'] = $V_Lat;
			$dirtyInput['Lng'] = $V_Lng;

			$venue_id  = self::insert($dirtyInput);
			
			if($venue_id):
				$EO_Errors->add('eo_notice', __("Venue <strong>created</strong>",'eventorganiser'));
				$_REQUEST['action']='edit';
				return new EO_Venue($venue_id);

			else:
				$EO_Errors->add('eo_error', __("Venue <strong>was not </strong> created",'eventorganiser'));
				$_REQUEST['action']='create';
				return false;

			endif;
		
		}else{
			$EO_Errors->add('eo_error', __("Venue <strong>was not </strong> created",'eventorganiser'));;
		}		
	}


	function display_description($context){
		switch($context):
			case 'edit':
				return $this->description;
				break;
		endswitch;
	}

	function get_the_link(){
		global $wp_rewrite;
		$venue_link = $wp_rewrite->get_extra_permastruct('event');

		if ( !empty($venue_link)) {
			 $eventorganiser_option_array = get_option('eventorganiser_options'); 
			$venue_slug = trim($eventorganiser_option_array['url_venue'], "/");

			$venue_link = $venue_slug.'/'.esc_attr($this->slug);
			$venue_link = home_url( user_trailingslashit($venue_link) );
		} else {
			$venue_link = add_query_arg(array('venue' =>$this->slug), '');
			$venue_link = home_url($venue_link);
		}
		return $venue_link;
	}

	function the_link(){
		echo $this->get_the_link();
	}

	function get_the_structure(){
		global $wp_rewrite;
		$venue_link = $wp_rewrite->get_extra_permastruct('event');

		if ( !empty($venue_link)) {
			 $eventorganiser_option_array = get_option('eventorganiser_options'); 
			$venue_link = trim($eventorganiser_option_array['url_venue'], "/");
			$venue_link = home_url( user_trailingslashit($venue_link) );
		} else {
			$venue_link = add_query_arg(array('venue' =>'='), '');
			$venue_link = home_url($venue_link);
		}
		return $venue_link;
	}

	function the_structure(){
		echo $this->get_the_structure();
	}

	function venue_edit_link(){
		if(!$this->isfound || !current_user_can('manage_venues'))
			return false;

		$link = admin_url( 'edit.php');
		$link =add_query_arg(array(
			'post_type'=>'event',
			'page'=>'venues',
			'event-venue'=>$this->slug,
			'action'=>'edit'
			), $link);
		
			return $link;	
	}

	function slugify($slug, $venue){
		global $wpdb,$eventorganiser_venue_table;

		$slug =sanitize_title($slug);
	
		//does slug exist?
		$check_sql = "SELECT venue_slug FROM $eventorganiser_venue_table WHERE venue_slug = %s AND  venue_slug != %s LIMIT 1";
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $venue) );

		if ( $post_name_check) {
			//slug already exist, append suffix until unique.
			$suffix = 2;
			do {
				 $alt_slug = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug, $venue) );
				$suffix++;
			} while ( $post_name_check);
				$slug =  $alt_slug;
			}
		return $slug;
	}

}
?>
