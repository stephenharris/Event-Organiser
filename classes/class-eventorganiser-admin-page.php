<?php
class EventOrganiser_Admin_Page{

	static $hook;
	static $title;
	static $menu;
	static $permissions;
	static $slug;
	static $page;

	function __construct() {
		add_action('admin_init', array($this,'admin_init_actions'));
		add_action('admin_menu', array($this,'add_page'));
		add_action('init', array($this,'set_constants'));
	}

	function set_constants(){
	}

	function add_page(){
		self::$page = add_submenu_page($this->hook,$this->title, $this->menu, $this->permissions,$this->slug,  array($this,'render_page'),10);
		add_action('load-'.self::$page,  array($this,'page_actions'),9);
		add_action('admin_print_scripts-'.self::$page,  array($this,'page_styles'),10);
		add_action('admin_print_styles-'.self::$page,  array($this,'page_scripts'),10);
		add_action("admin_footer-".self::$page,array($this,'footer_scripts'));
	}
	function footer_scripts(){
	}

	function page_scripts(){
	}
	/*
	* Actions to be taken prior to page loading. This is after headers have been set.
        * @uses load-$hook
	*/
	function page_actions(){
	}

	function page_styles(){
	}

	function admin_init_actions(){
	}

	function current_action(){

		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			return $_REQUEST['action'];

		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			return $_REQUEST['action2'];

		return false;
	}

	function init(){
	}

	function render_page(){
		$this->init();
		$this->display();
	}
	function display(){
	}
}
