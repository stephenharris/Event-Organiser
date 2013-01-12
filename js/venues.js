var map;
var marker;
jQuery(document).ready(function () {
	if (typeof EO_Venue != 'undefined') {
		postboxes.add_postbox_toggles(pagenow);
	}

    if (typeof google !== "undefined") {
        var eo_venue_Lat = jQuery("#eo_venue_Lat").val();
        var eo_venue_Lng = jQuery("#eo_venue_Lng").val();
        if (typeof eo_venue_Lat !== "undefined" && typeof eo_venue_Lng !== "undefined") {
		eo_initialize_map(eo_venue_Lat, eo_venue_Lng);

		if( eo_venue_Lat == 0 && eo_venue_Lng == 0 ){
			if( typeof EO_Venue != 'undefined'){
				var address = EO_Venue.location.split("/");	
				address = address[address.length-1];
			}
			if(  typeof address != 'undefined' && address ){
		                eventorganiser_code_address(address);
			}else{
				map.setZoom(1);
			}
		}
            jQuery(".eo_addressInput").change(function () {
                var address = [];
                jQuery(".eo_addressInput").each(function () {
			address.push(jQuery(this).val());
                });
                eventorganiser_code_address(address.join(', '))
            })
        }
    }
});

function eo_initialize_map(Lat, Lng) {

    if (typeof google !== "undefined") {
        var latlng = new google.maps.LatLng(Lat, Lng);

        var myOptions = {
            zoom: 15,
            center: latlng,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        map = new google.maps.Map(document.getElementById("venuemap"), myOptions);

        if (typeof EO_Venue != 'undefined') {
            var draggable = true
        } else {
            var draggable = false
        }

        marker = new google.maps.Marker({
            position: latlng,
            map: map,
            draggable: draggable
        });

        if (typeof EO_Venue != 'undefined') {
            google.maps.event.addListener(marker, 'dragend', function (evt) {
                jQuery("#eo_venue_Lat").val(evt.latLng.lat().toFixed(6));
                jQuery("#eo_venue_Lng").val(evt.latLng.lng().toFixed(6));
                map.setCenter(marker.position)
            })
        }
    }
}

function eventorganiser_code_address(addrStr) {
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({'address': addrStr}, function (results, status) {
		if ( status == google.maps.GeocoderStatus.OK){

			marker.setMap(null);
			map.setCenter(results[0].geometry.location);
			if (typeof EO_Venue != 'undefined') {
				var draggable = true
			} else {
				var draggable = false
			}
			marker = new google.maps.Marker({
               	 		map: map,
		                position: results[0].geometry.location,
                		draggable: draggable
            		});
			map.setZoom(15);
            	if (typeof EO_Venue != 'undefined') {
			google.maps.event.addListener(marker, 'dragend', function (evt) {
				jQuery("#eo_venue_Lat").val(evt.latLng.lat().toFixed(6));
				jQuery("#eo_venue_Lng").val(evt.latLng.lng().toFixed(6));
				map.setCenter(marker.position)
			})
		}
		jQuery("#eo_venue_Lat").val(results[0].geometry.location.lat());
		jQuery("#eo_venue_Lng").val(results[0].geometry.location.lng());
        }
})
}
