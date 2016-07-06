(function($) {

eovenue = {
		
	maps: {},

	/**
	 * Options
	 *  - lat
	 *  - lng
	 *  - zoom
	 *  - draggable
	 *  - onDrag
	 */
	init_map: function( id, options ){

	    var fieldID    = ( options.hasOwnProperty( 'fieldID' ) ? options.fieldID : id );
	    var draggable  = ( options.hasOwnProperty( 'draggable' ) ? options.draggable : false );
	    var markerIcon = ( options.hasOwnProperty( 'markerIcon' ) ? options.markerIcon : null );
	    	
	    var lat      = ( options.hasOwnProperty( 'lat' ) ? options.lat : 0 );
	    var lng      = ( options.hasOwnProperty( 'lng' ) ? options.lng : 0 );
	    var location = { lat: lat, lng: lng, venue_id: 0 };

	    var map_options = {
	    	zoom: ( options.hasOwnProperty( 'zoom' ) ? options.zoom : 15 ),
	    	center: location,
	    	//mapTypeId: google.maps.MapTypeId.ROADMAP,
			locations:[ location ]
	    };

		var map = new eventorganiserMapsAdapter.google.map( fieldID, map_options );

		var marker = map.addMarker({
			position:  { lat: lat, lng: lng },
			map:       map,
			venue_id: 0,
			draggable: draggable,
			icon:      markerIcon,
		});

	    this.maps[id] = {
	    	map:    map,
	    	marker: [ marker ]
	    } ;

	    if( options.hasOwnProperty( 'onDrag' ) && options.onDrag ){
			marker.on( 'drag', options.onDrag );
	    }
	    
	    if( options.hasOwnProperty( 'onDragend' ) && options.onDragend ){
			marker.on( 'dragend', options.onDragend );
	    }
	    
	    if( options.hasOwnProperty( 'onPositionchanged' ) && options.onPositionchanged ){
			marker.on( 'move', options.onPositionchanged );
	    }
	    
	},
	
	geocode: function( address, callback ){
		var geocoder = new eventorganiserMapsAdapter.google.geocoder();
		geocoder.geocode( address, callback );
	},
		
	get_map: function( id ){
		return this.maps[id];
	}
				
};
})(jQuery);