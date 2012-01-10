<?php
/**
 * Class used for displaying venue table and handling interations
 */

/*
 *The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary.
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class EO_List_Table extends WP_List_Table {
        
    /*
     * Constructor. Set some default configs.
     */
	function __construct(){
		global $status, $page;
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'venue',     //singular name of the listed records
			'plural'    => 'venues',    //plural name of the listed records
			'ajax'      => true        //does this table support ajax?
        	) );
	    }
    
    /*
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     */
    function column_default($item, $column_name){
		 switch($column_name){
			case 'venue_address':
			case 'venue_postal':
			case 'venue_country':
			case 'venue_slug':
				return $item[$column_name];
			default:
				return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
    }
    
        
    /*
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     */
    function column_name($item){

        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?post_type=event&page=%s&action=%s&venue=%d">Edit</a>',$_REQUEST['page'],'edit',$item['venue_id']),
            'delete'    => '<a href="'.wp_nonce_url(sprintf('?post_type=event&page=%s&action=%s&venue=%d',$_REQUEST['page'],'delete',$item['venue_id']), 'bulk-venues' ).'">Delete </a>',
            'view'    => sprintf('<a href="%s">View</a>',  eo_get_venue_link($item['venue_slug'])),
        );
        
        //Return the title contents
        return sprintf('<a href="?post_type=event&page=%1$s&action=%2$s&venue=%3$d" class="row-title">%4$s</a>%5$s',
            /*$1%s*/ $_REQUEST['page'],
            /*$2%s*/ 'edit',
            /*$3%s*/ $item['venue_id'],
            /*$4%s*/ $item['venue_name'],
            /*$5%s*/ $this->row_actions($actions)
        );
    }
    
    /*
     * Checkbox column for Bulk Actions.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     */
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("venue")
            /*$2%s*/ $item['venue_id']                //The value of the checkbox should be the record's id
        );
    }
    
   
    /*
     * Set columns sortable
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     */
    function get_sortable_columns() {
        $sortable_columns = array(
            'name'     => array('name',true),     //true means its sorted by default
            'venue_address'     => array('address',false),     //true means its sorted by default
            'venue_postal'     => array('postcode',false),     //true means its sorted by default
            'venue_country'     => array('country',false),     //true means its sorted by default
        );
        return $sortable_columns;
    }


    /*
     * Set bulk actions
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     */
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete selected'
        );
        return $actions;
    }
    

     /*
     * Echos the row, after assigning it an ID based ont eh venue being shown. Assign appropriate class to alternate rows.
     */       
	function single_row( $item ) {
		static $row_class = '';
		$row_id = 'id="venue-'.$item['venue_id'].'"';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );
		echo '<tr' .$row_class.' '.$row_id.'>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

    /*
     * Prepare venues for display
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items() {

        //Retrieve page number for pagination
         $current_page = $this->get_pagenum();

	//First, lets decide how many records per page to show
	$per_page = 20;
	$screen = get_current_screen();
	if(get_user_option($screen->id.'_per_page'))
		$per_page = (int) get_user_option($screen->id.'_per_page');
         
	//Get the columns, the hidden columns an sortable columns
	 $columns = get_column_headers('event_page_venues');
        $hidden = get_hidden_columns('event_page_venues');
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
                
       //Fetch the venues
	global $EO_Venues; 
      
	//Check search term
	$search_term = false;
	if ( isset($_REQUEST['s']) && $_REQUEST['s'] )	
		$search_term = esc_attr($_REQUEST['s']);

	//Check order
	$orderby = 'name';
	if ( isset($_REQUEST['orderby']) && $_REQUEST['orderby'] )	
		$orderby = esc_attr($_REQUEST['orderby']);
	
	$order = 'asc';
	if ( isset($_REQUEST['order']) && $_REQUEST['order'] )	
		$order = esc_attr($_REQUEST['order']);

	//Query venues
	$EO_Venues->query(array('s'=>$search_term,'limit'=>$per_page,'offset'=>($current_page-1)*$per_page, 'orderby'=>$orderby, 'order'=>$order));


	//Display result
	$this->items = $EO_Venues->results;
        
        
        //REQUIRED. We also have to register our pagination options & calculations.
        $this->set_pagination_args( array(
            'total_items' => $EO_Venues->count,
            'per_page'    => $per_page,
            'total_pages' => ceil($EO_Venues->count/$per_page)   //WE have to calculate the total number of pages
        ) );

    }
    
}?>
