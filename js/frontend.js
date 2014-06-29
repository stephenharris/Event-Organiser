if ( typeof EO_SCRIPT_DEBUG === 'undefined') { EO_SCRIPT_DEBUG = true;}

var eventorganiser = eventorganiser || {};

(function ($) {
jQuery(document).ready(function () {

	function eventorganiser_cat_dropdown(options){

		var terms = options.categories;
		
		//Are we whitelisting categories 
		var included_cats = ( typeof options.category !== "undefined" && options.category ? options.category.split(',') : false );   
		
		var html="<select class='eo-cal-filter' id='eo-event-cat'>";
		html+="<option value=''>"+options.buttonText.cat+"</option>";
		var term;
		for ( var term_id in terms ){
			
			term = terms[term_id];
			
			//If whitelist check term (or ancestor of) belongs to white list.
			if( included_cats ){
				var include_in_dropdown = false;
				
				if( $.inArray( term.slug, included_cats ) !== -1 ){
					include_in_dropdown = true;
				}
				
				//Check ancestors
				var parent = term;
				while( !include_in_dropdown && parent.parent > 0 ){
					parent = terms[parent.parent];
					if( $.inArray( parent.slug, included_cats ) !== -1 ){
						include_in_dropdown = true;
					}
				}
				
				if( !include_in_dropdown ){
					continue;
				}
			}
			
			html+= "<option class='cat-colour-"+term.colour+" cat' value='"+term.slug+"'>"+term.name+"</option>";
		}
		html+="</select>";

		var element = $("<span class='fc-header-dropdown filter-category'></span>");
		element.append(html);
		return element;
	}

	function eventorganiser_mini_calendar(){
		var element = $("<span class='fc-header-goto'><input type='hidden' class='eo-mini-calendar'/></span>");
		return element;
	}

	function eventorganiser_tag_dropdown(options){

		
		var terms = options.tags;
		
		var html="<select class='eo-cal-filter' data-filter-type='event-tag'>";
		html+="<option value=''>"+options.buttonText.tag+"</option>";
		for (var i=0; i < terms.length; i++){
			html+= "<option value='"+terms[i].slug+"'>"+terms[i].name+"</option>";	
		}
		html+="</select>";

		var element = $("<span class='fc-header-dropdown filter-tag'></span>");
		element.append(html);
		return element;
	}
	
	function eventorganiser_venue_dropdown(options){

		var venues = options.venues;

		var html="<select class='eo-cal-filter' id='eo-event-venue'>";
		html+="<option value=''>"+options.buttonText.venue+"</option>";
		
		//Are we whitelisting venues 
		var included_venues = ( typeof options.venue !== "undefined" && options.venue ? options.venue.split(',') : false );

		for (var i=0; i<venues.length; i++){
			
			//If whitelist check term (or ancestor of) belongs to white list.
			if( included_venues && $.inArray( venues[i].slug, included_venues ) === -1 ){
				continue;
			}
				
			html+= "<option value='"+venues[i].term_id+"'>"+venues[i].name+"</option>";	
		}
		
		html+="</select>";
		var element = $("<span class='fc-header-dropdown filter-venue'></span>");
		element.append(html);
		return element;
	}

	if( $('#eo-upcoming-dates').length>0 && $('#eo-upcoming-dates').find('li:gt(4)').length > 0 ){
		var eobloc = 5;
		var locale = { more : EOAjaxFront.locale.ShowMore, less : EOAjaxFront.locale.ShowLess};
		$('#eo-upcoming-dates').find('li:gt('+(eobloc-1)+')').hide().end().after(
	    		$('<a href="#" id="eo-upcoming-dates-less">'+locale.less+'</a> <span id="eo-upcoming-dates-pipe">|</span> <a href="#" id="eo-upcoming-dates-more">'+locale.more+'</a>')
		);
		$('#eo-upcoming-dates-pipe').hide();
		$('#eo-upcoming-dates-less').hide().click(function(e){
			e.preventDefault();
			var index = Math.floor( ($('#eo-upcoming-dates li:visible').length -1) / eobloc)*eobloc -1;
			$('#eo-upcoming-dates li:gt('+index+')').hide();
			$('#eo-upcoming-dates-more,#eo-upcoming-dates-pipe').show();
			if( $('#eo-upcoming-dates li:visible').length <= eobloc ){
					$('#eo-upcoming-dates-less,#eo-upcoming-dates-pipe').hide();
			}
		});
		$('#eo-upcoming-dates-more').click(function(e){
			e.preventDefault();
			$('#eo-upcoming-dates-less,#eo-upcoming-dates-pipe, #eo-upcoming-dates li:hidden:lt('+eobloc+')').show();
			var offset = $('#eo-upcoming-dates-pipe').offset();
			$('html, body').animate({
				scrollTop: Math.max( offset.top + 40 - $(window).height(),$(window).scrollTop())
			});
			if( $('#eo-upcoming-dates li:hidden').length === 0 ){
				$('#eo-upcoming-dates-more,#eo-upcoming-dates-pipe').hide();
			}
		});
	}

        if ($(".eo-fullcalendar").length > 0) {
		var calendars = eventorganiser.calendars;
		var loadingTimeOut;
            for (var i = 0; i < calendars.length; i++) {
            	var calendar = "#eo_fullcalendar_" + (i + 1);
            	if (typeof calendars[i].category === "undefined") {
            		calendars[i].category ='';
            	}
            	if (typeof calendars[i].venue === "undefined") {
            		calendars[i].venue ='';
            	}

            	var args = {
				id: calendar,
				year: calendars[i].year ? calendars[i].year : undefined,
				month: calendars[i].month ? calendars[i].month : undefined,
				date: calendars[i].date ? calendars[i].date : undefined,
				category: calendars[i].event_category,
				venue: calendars[i].event_venue,
				customButtons:{
					tag:		eventorganiser_tag_dropdown,
					category:  	eventorganiser_cat_dropdown,
					venue:  	eventorganiser_venue_dropdown,
					'goto': 	eventorganiser_mini_calendar
				},
				theme: calendars[i].theme,
				categories: eventorganiser.fullcal.categories,
				venues: eventorganiser.fullcal.venues,
				tags: eventorganiser.fullcal.tags,
				timeFormatphp: calendars[i].timeformatphp,
				timeFormat: calendars[i].timeformat,
				isRTL: calendars[i].isrtl,
				editable: false,
				tooltip: calendars[i].tooltip,
				firstDay: parseInt( eventorganiser.fullcal.firstDay, 10 ),
				weekends: calendars[i].weekends,
				allDaySlot: calendars[i].alldayslot,
				allDayText: calendars[i].alldaytext,
				axisFormat: calendars[i].axisformat,
				minTime: calendars[i].mintime,
				maxTime:calendars[i].maxtime,
				columnFormat: {
					month: calendars[i].columnformatmonth,
					week: calendars[i].columnformatweek,
					day: calendars[i].columnformatday
				},
				titleFormat: {
					month: calendars[i].titleformatmonth,
					week: calendars[i].titleformatweek,
					day: calendars[i].titleformatday
				},
				header: {
					left: calendars[i].headerleft,
					center: calendars[i].headercenter,
					right: calendars[i].headerright
				},
				eventRender: 
					function (a, b, v) {
						var c = $(v.calendar.options.id).find(".filter-category .eo-cal-filter").val();
						var d = $(v.calendar.options.id).find(".filter-venue .eo-cal-filter").val();
						var tag = $(v.calendar.options.id).find(".filter-tag .eo-cal-filter").val();
					
						if (typeof c !== "undefined" && c !== "" && $.inArray(c, a.category) < 0 ) {
                            	return "<div></div>";
                        }
                        if (typeof d !== "undefined" && d !== "" && d != a.venue) {
                            	return "<div></div>";
                        }
                        
						if (typeof tag !== "undefined" && tag !== "" && $.inArray(tag, a.tags) < 0 ) {
                        	return "<div></div>";
						}
                        
                        if( !wp.hooks.applyFilters( 'eventorganiser.fullcalendar_render_event', true, a, b, v ) )
                        	return "<div></div>";
                        	
                        if (! v.calendar.options.tooltip ) {
                          	return;
                        }

                        $(b).qtip({
                        	content: {
                        		text:  a.description,
                        		button: false,
                        		title: a.title
                        	},
                        	position: {
                        		my: "top center",
                        		at: "bottom center",
                        		viewport: $(window),
                        		adjust: {
                                    method: 'shift none'
                                }
                        	},
                        	hide: {
                        		fixed: true,
                        		delay: 500,
                        		effect: function (a) {$(this).fadeOut("50");}
                        	},
                        	border: {
                        		radius: 4,
                        		width: 3
                        	},
                        	style: {
                        		classes: "eo-event-toolip ui-tooltip-shadow",
                        		widget: true,
                        		tip: "topMiddle"
                        	}
                        });
                    },
                    buttonText: {
                    	today: 	EOAjaxFront.locale.today,
                    	month: 	EOAjaxFront.locale.month,
                		week: 	EOAjaxFront.locale.week,
                		day: 	EOAjaxFront.locale.day,
                		cat: 	EOAjaxFront.locale.cat,
                		venue: 	EOAjaxFront.locale.venue,
                		tag: 	EOAjaxFront.locale.tag
                    },
                    monthNames: EOAjaxFront.locale.monthNames,
                    monthNamesShort: EOAjaxFront.locale.monthAbbrev,
                	dayNames: EOAjaxFront.locale.dayNames,
                	dayNamesShort: EOAjaxFront.locale.dayAbbrev,
                	eventColor: "#21759B",
                	defaultView: calendars[i].defaultview,
                	lazyFetching: "true",
                	events: 
                		function (a, b, c, d) {
                			var request = {
                					start: jQuery.fullCalendar.formatDate(a, "yyyy-MM-dd"),
                					end: jQuery.fullCalendar.formatDate(b, "yyyy-MM-dd"),
                					timeformat:d.timeFormatphp,
                					users_events: d.users_events
                			};
                			
                			if (typeof d.category !== "undefined" &&d.category !== "") {
                				request.category = d.category;
                			}
                			if (typeof d.venue !== "undefined" &&d.venue !== "") {
                				request.venue = d.venue;
                			}
                			
                			request = wp.hooks.applyFilters( 'eventorganiser.fullcalendar_request', request, a, b, c, d );
                			
                			$.ajax({
                				url: eventorganiser.ajaxurl + "?action=eventorganiser-fullcal",
                				dataType: "JSON",
                				data: request,
                				success: c,
                				complete: function( r, status ){
                					if ( EO_SCRIPT_DEBUG ) {
                						if( status == "error" ){
                							 
                						}else if( status == "parsererror" ){
                							if( window.console ){
                								console.log( "Response is not valid JSON. This is usually caused by error notices from WordPress or other plug-ins" ); 
                								console.log( "Response reads: " + r.responseText );
                							}
                  							alert( "An error has occurred in parsing the response. Please inspect console log for details" );
                						} 
                					}
                				}
                			});
                		},
                	selectable: false,
                	weekMode: "variable",
                	aspectRatio: 1.5,
                	loading: 
                		function (a) {
                			var loading = $("#" + $(this).attr("id") + "_loading");
                			if (a) {
                				window.clearTimeout(loadingTimeOut);
                				loadingTimeOut = window.setTimeout(function () {loading.show();}, 1e3);
                			} else {
                				window.clearTimeout(loadingTimeOut);
                				loading.hide();
                			}
                		}
            	};
            	args = wp.hooks.applyFilters( 'eventorganiser.fullcalendar_options', args, calendars[i] );
            	
            	$(calendar).fullCalendar(args);
			}
	
		$(".eo-cal-filter").change(function () {
			$(".eo-fullcalendar").fullCalendar("rerenderEvents");
		});

		$('.eo-mini-calendar').datepicker({
			dateFormat: 'DD, d MM, yy',
			changeMonth: true,
			changeYear: true,
			firstDay: parseInt( eventorganiser.fullcal.firstDay, 10 ),
			buttonText: EOAjaxFront.locale.gotodate,
			monthNamesShort: EOAjaxFront.locale.monthAbbrev,
			dayNamesMin: EOAjaxFront.locale.dayAbbrev,
			nextText: EOAjaxFront.locale.nextText,
			prevText: EOAjaxFront.locale.prevText,
			showOn: 'button',
			beforeShow: function(input, inst) {
				if( inst.hasOwnProperty( 'dpDiv' ) ){
					inst.dpDiv.addClass('eo-datepicker');
				}else{
					$('#ui-datepicker-div').addClass('eo-datepicker');
				}
			},
			onSelect: function (dateText, dp) {
				var cal_id = $(this).parents('div.eo-fullcalendar').attr('id');
				$('#'+cal_id).fullCalendar('gotoDate', new Date(Date.parse(dateText)));
            		}
        	});
	}

        if ($(".eo_widget_calendar").length > 0 ) {

        	$(".eo_widget_calendar tfoot").unbind("click");
        	$(".eo_widget_calendar").off("click").on("click", 'tfoot a', function (a) {
        		a.preventDefault();
        		var b = $(this).closest(".eo_widget_calendar").attr("id");
        		
        		//Defaults
        		var cal = {showpastevents: 1, 'show-long': 0, 'link-to-single': 0 };

        		//Shortcode widget calendar
        		if( typeof eventorganiser.widget_calendars !== "undefined" && typeof eventorganiser.widget_calendars[b] !== "undefined" ){
        			cal = eventorganiser.widget_calendars[b];	
        		}
        		//Widget calendar
        		if( typeof eo_widget_cal !== "undefined" && typeof eo_widget_cal[b] !== "undefined" ){
        			cal = eo_widget_cal[b];
                }

                //Set month
                cal.eo_month = eveorg_getParameterByName("eo_month", $(this).attr("href"));

                $.getJSON(EOAjaxFront.adminajax + "?action=eo_widget_cal", cal,function (a) {$("#" + b + "_content").html(a);});
        	});
        }

        if ($('.eo-agenda-widget').length > 0) {
            function eventorganiserGetEvents(a, b) {
                $.ajax({
                    url: EOAjaxFront.adminajax,
                    dataType: "JSON",
                    data: {
                        action: "eo_widget_agenda",
                        instance_number: b.number,
                        direction: a,
                        start: b.StartDate,
                        end: b.EndDate
                    },
                    success: function (a) {
                        if (!jQuery.isArray(a) || !a[0]) {
                            return false;
                        } else {
                            b.StartDate = a[0].StartDate;
                            b.EndDate = a[a.length - 1].StartDate;
                            populateAgenda(a, b);
                        }
                    }
                });
            }
            function populateAgenda(a, b) {
                var agendaWidget = $("#" + b.id + "_container");
                var dateList = agendaWidget.find("ul.dates");
                var dates = dateList.find("li");
                $(dates).remove();
                var current = false;
                for (i = 0; i < a.length; i++) {
                    var d = new Date(a[i].StartDate),currentList,c;
                    
                    if ( current === false || current != a[i].StartDate && b.mode == "day" ) {
                        current = a[i].StartDate;
                        currentList = $('<li class="date" >' + a[i].display + '<ul class="a-date"></ul></li>');
                        dateList.append(currentList);
                    }
                    if( b.add_to_google ){
                    	c = $('<li class="event"></li>').append('<span class="cat"></span><span><strong>' + a[i].time + ": </strong></span>" + a[i]
                        	.post_title)
                        	.append('<div class="meta" style="display:none;"><span>' + a[i].link + "</span><span>   </span><span>" + a[i]
                        	.Glink + "</span></div>");
                    }else{
                    	c = $('<li class="event"></li>').append("<a class='eo-agenda-event-permalink' href='"+a[i].event_url+"'><span class='cat'></span><span><strong>" + a[i].time + ": </strong></span>" + a[i]
                        	.post_title+"</a>");
                    }
                    
                    c.find("span.cat")
                        .css({
                        background: a[i].color
                    });
                    currentList.append(c);
                }
                dates = dateList.find("li");
                var events_el = agendaWidget.find("ul li.event");
                events_el.on("click", function () {
                    $(this).find(".meta").toggle("400");
                });
            }
            for (var agenda in eo_widget_agenda) {
                agenda = eo_widget_agenda[agenda];
                var d = new Date();
                agenda.StartDate = $.fullCalendar.formatDate(d, "yyyy-MM-dd");
                agenda.EndDate = agenda.StartDate;
                eventorganiserGetEvents( 1, agenda );
            }
            $(".eo-agenda-widget .agenda-nav span.button").click(function (a) {
                var id = $(this).parents(".eo-agenda-widget").attr("id");
                agenda = eo_widget_agenda[id];
                a.preventDefault();
                var dir = false;
                if ($(this).hasClass("next")) {
                	dir = "+1";
                } else if ($(this).hasClass("prev")) {
                    dir = "-1";
                } else {
                    var par = $(this).parent();
                    if (par.hasClass("prev")) {
                        dir = "-1";
                    } else {
                        dir = "+1";
                    }
                }
                eventorganiserGetEvents( dir, agenda );
            });
        }
    });
})(jQuery);

function eveorg_getParameterByName(a, b) {
    a = a.replace(/[\[]/, "\\[")
        .replace(/[\]]/, "\\]");
    var c = "[\\?&]" + a + "=([^&#]*)";
    var d = new RegExp(c);
    var e = d.exec(b);
    if (e === null) return "";
    else return decodeURIComponent(e[1].replace(/\+/g, " "));
}

function eo_load_map() {
	var maps = eventorganiser.map;

	for (var i = 0; i < maps.length; i++) {

		if ( null === document.getElementById( "eo_venue_map-" + (i + 1))) {
			continue;
		}

		//Store markers
		eventorganiser.map[i].markers = {};
		var locations = maps[i].locations;

		var map_options = {
			zoom: maps[i].zoom,
			scrollwheel: maps[i].scrollwheel,
			zoomControl: maps[i].zoomcontrol,
			rotateControl: maps[i].rotatecontrol,
			panControl: maps[i].pancontrol,
			overviewMapControl: maps[i].overviewmapcontrol,
			streetViewControl: maps[i].streetviewcontrol,
			draggable: maps[i].draggable,
			mapTypeControl: maps[i].maptypecontrol,
			mapTypeId: google.maps.MapTypeId[maps[i].maptypeid]
		};

		map_options = wp.hooks.applyFilters( 'eventorganiser.google_map_options', map_options);

		var map = new google.maps.Map(document.getElementById("eo_venue_map-" + (i + 1)), map_options);

		if (maps[i].centerlng && maps[i].centerlat) {
			map.setCenter(new google.maps.LatLng(maps[i].centerlat, maps[i].centerlng));
		} else if( locations.length > 1 ) {
			var bounds = new google.maps.LatLngBounds();
			for(var k = 0; k < locations.length; k++) {
				var locationLatLng = new google.maps.LatLng(locations[k].lat, locations[k].lng);
				bounds = bounds.extend(locationLatLng);
			}
			map.fitBounds(bounds);
		} else {
			map.setCenter(new google.maps.LatLng(locations[0].lat, locations[0].lng));
		}

		// place the markers on the map
		for(var j = 0; j < locations.length; j++) {
			var lat = locations[j].lat;
			var lng = locations[j].lng;
			if (lat !== undefined && lng !== undefined) {
				var locationLatLng = new google.maps.LatLng(lat, lng);

				var marker_options = {
					venue_id: locations[j].venue_id,
					position: locationLatLng,
					map: map,
					content:locations[j].tooltipContent,
					icon: locations[j].icon
				};

				marker_options = wp.hooks.applyFilters( 'eventorganiser.venue_marker_options', marker_options );

				// create closure to capture marker variable
				(function() {
					var marker = new google.maps.Marker(marker_options);				

					eventorganiser.map[i].markers[locations[j].venue_id] = marker;
					if( maps[i].tooltip ){
						var infoWindow = new google.maps.InfoWindow({
							content: locations[j].tooltipContent
							, maxWidth: 200
						});
						google.maps.event.addListener(marker, 'click',function(){
							infoWindow.open(map, marker); 
						});
					}
				})();
			}
		}
	}
}

