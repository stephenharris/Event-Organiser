<?php

	if(isset($_REQUEST['action'])&&$_REQUEST['action']=='eo-search-venue'):
		do_action( 'wp_ajax_' . $_REQUEST['action'] );
	endif;

	add_action( 'wp_ajax_eo-search-venue', 'eventorganiser_search_venues' ); 
	function eventorganiser_search_venues() {
		// Query the venues with the given term
		$EO_Venues = new EO_Venues;
		$EO_Venues->query(array('s'=>$_GET["term"]));

		$venues_array = $EO_Venues->results;

		//echo JSON to page  
		$response = $_GET["callback"] . "(" . json_encode($venues_array) . ")";  
		echo $response;  
		exit;
}
?>
