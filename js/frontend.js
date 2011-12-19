jQuery(document).ready(function() {

	if(jQuery("#eo_venue_map").length>0){
		var eo_venue_Lat = jQuery("#eo_venue_Lat").val();
		var eo_venue_Lng = jQuery("#eo_venue_Lng").val();;
		
		var map;
		eo_load_map(eo_venue_Lat,eo_venue_Lng);
	}

	if(jQuery("#eo_calendar").length>0 && typeof EOAjax.ajaxurl !== undefined){
		jQuery('#eo_calendar tfoot').unbind("click");
		jQuery('#eo_calendar tfoot a').die("click");
		jQuery('#eo_calendar tfoot a').live('click', function(e){
			e.preventDefault();
			jQuery.getJSON(
				EOAjax.ajaxurl+"?action=eo_widget_cal",{
					eo_month: getParameterByName('eo_month',jQuery(this).attr('href')),
					eo_query: EOAjax.query
				},
			  	function(data){
					jQuery('#eo_calendar').html(data);
				});
		});	
	}
});

	function getParameterByName(name,url){
		name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
		var regexS = "[\\?&]" + name + "=([^&#]*)";
		var regex = new RegExp(regexS);
		var results = regex.exec(url);
		if(results == null)
			return "";
		else
			return decodeURIComponent(results[1].replace(/\+/g, " "));
	}


	function eo_load_map(lat,lng){
		var latlng = new google.maps.LatLng(lat,lng);
		var myOptions = {
			zoom: 15,
			center: latlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		map = new google.maps.Map(document.getElementById("eo_venue_map"),myOptions);
		var marker = new google.maps.Marker({
			position: latlng, 
			map: map
		});
	}
