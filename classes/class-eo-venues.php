<?php
/**
 * Class which manipulates arrays of Venues. Allows querying and deleting of Venues.
 */
class EO_Venues{

	var $count=0;
	var $pages=0;
	var $results;

	function query($args=array() ){
		global $wpdb, $eventorganiser_events_table, $eventorganiser_venue_table;

		/*
		*Use defaults where key not set
		*/
		$defaults = array('venue'=>array(),'offset'=>0,'orderby'=> 'name','order'=> 'ASC','s'=>'','limit'=>-1); 	
		$query_array = array_merge($defaults,$args);

		/*
		White list keys - if a key is not recognised, chuck it.
		*/
		$whitelist = array('offset','venue','s','limit','orderby','order'); 	
		$query_array = array_intersect_key($query_array, array_flip($whitelist));

		/*
		Construct query
		*/
		$select = "SELECT * FROM $eventorganiser_venue_table ";

		$where=" WHERE 1=1 ";
		$orderby="";
		$order="";
		$limit="";

		$venue = $query_array['venue'];
		
		if(!empty($venue)):
			$argtype = array_fill(0, count($query_array['venue']), '%d');
			$where .= " AND {$eventorganiser_venue_table}.venue_id=".implode( " OR {$eventorganiser_venue_table}.venue_id=", $argtype);
			$wpdb->prepare($where,$argtype);
		endif;

		if($query_array['s']!=''):
			$where .= $wpdb->prepare(" AND (venue_name LIKE'%%%s%%') ",$query_array['s']);
		endif;

		$this->count = $wpdb->get_var("SELECT COUNT(*) FROM $eventorganiser_venue_table $where");

		switch($query_array['orderby']):
			case 'name':
				$orderby = "ORDER BY venue_name ";
				break;
			case 'address':
				$orderby = "ORDER BY venue_address ";
				break;
			case 'postcode':
				$orderby = "ORDER BY venue_postal ";
				break;
			case 'country':
				$orderby = "ORDER BY venue_country ";
				break;
			default:
				$orderby = "ORDER BY venue_name ";
		endswitch;

		$order = ($query_array['order']=='asc' ? ' asc ' : ' desc ');

		$limit_int = intval($query_array['limit']);
		$offset_int = intval($query_array['offset']);
		$offset_int  = max(0,$offset_int);

		if($limit_int >-1):
			$limit=$wpdb->prepare("LIMIT %d, %d",$offset_int,$limit_int);
		endif;
	
		/*
		*Finally construct the query statement
		*/
		$sql = $select.$where.$orderby.$order.$limit;
		
		$results = $wpdb->get_results($sql,ARRAY_A);
		$this->results =$results;
}


	function count(){
		global $wpdb, $eventorganiser_venue_table;
		return $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $eventorganiser_venue_table"));
	}


	function doaction( $args = array(), $action='' ){
		global $wpdb, $eventorganiser_events_table, $eventorganiser_venue_table;
		global $EO_Errors;
		$where ="";
		switch($action):
			case 'delete':
				//security check
				if( !check_admin_referer('bulk-venues')){
					$EO_Errors = new WP_Error('eo_error', __("You do not have permission to edit this venue",'eventorganiser'));
				}else{			
					if(!empty($args)):
						$argtype = array_fill(0, count($args), '%d');
						$where = "WHERE {$eventorganiser_venue_table}.venue_id=".implode( " OR {$eventorganiser_venue_table}.venue_id=", $argtype);
						$sql = "DELETE FROM $eventorganiser_venue_table $where";
						$del = $wpdb->query($wpdb->prepare($sql,$args));
						if($del==0){
							$EO_Errors = new WP_Error('eo_error', __("Venue(s) <strong>were not </strong> deleted",'eventorganiser'));
						}else{
							$EO_Errors = new WP_Error('eo_notice', __("Venue(s) <strong>deleted</strong>",'eventorganiser'));
						}
					else:
					endif;
				}
			break;

			case 'add':
				 $EO_Venue = new EO_Venue;
				 $EO_Venue->add($args); 
			break;

			case 'create':
				global $EO_Venue; 
				 $EO_Venue = new EO_Venue();
			break;

			case 'edit':
				global $EO_Venue; 
				 $EO_Venue = new EO_Venue($args);
			break;

			default:
		endswitch;
	}	
}
?>
