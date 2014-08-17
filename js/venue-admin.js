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
        onDrag: function (evt) {
        	this.dragging = true;
        },
        onDragend: function ( evt ) {
        	this.dragging = false;
        	this.setPosition( this.position );
        },
        onPositionchanged: function (){
        	if( !this.dragging ){
        		var latLng = this.getPosition();

        		$("#eo_venue_Lat").val( latLng.lat().toFixed(6) );
        		$("#eo_venue_Lng").val( latLng.lng().toFixed(6) );
        		                
        		this.getMap().setCenter( latLng );
        		this.getMap().setZoom( 15 );
        	}
        },
	});
        
	$(".eo_addressInput").change(function () {
		var address = [];
		$(".eo_addressInput").each(function () {
			address.push($(this).val());
		});
            
		eovenue.geocode( address.join(', '), function( latlng ){
			if( latlng ){
				eovenue.get_map( 'venuemap' ).marker[0].setPosition( latlng );
			}
		});
	});
			
});