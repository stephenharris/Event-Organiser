<?php

	if(isset($_REQUEST['action'])&&$_REQUEST['action']='myajax_submit'):
		do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
		// do_action( 'wp_ajax_' . $_POST['action'] );
	endif;

	add_action( 'wp_ajax_nopriv_myajax-submit', 'myajax_submit' );
	add_action( 'wp_ajax_myajax-submit', 'myajax_submit' );
 
	function myajax_submit() {
	
		// get the submitted parameters
		global $wpdb, $eventorganiser_venue_table;

		$EO_Venues = new EO_Venues;
		$EO_Venues->query(array('s'=>$_GET["term"]));

		$venues_array = $EO_Venues->results;

		//echo JSON to page  
		$response = $_GET["callback"] . "(" . json_encode($venues_array) . ")";  
		echo $response;  
		exit;
}
?>
