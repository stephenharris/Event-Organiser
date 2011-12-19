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


	//Other Vars
	var $fields = array( 
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

	var $feedback_message = "";
	var $isfound = false;

	
	var $countries = array("Afghanistan" => "AF", "Åland Islands" => "AX Aaland Aland", "Albania" => "AL", "Algeria" => "DZ", "American Samoa" => "AS", "Andorra" => "AD", "Angola" => "AO", "Anguilla" => "AI", "Antarctica" => "AQ", "Antigua And Barbuda" => "AG", "Argentina" => "AR", "Armenia" => "AM", "Aruba" => "AW", "Australia" => "AU", "Austria" => "AT Österreich Osterreich Oesterreich ", "Azerbaijan" => "AZ", "Bahamas" => "BS", "Bahrain" => "BH", "Bangladesh" => "BD", "Barbados" => "BB", "Belarus" => "BY", "Belgium" => "BE België Belgie", "Belize" => "BZ", "Benin" => "BJ", "Bermuda" => "BM", "Bhutan" => "BT", "Bolivia" => "BO", "Bonaire, Sint Eustatius and Saba" => "BQ", "Bosnia and Herzegovina" => "BA", "Botswana" => "BW", "Bouvet Island" => "BV", "Brazil" => "BR Brasil", "British Indian Ocean Territory" => "IO", "Brunei Darussalam" => "BN", "Bulgaria" => "BG", "Burkina Faso" => "BF", "Burundi" => "BI", "Cambodia" => "KH", "Cameroon" => "CM", "Canada" => "CA", "Cape Verde" => "CV", "Cayman Islands" => "KY", "Central African Republic" => "CF", "Chad" => "TD", "Chile" => "CL", "China" => "CN Zhongguo Zhonghua", "Christmas Island" => "CX", "Cocos (Keeling) Islands" => "CC", "Colombia" => "CO", "Comoros" => "KM", "Congo" => "CG", "Congo, the Democratic Republic of the" => "CD", "Cook Islands" => "CK", "Costa Rica" => "CR", "Côte d'Ivoire" => "CI Cote dIvoire", "Croatia" => "HR Hrvatska", "Cuba" => "CU", "Curaçao" => "CW Curacao", "Cyprus" => "CY", "Czech Republic" => "CZ Česká Ceska", "Denmark" => "DK Danmark", "Djibouti" => "DJ", "Dominica" => "DM", "Dominican Republic" => "DO", "Ecuador" => "EC", "Egypt" => "EG", "El Salvador" => "SV", "Equatorial Guinea" => "GQ", "Eritrea" => "ER", "Estonia" => "EE Eesti", "Ethiopia" => "ET", "Falkland Islands (Malvinas)" => "FK", "Faroe Islands" => "FO Føroyar Færøerne", "Fiji" => "FJ", "Finland" => "FI Suomi", "France" => "FR République française", "French Guiana" => "GF", "French Polynesia" => "PF", "French Southern Territories" => "TF", "Gabon" => "GA", "Gambia" => "GM", "Georgia" => "GE", "Germany" => "DE Bundesrepublik Deutschland", "Ghana" => "GH", "Gibraltar" => "GI", "Greece" => "GR", "Greenland" => "GL grønland", "Grenada" => "GD", "Guadeloupe" => "GP", "Guam" => "GU", "Guatemala" => "GT", "Guernsey" => "GG", "Guinea" => "GN", "Guinea-Bissau" => "GW", "Guyana" => "GY", "Haiti" => "HT", "Heard Island and McDonald Islands" => "HM", "Holy See (Vatican City State)" => "VA", "Honduras" => "HN", "Hong Kong" => "HK", "Hungary" => "HU", "Iceland" => "IS Island", "India" => "IN", "Indonesia" => "ID", "Iran, Islamic Republic of" => "IR", "Iraq" => "IQ", "Ireland" => "IE Éire", "Isle of Man" => "IM", "Israel" => "IL", "Italy" => "IT Italia", "Jamaica" => "JM", "Japan" => "JP Nippon Nihon", "Jersey" => "JE", "Jordan" => "JO", "Kazakhstan" => "KZ", "Kenya" => "KE", "Kiribati" => "KI", "Korea, Democratic People's Republic of" => "KP North Korea", "Korea, Republic of" => "KR South Korea", "Kuwait" => "KW", "Kyrgyzstan" => "KG", "Lao People's Democratic Republic" => "LA", "Latvia" => "LV", "Lebanon" => "LB", "Lesotho" => "LS", "Liberia" => "LR", "Libyan Arab Jamahiriya" => "LY", "Liechtenstein" => "LI", "Lithuania" => "LT", "Luxembourg" => "LU", "Macao" => "MO", "Macedonia, The Former Yugoslav Republic Of" => "MK", "Madagascar" => "MG", "Malawi" => "MW", "Malaysia" => "MY", "Maldives" => "MV", "Mali" => "ML", "Malta" => "MT", "Marshall Islands" => "MH", "Martinique" => "MQ", "Mauritania" => "MR", "Mauritius" => "MU", "Mayotte" => "YT", "Mexico" => "MX Mexicanos", "Micronesia, Federated States of" => "FM", "Moldova, Republic of" => "MD", "Monaco" => "MC", "Mongolia" => "MN", "Montenegro" => "ME", "Montserrat" => "MS", "Morocco" => "MA", "Mozambique" => "MZ", "Myanmar" => "MM", "Namibia" => "NA", "Nauru" => "NR", "Nepal" => "NP", "Netherlands" => "NL Holland Nederland", "New Caledonia" => "NC", "New Zealand" => "NZ", "Nicaragua" => "NI", "Niger" => "NE", "Nigeria" => "NG", "Niue" => "NU", "Norfolk Island" => "NF", "Northern Mariana Islands" => "MP", "Norway" => "NO Norge Noreg", "Oman" => "OM", "Pakistan" => "PK", "Palau" => "PW", "Palestinian Territory, Occupied" => "PS", "Panama" => "PA", "Papua New Guinea" => "PG", "Paraguay" => "PY", "Peru" => "PE", "Philippines" => "PH", "Pitcairn" => "PN", "Poland" => "PL", "Portugal" => "PT", "Puerto Rico" => "PR", "Qatar" => "QA", "Réunion" => "RE Reunion", "Romania" => "RO", "Russian Federation" => "RU Russia Rossiya", "Rwanda" => "RW", "Saint Barthélemy" => "BL", "Saint Helena" => "SH", "Saint Kitts and Nevis" => "KN", "Saint Lucia" => "LC", "Saint Martin (French Part)" => "MF", "Saint Pierre and Miquelon" => "PM", "Saint Vincent and the Grenadines" => "VC", "Samoa" => "WS", "San Marino" => "SM", "Sao Tome and Principe" => "ST", "Saudi Arabia" => "SA", "Senegal" => "SN", "Serbia" => "RS", "Seychelles" => "SC", "Sierra Leone" => "SL", "Singapore" => "SG", "Sint Maarten (Dutch Part)" => "SX", "Slovakia" => "SK", "Slovenia" => "SI", "Solomon Islands" => "SB", "Somalia" => "SO", "South Africa" => "ZA", "South Georgia and the South Sandwich Islands" => "GS", "South Sudan" => "SS", "Spain" => "ES España", "Sri Lanka" => "LK", "Sudan" => "SD", "Suriname" => "SR", "Svalbard and Jan Mayen" => "SJ", "Swaziland" => "SZ", "Sweden" => "SE Sverige", "Switzerland" => "CH Swiss Confederation Schweiz Suisse Svizzera Svizra", "Syrian Arab Republic" => "SY Syria", "Taiwan, Province of China" => "TW", "Tajikistan" => "TJ", "Tanzania, United Republic of" => "TZ", "Thailand" => "TH", "Timor-Leste" => "TL", "Togo" => "TG", "Tokelau" => "TK", "Tonga" => "TO", "Trinidad and Tobago" => "TT", "Tunisia" => "TN", "Turkey" => "TR Türkiye Turkiye", "Turkmenistan" => "TM", "Turks and Caicos Islands" => "TC", "Tuvalu" => "TV", "Uganda" => "UG", "Ukraine" => "UA Ukrayina", "United Arab Emirates" => "AE UAE Emirates", "United Kingdom" => "GB Great Britain England UK Wales Scotland Northern Ireland", "United States" => "US USA United States of America", "United States Minor Outlying Islands" => "UM", "Uruguay" => "UY", "Uzbekistan" => "UZ", "Vanuatu" => "VU", "Venezuela" => "VE", "Vietnam" => "VN", "Virgin Islands, British" => "VG", "Virgin Islands, U.S." => "VI", "Wallis and Futuna" => "WF", "Western Sahara" => "EH", "Yemen" => "YE", "Zambia" => "ZM", "Zimbabwe" => "ZW");	


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
			foreach ( $this->fields as $key => $val ) :
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
		if( !check_admin_referer('eventorganiser-edit-venue')){
			$EO_Errors = new WP_Error('eo_error', __("You do not have permission to edit this venue"));

		}elseif(is_array($dirtyInput) && !empty($dirtyInput)){			
			$V_add= $dirtyInput['Add'];
			$V_postcode= esc_attr($dirtyInput['PostCode']);
			$V_Lat= esc_html($dirtyInput['Lat']);
			$V_Lng= esc_html($dirtyInput['Lng']);
			$V_Country= esc_html($dirtyInput['Country']);
			$description= ( !empty( $dirtyInput['content']) ) ? stripslashes( $dirtyInput['content']):'';
			$name= esc_html($dirtyInput['Name']);
			$V_id = intval($dirtyInput['id']);

			//Create slug
			$slug = (empty($dirtyInput['slug'])) ? $name : $dirtyInput['slug'];
			$slug =sanitize_title($slug);
			$slug=	$this->slugify($slug, $V_id);
			
			$this->EO_Venue($V_id);

			if(empty($V_Lng) || empty($V_Lat) ){							
				$address = urlencode($V_add." ".$V_postcode." ".$V_Country);
				$geocode=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false');
				$LatLing=false;
				$LatLing= json_decode($geocode);
				if($LatLing==false){	
					$EO_Errors->add('eo_error', __("There was a problem with locating the latitude and longitude co-ordinates of the venue."));
				}else{
					$V_Lng = esc_html($LatLing->results[0]->geometry->location->lat);
					$V_La = esc_html($LatLing->results[0]->geometry->location->lng);
				}
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
						$update = $wpdb->update( $eventorganiser_venue_table,$clearnInput, array( 'venue_id' => $V_id));
					}else{
						$EO_Errors->add('eo_error', __("Venue name or slug is empty"));	
					}

					$_REQUEST['action']='edit';
					$this->to_object($clearnInput);

					if($update!==false){
						$EO_Errors->add('eo_notice', __("Venue <strong>updated</strong>"));
						return true;
					}else{
						$EO_Errors->add('eo_error', __("Venue <strong>was not </strong> updated"));	
						return false;
					}
					

		}else{
			$EO_Errors = new WP_Error('eo_error', __("Venue <strong>was not </strong> updated. Invalid input."));		
		}		
	}

	function add($dirtyInput = array()){
		global $eventorganiser_venue_table, $wpdb;
		global $EO_Errors,$current_user;
		$EO_Errors = new WP_Error();
		get_currentuserinfo();
		//security check
		if( !check_admin_referer('eventorganiser-edit-venue')){
			$EO_Errors = new WP_Error('eo_error', __("You do not have permission to create this venue"));
		}elseif(is_array($dirtyInput) && !empty($dirtyInput)){			
						$V_add= $dirtyInput['Add'];
						$V_postcode= esc_attr($dirtyInput['PostCode']);
						$V_Lat= esc_html($dirtyInput['Lat']);
						$V_Lng= esc_html($dirtyInput['Lng']);
						$V_Country= esc_html($dirtyInput['Country']);
			$name= esc_html($dirtyInput['Name']);
						$description= ( !empty( $dirtyInput['content']) ) ? stripslashes( $dirtyInput['content']):'';
						$V_id = intval(mysql_real_escape_string($dirtyInput['id']));
			$slug = (empty($dirtyInput['slug'])) ? $name : $dirtyInput['slug'];
			$slug =sanitize_title($slug);
			$slug=	$this->slugify($slug, $V_id);
			
			if(empty($V_Lng) || empty($V_Lat) ){							
				$address = urlencode($V_add." ".$V_postcode." ".$V_Country);
				$LatLing=false;
				if(file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false')){
					$geocode=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false');
					
					$LatLing= json_decode($geocode);
				}

				if(!$LatLing){	
					$EO_Errors->add('eo_error', __("There was a problem with locating the latitude and longitude co-ordinates of the venue."));
				}else{
					$V_Lng = esc_html($LatLing->results[0]->geometry->location->lat);
					$V_La = esc_html($LatLing->results[0]->geometry->location->lng);
				}
			}

					//Venue being updated, do not alter slug
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
					$ins = false;
					if($name!=''&& $slug!=''){
						$ins = $wpdb->insert($eventorganiser_venue_table,$cleaninput);
					}else{
						$EO_Errors->add('eo_error', __("Venue name or slug is empty"));	
					}
					
					foreach ( $this->fields as $key => $val ) :
						if(array_key_exists($key, $cleaninput)){
							$this->$val['name'] = esc_html($cleaninput[$key]);
						}
					endforeach;

					if($ins){
						$EO_Errors->add('eo_notice', __("Venue <strong>created</strong>"));
						$_REQUEST['action']='edit';
					}else{
						$EO_Errors->add('eo_error', __("Venue <strong>was not </strong> created"));	
						$_REQUEST['action']='create';
					}
		}else{
			$EO_Errors = new WP_Error('EO_error', __("Venue <strong>was not </strong> added. wp"));		
		}		
	}

	function display_description($context){

	switch($context):
		case 'edit':
			//$value = format_to_edit($this->description, user_can_richedit());
			return $this->description;
			break;

	endswitch;
	}

	function country_select(){
		?>
		<select name="eo_venue[Country]" id="country-selector" class="eo_addressInput">
		      <option value="" <?php selected($this->country,''); ?> >Select Country</option>
			<?php foreach ($this->countries as $country => $alternate): ?>
				<option <?php selected($this->country,$country);?> value="<?php echo $country;?>" data-alternative-spellings="<?php echo $alternate; ?>"><?php echo $country;?></option>
			<?php endforeach;?>
		</select>
	<?php
	}

	function get_the_link(){
		global $wp_rewrite;
		$venue_link = $wp_rewrite->get_extra_permastruct('event');

		if ( !empty($venue_link)) {
			$venue_link = 'events/venue/'.esc_attr($this->slug);
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
			$venue_link = 'events/venue/';
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
			'venue'=>$this->id,
			'action'=>'edit'
			), $link);
		
			return $link;
		
	}

	function slugify($slug, $venue_id){
		global $wpdb,$eventorganiser_venue_table;
	
		//does slug exist?
		$check_sql = "SELECT venue_slug FROM $eventorganiser_venue_table WHERE venue_slug = %s AND venue_id != %d LIMIT 1";
		$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $venue_id) );

		if ( $post_name_check) {
			//slug already exist, append suffix until unique.
			$suffix = 2;
			do {
				 $alt_slug = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug, $venue_id) );
				$suffix++;
			} while ( $post_name_check);
				$slug =  $alt_slug;
			}
		return $slug;
}

	
}
?>
