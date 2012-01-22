jQuery(document).ready(function() {

	if(typeof google !=="undefined"){		
		//Retrieve Lat-Lng values from form and load map as page loads.
		var eo_venue_Lat = jQuery("#eo_venue_Lat").val();
		var eo_venue_Lng = jQuery("#eo_venue_Lng").val();
		if(typeof eo_venue_Lat !=="undefined" && typeof eo_venue_Lng !=="undefined"){
			var map;
			var marker;
			initialize(eo_venue_Lat,eo_venue_Lng);

			//Every time form looses focus, use input to display map of address
			jQuery(".eo_addressInput").blur(function(){
				address="";
				jQuery(".eo_addressInput").each(function(){
					if(jQuery(this).attr('id')!='country-selector'){
						address = address+" "+jQuery(this).val();
					}
				})
			codeAddress(address);
			});
		}
	}
});

/**
 * Function that puts a marker on the Google Map at the latitue - longtitude co-ordinates (Lat, Lng)
 * @since 1.0.0
 */
function initialize(Lat,Lng) {
	if(typeof google !=="undefined"){

		var latlng = new google.maps.LatLng(Lat,Lng);
		var myOptions = {
			zoom: 15,
			center: latlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		map = new google.maps.Map(document.getElementById("venuemap"),myOptions);
		marker = new google.maps.Marker({
			position: latlng, 
			map: map
		});

	}
}
	

/**
 * Converts string address into Latitude - Longtitude co-ordinates and then 
 * adds these as the value of two (hidden) input forms.
 * @since 1.0.0
 */	
	function codeAddress(addrStr) {
		var geocoder;
		geocoder = new google.maps.Geocoder();
		geocoder.geocode( { 'address': addrStr}, function(results, status) {
      		if (status == google.maps.GeocoderStatus.OK) {
			map.setCenter(results[0].geometry.location);
			marker.setMap(null);
			marker = new google.maps.Marker({
				map: map,
				position: results[0].geometry.location
			});

		jQuery("#eo_venue_Lat").val(results[0].geometry.location.lat());
		jQuery("#eo_venue_Lng").val(results[0].geometry.location.lng());;
      		} 
	});
	}
