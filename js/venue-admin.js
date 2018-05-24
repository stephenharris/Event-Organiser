var eo_venue = eo_venue || { marker: false };

jQuery(document).ready(function ($) {
	
	postboxes.add_postbox_toggles( pagenow );
				
	var eo_venue_Lat = $("#eo_venue_Lat").val();
	var eo_venue_Lng = $("#eo_venue_Lng").val();
	var zoom = 15;
        
	if( eo_venue_Lat === 0 && eo_venue_Lng === 0 ){
		var address = [];
		$(".eo_addressInput").each(function (){ address.push($(this).val());});
		if( !address.join('') ){
			zoom = 1;
		}
	}

	eovenue.init_map( 'venuemap', {
		lat: eo_venue_Lat,
        lng: eo_venue_Lng,
        zoom: zoom,
        draggable: true,
		onDrag: function( evt ) {
        	this.dragging = true;
        	$("#eo-venue-latllng-text").text( evt.target.latlng.lat + ', ' + evt.target.latlng.lng );
        },
        onDragend: function( evt ) {
        	this.dragging = false;
			var latlngStr = evt.target.latlng.lat + ', ' + evt.target.latlng.lng;
			$("#eo_venue_Lat").val( evt.target.latlng.lat );
			$("#eo_venue_Lng").val( evt.target.latlng.lng );
			$("#eo-venue-latllng-text").text( latlngStr );
			evt.target.map.setCenter( evt.target.latlng );
		},
        onPositionchanged: function ( evt ){

        	if( !this.dragging ){
				var latlngStr = evt.target.latlng.lat + ', ' + evt.target.latlng.lng;
        		$("#eo_venue_Lat").val( evt.target.latlng.lat );
        		$("#eo_venue_Lng").val( evt.target.latlng.lng );
        		$("#eo-venue-latllng-text").text( latlngStr );
				evt.target.map.setCenter( evt.target.latlng );
        	}
        },
	});
        
	$(".eo_addressInput").change(function () {
		var address = {};
		$(".eo_addressInput").each(function () {
			var component = $(this).attr('id').replace(/^eo-venue-/, '');
			address[component] = $(this).val() ? $(this).val() : null;
		});
		eovenue.geocode( address, function( latlng ){
			if( latlng ){
				eovenue.get_map( 'venuemap' ).marker[0].setPosition( { lat: latlng.lat, lng: latlng.lng } );
			}
		});
	});
	
	$('#eo-venue-latllng-text').blur(function() {
		var text    = $(this).text().trim().replace(/ /g,'');
		var match   = text.match(/^(-?[0-9]{1,3}\.[0-9]+),(-?[0-9]{1,3}\.[0-9]+)$/);
		var old_lat = $(this).data('eo-lat');
		var old_lng = $(this).data('eo-lng');
		
		if( match ){
			var lat = match[1];
			var lng = match[2];
			
			if( lat != old_lat || lng != old_lng ){
				$(this).data( 'eo-lat', lat );
				$(this).data( 'eo-lng', lng );
				eovenue.get_map( 'venuemap' ).marker[0].setPosition( { lat: lat, lng: lng } );
			}
		}else{
			//Not valid...
			$(this).text( old_lat + ", " + old_lng );
		}
	});
	
	$('#eo-venue-latllng-text').keydown( function( evt ){
		//On enter leave the latitude/longtitude
		if( 13 === evt.which ){
			$(this).blur();	
		}
	});
			
});