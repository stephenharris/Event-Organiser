(function ($) {
jQuery(document).ready(function () {

	function eventorganiser_cat_dropdown(options){

		var terms = options.categories;

		var html="<select class='eo-cal-filter' id='eo-event-cat'>";
		html+="<option value=''>"+options.buttonText.cat+"</option>";
		for (var i=0; i<terms.length; i++){
			html+= "<option class='cat-colour-"+terms[i].colour+" cat' value='"+terms[i].slug+"'>"+terms[i].name+"</option>";
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

	function eventorganiser_venue_dropdown(options){

		var venues = options.venues;

		var html="<select class='eo-cal-filter' id='eo-event-venue'>";
		html+="<option value=''>"+options.buttonText.venue+"</option>";

		for (var i=0; i<venues.length; i++){
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
			if( $('#eo-upcoming-dates li:hidden').length == 0 ){
				$('#eo-upcoming-dates-more,#eo-upcoming-dates-pipe').hide();
			}
		});
	}

        if ($(".eo-fullcalendar").length > 0) {
		var calendars = EOAjax.calendars;
		var loadingTimeOut;
            	for (var i = 0; i < calendars.length; i++) {
			var calendar = "#eo_fullcalendar_" + (i + 1);

                        if (typeof calendars[i].category === "undefined") {
	                             calendars[i].category ='';
                        }
                        if (typeof calendars[i].venue === "undefined") {
	                             calendars[i].venue ='';
                        }
                	$(calendar).fullCalendar({
				id: calendar,
				year: calendars[i].year ? calendars[i].year : undefined,
				month: calendars[i].month ? calendars[i].month : undefined,
				date: calendars[i].date ? calendars[i].date : undefined,
				category: calendars[i].event_category,
				venue: calendars[i].event_venue,
				customButtons:{
					category:  eventorganiser_cat_dropdown,
					venue:  eventorganiser_venue_dropdown,
					'goto': eventorganiser_mini_calendar
				},
				theme: true,
				categories: EOAjax.fullcal.categories,
				venues: EOAjax.fullcal.venues,
				timeFormatphp: calendars[i].timeformatphp,
				timeFormat: calendars[i].timeformat,
				editable: false,
                    		tooltip: calendars[i].tooltip,
                    		firstDay: parseInt(EOAjax.fullcal.firstDay),
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
				eventRender: function (a, b, v) {
                        		var c = $(v.calendar.options.id).find(".filter-category .eo-cal-filter").val();
                        		var d = $(v.calendar.options.id).find(".filter-venue .eo-cal-filter").val();
					
                        		if (typeof c !== "undefined" && c != "" && $.inArray(c, a.category) < 0) {
                            			return "<div></div>";
                        		}
                        		if (typeof d !== "undefined" && d != "" && d != a.venue) {
                            			return "<div></div>";
                        		}
                        		if (! v.calendar.options.tooltip ) {
                            			return
                        		}

					$(b).qtip({
                            			content: {
							text:  a.description,
							button: "x",
							title: a.title
						},
						position: {
							my: "top center",
							at: "bottom center"
						},
						hide: {
							fixed: true,
							delay: 500,
							effect: function (a) {
								$(this).fadeOut("50")
							}
						},
						border: {
							radius: 4,
							width: 3
						},
						style: {
							classes: "ui-tooltip-shadow",
							widget: true,
							tip: "topMiddle"
						}
					})
                    		},
				buttonText: {
		                        today: EOAjaxFront.locale.today,
                		        month: EOAjaxFront.locale.month,
                		        week: EOAjaxFront.locale.week,
                		        day: EOAjaxFront.locale.day,
                		        cat: EOAjaxFront.locale.cat,
                		        venue: EOAjaxFront.locale.venue
                		    },
                		    monthNames: EOAjaxFront.locale.monthNames,
                		    monthNamesShort: EOAjaxFront.locale.monthAbbrev,
                		    dayNames: EOAjaxFront.locale.dayNames,
                		    dayNamesShort: EOAjaxFront.locale.dayAbbrev,
                		    eventColor: "#21759B",
                		    defaultView: calendars[i].defaultview,
                		    lazyFetching: "true",
                		    events: function (a, b, c, d) {
                		        var request = {
						start: jQuery.fullCalendar.formatDate(a, "yyyy-MM-dd"),
						end: jQuery.fullCalendar.formatDate(b, "yyyy-MM-dd"),
						timeformat:d.timeFormatphp
                		        };
                		        if (typeof d.category !== "undefined" &&d.category != "") {
                		            request.category = d.category
                		        }
                		        if (typeof d.venue !== "undefined" &&d.venue != "") {
                		            request.venue = d.venue
					}
                		        jQuery.ajax({
                		            url: EOAjax.ajaxurl + "?action=eventorganiser-fullcal",
                		            dataType: "JSON",
                		            data: request,
                		            success: c
                		        })
                		    },
                		    selectable: false,
                		    weekMode: "variable",
                		    aspectRatio: 1.5,
                		    loading: function (a) {
                		        var loading = $("#" + $(this).attr("id") + "_loading");
                		        if (a) {
                		            window.clearTimeout(loadingTimeOut);
                		            loadingTimeOut = window.setTimeout(function () {
                		                loading.show()
                		            }, 1e3)
                		        } else {
                		            window.clearTimeout(loadingTimeOut);
                		            loading.hide()
                		        }
                		    }
                		})
			}
	
		$(".eo-cal-filter").change(function () {
			$(".eo-fullcalendar").fullCalendar("rerenderEvents")
		})

		$('.eo-mini-calendar').datepicker({
			dateFormat: 'DD, d MM, yy',
			changeMonth: true,
			changeYear: true,
			dateFormat: 'DD, d MM, yy',
			firstDay: parseInt(EOAjax.fullcal.firstDay),
			buttonText: EOAjaxFront.locale.gotodate,
			monthNamesShort: EOAjaxFront.locale.monthAbbrev,
			dayNamesMin: EOAjaxFront.locale.dayAbbrev,
			showOn: 'button',
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
			var cal = {showpastevents: 1};

			//Shortcode widget calendar
			if( typeof EOAjax !== "undefined" && typeof EOAjax.widget_calendars !== "undefined" ){
				cal = EOAjax.widget_calendars[b];	
			}
			//Widget calendar
                	if (typeof eo_widget_cal !== "undefined") {
                    		cal = eo_widget_cal[b];
                	}

			//Set month
			cal.eo_month = eveorg_getParameterByName("eo_month", $(this).attr("href"));

			$.getJSON(EOAjaxFront.adminajax + "?action=eo_widget_cal", cal,function (a) {$("#" + b + "_content").html(a)})
            	})
        }

        if ($('.eo-agenda-widget').length > 0) {
            function getEvents(a, b) {
                $.ajax({
                    url: EOAjaxFront.adminajax,
                    dataType: "JSON",
                    data: {
                        action: "eo_widget_agenda",
                        instance_number: b["number"],
                        direction: a,
                        start: b.StartDate,
                        end: b.EndDate
                    },
                    success: function (a) {
                        if (!jQuery.isArray(a) || !a[0]) {
                            return false
                        } else {
                            b["StartDate"] = a[0].StartDate;
                            b["EndDate"] = a[a.length - 1].StartDate;
                            populateAgenda(a, b)
                        }
                    }
                })
            }
            function populateAgenda(a, b) {
                var agendaWidget = $("#" + b.id + "_container");
                var dateList = agendaWidget.find("ul.dates");
                var dates = dateList.find("li");
                $(dates).remove();
                var current = "";
                for (i = 0; i < a.length; i++) {
                    var d = new Date(a[i].StartDate);
                    if (current == "" || current != a[i].StartDate && b
                        .mode == "day") {
                        current = a[i].StartDate;
                        var currentList = $('<li class="date" >' + a[i].display + '<ul class="a-date"></ul></li>');
                        dateList.append(currentList)
                    }
		if( b.add_to_google ){
                    var c = $('<li class="event"></li>').append('<span class="cat"></span><span><strong>' + a[i].time + ": </strong></span>" + a[i]
                        .post_title)
                        .append('<div class="meta" style="display:none;"><span>' + a[i].link + "</span><span>   </span><span>" + a[i]
                        .Glink + "</span></div>");
		}else{
                    var c = $('<li class="event"></li>').append("<a class='eo-agenda-event-permalink' href='"+a[i].event_url+"'><span class='cat'></span><span><strong>" + a[i].time + ": </strong></span>" + a[i]
                        .post_title+"</a>")
		}
                 c.find("span.cat")
                        .css({
                        background: a[i].color
                    });
                    currentList.append(c)
                }
                dates = dateList.find("li");
                var events_el = agendaWidget.find("ul li.event");
                events_el.on("click", function () {
                    $(this).find(".meta")
                        .toggle("400")
                })
            }
            for (var agenda in eo_widget_agenda) {
                agenda = eo_widget_agenda[agenda];
                var d = new Date;
                agenda.StartDate = $.fullCalendar.formatDate(d, "yyyy-MM-dd");
                agenda.EndDate = agenda.StartDate;
                getEvents(1, agenda)
            }
            $(".eo-agenda-widget .agenda-nav span.button").click(function (a) {
                var id = $(this).parents(".eo-agenda-widget").attr("id");
                agenda = eo_widget_agenda[id];
                a.preventDefault();
                if ($(this).hasClass("next")) {
                    var dir = "+1"
                } else if ($(this).hasClass("prev")) {
                    var dir = "-1"
                } else {
                    var par = $(this).parent();
                    if (par.hasClass("prev")) {
                        var dir = "-1"
                    } else {
                        var dir = "+1"
                    }
                }
                getEvents(dir, agenda)
            })
        }
    });
})(jQuery);

function eveorg_getParameterByName(a, b) {
    a = a.replace(/[\[]/, "\\[")
        .replace(/[\]]/, "\\]");
    var c = "[\\?&]" + a + "=([^&#]*)";
    var d = new RegExp(c);
    var e = d.exec(b);
    if (e == null) return "";
    else return decodeURIComponent(e[1].replace(/\+/g, " "))
}

function eo_load_map() {
	var maps = EOAjax.map;
	for (var i = 0; i < maps.length; i++) {
		
		if ( null === document.getElementById( "eo_venue_map-" + (i + 1) ) )
		    continue;
		
		var locations = maps[i].locations;
		var b = {
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
		var map = new google.maps.Map(document.getElementById("eo_venue_map-" + (i + 1)), b);

		//  Create a new viewpoint bound
		var bounds = new google.maps.LatLngBounds();

		var LatLngList = new Array();
		for( var j=0; j<locations.length; j++){
			var lat = locations[j].lat;
        		var lng = locations[j].lng;
        		if (lat !== undefined && lng != undefined) {
				LatLngList.push(new google.maps.LatLng(lat, lng));
			  	bounds.extend (LatLngList[j]);
				var c = new google.maps.Marker({
					position: LatLngList[j],
	                		map: map,
					content:locations[j].tooltipContent
	            		});
				if( 'false' != maps[i].tooltip ){
					google.maps.event.addListener(c, 'click',eventorganiser_venue_tooltip);
				}
        		}
		}

   		if( locations.length > 1 ){	
			//  Fit these bounds to the map
			map.fitBounds (bounds);
			//google.maps.event.addListenerOnce(map, 'zoom_changed', function() {map.setZoom(zoom);});
		}else{
			map.setCenter ( LatLngList[0]);
		}

	}//Foreach map
}
/**
* @constructor
*/
function eventorganiser_venue_tooltip() {

	// Grab marker position: convert world point into pixel point
	var map = this.getMap();
	var pixel = this.getMap().getProjection().fromLatLngToPoint(this.position);
         var topRight=map.getProjection().fromLatLngToPoint(map.getBounds().getNorthEast()); 
         var bottomLeft=map.getProjection().fromLatLngToPoint(map.getBounds().getSouthWest()); 
         var scale=Math.pow(2,map.getZoom()); 
	pixel=  new google.maps.Point((pixel.x- bottomLeft.x)*scale,(pixel.y-topRight.y)*scale);

	//var pixel = LatLngToPixel.fromLatLngToContainerPixel(this.position);
	var pos = [ pixel.x, pixel.y ];

	if(this.tooltip){
		this.tooltip.qtip('api').set('position.target', pos);
		this.tooltip.qtip('show');
		return;
	}
	jQuery(this.getMap().getDiv()).css({overflow: 'visible'});

	// Create the tooltip on a dummy div and store it on the marker
	 this.tooltip =jQuery('<div />').qtip({
        	content: {
			text: this.content
		},
		border: {
			radius: 4,
			width: 3
		},
		style: {
			classes: "ui-tooltip-shadow",
			widget: true
		},
        	position: {
        	    at: "right center",
        	    my: "top center",
        	    target: pos,
        	    container: jQuery(this.getMap().getDiv())
        	},
        	show: {
        	    ready: true,
        	    event: false,
        	    solo: true
        	},
        	hide: {
        	   event: 'mouseleave unfocus'
        	}
	    });
  }
