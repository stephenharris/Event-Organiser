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

class EO_Venue_List_Table extends WP_List_Table {
        
    /*
     * Constructor. Set some default configs.
     */
	function __construct(){
		global $status, $page;
		//Set parent defaults
		parent::__construct( array(
			'singular'  => __('venue','eventorganiser'),     //singular name of the listed records
			'plural'    =>  __('venues','eventorganiser'),    //plural name of the listed records
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
		$term_id = (int) $item->term_id;
		 switch($column_name){
			case 'venue_address':
				$address = eo_get_venue_address($term_id);
				return esc_html($address['address']);
			case 'venue_postal':
				$address = eo_get_venue_address($term_id);
				return esc_html($address['postcode']);
			case 'venue_city':
				$address = eo_get_venue_address($term_id);
				return esc_html($address['city']);
			case 'venue_country':
				$address = eo_get_venue_address($term_id);
				return esc_html($address['country']);
			case 'venue_slug':
				return esc_html($item->slug);
			case 'posts':
				return intval($item->count);
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
	$term_id = (int) $item->term_id;

        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?post_type=event&page=%s&action=%s&event-venue=%s">'.__('Edit').'</a>',$_REQUEST['page'],'edit',$item->slug),
            'delete'    => '<a href="'.wp_nonce_url(sprintf('?post_type=event&page=%s&action=%s&event-venue=%s',$_REQUEST['page'],'delete',$item->slug), 'eventorganiser_delete_venue_'.$item->slug).'">'.__('Delete').' </a>',
            'view'    => sprintf('<a href="%s">'.__('View').'</a>',  eo_get_venue_link($term_id)),
        );
        
        //Return the title contents
        return sprintf('<a href="?post_type=event&page=%1$s&action=%2$s&event-venue=%3$s" class="row-title">%4$s</a>%5$s',
            /*$1%s*/ $_REQUEST['page'],
            /*$2%s*/ 'edit',
            /*$3%s*/ $item->slug,
            /*$4%s*/ $item->name,
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
            /*$1%s*/ 'event-venue',  
            /*$2%s*/ $item->slug       //The value of the checkbox should be the record's id
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
            'venue_city'     => array('city',false),     //true means its sorted by default
            'venue_country'     => array('country',false),     //true means its sorted by default
            'venue_slug'     => array('slug',false),     //true means its sorted by default
            'posts'     => array('count',false),     //true means its sorted by default
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
            'delete'    => __('Delete')
        );
        return $actions;
    }
    

     /*
     * Echos the row, after assigning it an ID based ont eh venue being shown. Assign appropriate class to alternate rows.
     */       
	function single_row( $item ) {
		static $row_class = '';
		$row_id = 'id="venue-'.$item->term_id.'"';
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
         $current_page = (int) $this->get_pagenum();

	//First, lets decide how many records per page to show
	$screen = get_current_screen();
	$per_page = $this->get_items_per_page( 'edit_event_venue_per_page' );

	//Get the columns, the hidden columns an sortable columns
	$columns = get_column_headers('event_page_venues');
	$hidden = get_hidden_columns('event_page_venues');
	$sortable = $this->get_sortable_columns();
	$this->_column_headers = array($columns, $hidden, $sortable);
	$taxonomy ='event-venue';

	$search = (!empty( $_REQUEST['s'] ) ? trim( stripslashes( $_REQUEST['s'] ) ) : '');
	$orderby =( !empty( $_REQUEST['orderby'] )  ? trim( stripslashes($_REQUEST['orderby'])) : '');
	$order =( !empty( $_REQUEST['order'] )  ? trim( stripslashes($_REQUEST['order'])) : '');

	//Display result
	$this->items = get_terms('event-venue',array(
			'hide_empty'=>false,
			'search'=>$search,
			'offset'=> ($current_page-1)*$per_page,
			'number'=>$per_page,
			 'orderby'=>$orderby,
			 'order'=>$order			
		)
	);

	$this->set_pagination_args( array(
		'total_items' => wp_count_terms('event-venue', compact( 'search', 'orderby' ) ),
		'per_page' => $per_page,
	) );     

    }
    
}?>
