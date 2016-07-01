(function($) {
	/**
	 * Google Maps Adapter class
	 * Dynamic Prototype Pattern, Adapter
	 */
	eventorganiser.EOGoogleMapAdapter = function ( elementID, args) {

		this._proxiedCallbacks = {};
		this.elementID = elementID;
		this.args = args;
		var mapArgs = {
			zoom: args.zoom,
			scrollwheel: args.scrollwheel,
			zoomControl: args.zoomcontrol,
			rotateControl: args.rotatecontrol,
			panControl: args.pancontrol,
			overviewMapControl: args.overviewmapcontrol,
			streetViewControl: args.streetviewcontrol,
			draggable: args.draggable,
			mapTypeControl: args.maptypecontrol,
			mapTypeId: google.maps.MapTypeId[args.maptypeid],
			styles: args.styles,
			minZoom: args.minzoom,
			maxZoom: args.maxzoom,
			locations: args.locations
		};

		this._markers = {};

		//mapArgs = wp.hooks.applyFilters( 'eventorganiser.google_map_options', mapArgs, this.args );
		this._map = new google.maps.Map( document.getElementById( this.elementID ), mapArgs );

		var mapAdapter = this;

		// constructor prototype to share properties and methods
		if ( typeof this.setCenter !== "function" ) {

			eventorganiser.EOGoogleMapAdapter.prototype.setCenter = function( location ) {
				var latlng = new google.maps.LatLng(location.lat, location.lng );
				this._map.setCenter( latlng );
			};

			eventorganiser.EOGoogleMapAdapter.prototype.setZoom = function( zoom ) {
				this._map.setZoom( zoom );
			};

			eventorganiser.EOGoogleMapAdapter.prototype.fitLocations = function( locations ) {
				var bounds = new google.maps.LatLngBounds();
				for( var j = 0; j < locations.length; j++ ) {

					var lat = locations[j].lat;
					var lng = locations[j].lng;

					if (lat === undefined || lng === undefined) {
						continue;
					}

					var latlng = new google.maps.LatLng(lat, lng);
					bounds.extend(latlng);
				}
				this._map.fitBounds( bounds );
			};

			eventorganiser.EOGoogleMapAdapter.prototype.addMarker = function( location ) {
				location.map = this;
				var marker = new eventorganiser.EOGoogleMarkerAdapter( location );
				this._markers[location.venue_id] = marker;
				return marker;
			};

		}

		if ( this.args.locations.length > 1 ) {
			this.fitLocations( this.args.locations );
		} else {
			this.setCenter( this.args.locations[0] );
		}

		//wp.hooks.doAction( 'eventorganiser.google_map_loaded', this );

	};

	eventorganiser.EOGoogleMarkerAdapter = function ( args ) {

		this.map = args.map;
		args.map = this.map._map;

		var latlng =  new google.maps.LatLng( args.position.lat, args.position.lng );
		var marker_options = $.extend({}, args, {
			venue_id: args.venue_id,
			position: latlng,
			content: args.tooltipContent,
			position: latlng,
			icon: args.icon
		});

		this._marker = new google.maps.Marker(marker_options);

		var infowindow = new google.maps.InfoWindow({
			content: marker_options.content
		});

		if( args.tooltip ){
			google.maps.event.addListener(marker, 'click', function() {
				infowindow.open(args.map,marker);
			});
		}

		var mapAdapter =  this.map;

		// constructor prototype to share properties and methods
		if ( typeof this.setPosition !== "function" ) {
			eventorganiser.EOGoogleMarkerAdapter.prototype.setPosition = function( latLng ) {
				//TODO
				this._marker.setPosition( {lat: latLng.lat, lng: latLng.lng } );
			},
			eventorganiser.EOGoogleMarkerAdapter.prototype.on = function( eventName, callback ) {

				//TODO store callbacks to we can support removing them
				switch(eventName) {
					case 'move':
						eventNameMapped = 'position_changed';
						break;
					default:
						eventNameMapped = eventName;
				}

				google.maps.event.addListener(this._marker, eventNameMapped, function( evt ){

					var proxyEvt = {
						_evt: evt,
						type: eventName,
						target: {
							map: mapAdapter,
							latlng: {}
						}
					}
					if ( ! evt || typeof evt.latLng == undefined ) {
						var latLng = this.getPosition();
					} else {
						var latLng = evt.latLng;
					}

					proxyEvt.target.latlng = new eventorganiser.EOGoogleLatLngProxy( latLng );
					callback.call( mapAdapter, proxyEvt );
				} );
			}
		}
	}

	eventorganiser.EOGoogleLatLngProxy = function( latLng ) {
		this.lat = latLng.lat().toFixed(6);
		this.lng = latLng.lng().toFixed(6);
		this._latlng = latLng;
	}


	/**
	 * OSM Adapter class
	 * Dynamic Prototype Pattern, Adapter
	 */
	eventorganiser.EOpenSourceMapAdapter = function( elementID, args) {

		this._proxiedCallbacks = {};

		this.elementID = elementID;
		this.args = args;
		var mapArgs = {
			zoom: 12,//args.zoom,
			minZoom: 0,//args.minzoom,
			maxZoom: 20,//args.maxzoom,
			locations: args.locations,
			draggable: args.draggable,
			zoomControl: args.zoomcontrol,//whether to display +/- for zooming;
			scrollWheelZoom: args.scrollwheel,//zoom using scroll wheel
			tiles: {
				url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
				attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
			},
			worldCopyJump: true
		};
		this._markers = {};

		//mapArgs = wp.hooks.applyFilters( 'eventorganiser.leaflet_map_options', mapArgs, this.args );
		this._map = L.map( document.getElementById( this.elementID ), mapArgs );
		L.tileLayer( mapArgs.tiles.url, mapArgs.tiles).addTo(this._map);

		var mapAdapter = this;

		// constructor prototype to share properties and methods
		if ( typeof this.setCenter !== "function" ) {

			eventorganiser.EOpenSourceMapAdapter.prototype.setCenter = function( location ) {
				var latlng = L.latLng(location.lat, location.lng);
				this._map.setView( latlng, args.zoom );
			};

			eventorganiser.EOpenSourceMapAdapter.prototype.setZoom = function( zoom ) {
				this._map.setZoom( zoom );
			};

			eventorganiser.EOpenSourceMapAdapter.prototype.fitLocations = function( locations ) {
				var bounds = new L.LatLngBounds();
				for( var j = 0; j < locations.length; j++ ) {

					var lat = locations[j].lat;
					var lng = locations[j].lng;

					if (lat === undefined || lng === undefined) {
						continue;
					}
					var latlng = L.latLng(lat, lng);
					bounds.extend(latlng);
				}
				this._map.fitBounds( bounds );
			};

			eventorganiser.EOpenSourceMapAdapter.prototype.addMarker = function( location ) {
				location.map = this;
				var marker = new eventorganiser.EOpenSourceMarkerAdapter( location );
				this._markers[location.venue_id] = marker;
				return marker;
			};
		}

		if ( this.args.locations.length > 1 ) {
			this.fitLocations( this.args.locations );
		} else {
			this.setCenter( this.args.locations[0] );
		}

		//wp.hooks.doAction( 'eventorganiser.google_map_loaded', this );

	};

	eventorganiser.EOpenSourceMarkerAdapter = function ( args ) {

		this.map = args.map;

		var marker_options = $.extend({}, args, {});
		delete marker_options.icon;

		this._marker = L.marker([args.position.lat, args.position.lng], marker_options);
		this._marker.addTo(this.map._map);

		if( args.tooltip ){
			this._marker.bindPopup( args.tooltipContent )
		}

		var mapAdapter =  this.map;

		// constructor prototype to share properties and methods
		if ( typeof this.setCenter !== "function" ) {
			eventorganiser.EOpenSourceMarkerAdapter.prototype.setPosition = function( latLng ) {
				this._marker.setLatLng( [latLng.lat, latLng.lng ]  );
			},
			eventorganiser.EOpenSourceMarkerAdapter.prototype.on = function( eventName, callback ) {

				//TODO store callbacks to we can support removing them

				this._marker.on( eventName, function( evt ){
					var proxyEvt = {
						_evt: evt,
						type: eventName,
						target: {
							map: mapAdapter,
							latlng: evt.target._latlng
						}
					}
					callback.call( mapAdapter, proxyEvt );
				} );
			}
		}

	}


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
		
	    if (typeof google === "undefined") {
	    	return;
	    }
	
	    var fieldID   = ( options.hasOwnProperty( 'fieldID' ) ? options.fieldID : id );
	    var draggable  = ( options.hasOwnProperty( 'draggable' ) ? options.draggable : false );
	    var markerIcon = ( options.hasOwnProperty( 'markerIcon' ) ? options.markerIcon : null );
	    	
	    var lat = ( options.hasOwnProperty( 'lat' ) ? options.lat : 0 );
	    var lng = ( options.hasOwnProperty( 'lng' ) ? options.lng : 0 );
	    var latlng = new google.maps.LatLng( lat, lng );

	    var map_options = {
	    	zoom: ( options.hasOwnProperty( 'zoom' ) ? options.zoom : 15 ),
	    	center: latlng,
	    	mapTypeId: google.maps.MapTypeId.ROADMAP,
			locations:[
				{ lat: lat, lng: lng, venue_id: 0 }
			]
	    };

		var map   = new eventorganiser.EOGoogleMapAdapter( fieldID, map_options );
		//var map   = new eventorganiser.EOpenSourceMapAdapter( fieldID, map_options );

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
	    
		if (typeof google === "undefined") {
	    	return;
	    }		
		
		var geocoder = new google.maps.Geocoder();
		
		geocoder.geocode(
			{ 'address': address}, 
			function (results, status) {
				if ( status == google.maps.GeocoderStatus.OK ){
					callback.call( this, results[0].geometry.location );
				}else{
					return callback.call( this, false );
				}
		});
	},
		
	get_map: function( id ){
		return this.maps[id];
	}
				
};
})(jQuery);