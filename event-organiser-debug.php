<?php
/****** DEBUG PAGE ******/
if ( !class_exists( 'EventOrganiser_Admin_Page' ) ){
    require_once( EVENT_ORGANISER_DIR.'classes/class-eventorganiser-admin-page.php' );
}
/**
 * @ignore
 */
class EventOrganiser_Debug_Page extends EventOrganiser_Admin_Page
{
	function set_constants(){
		$this->hook = 'edit.php?post_type=event';
		$this->title = __( 'System Info', 'eventorganiser' );
		$this->menu = __( 'System Info', 'eventorganiser' );
		$this->permissions = 'manage_options';
		$this->slug = 'debug';
	}

	function add_page(){		
		self::$page = add_submenu_page($this->hook,$this->title, $this->menu, $this->permissions,$this->slug,  array($this,'render_page'),10);
		add_action('load-'.self::$page,  array($this,'page_actions'),9);
		add_action('admin_print_scripts-'.self::$page,  array($this,'page_styles'),10);
		add_action('admin_print_styles-'.self::$page,  array($this,'page_scripts'),10);
		add_action("admin_footer-".self::$page,array($this,'footer_scripts'));
		if( !defined( "WP_DEBUG" ) || !WP_DEBUG ){
			remove_submenu_page('edit.php?post_type=event',$this->slug);
		}
	}
	
	function page_actions(){
		
		
		$eo_debugger = new EventOrganiser_Debugger();
		$eo_debugger->set_prequiste( 'WordPress', '3.3', '3.5.1');
		//$eo_debugger->set_known_plugin_conflicts();
		//$eo_debugger->set_known_theme_conflicts();
		$eo_debugger->set_db_tables( 'eo_events', 'eo_venuemeta' );
		
		do_action_ref_array( 'eventorganiser_debugger_setup', array( &$eo_debugger ) );
		
		$this->debugger = $eo_debugger;
		
		if( !empty( $_GET['eo-download-debug-file'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( $_GET['eo-download-debug-file'], 'eo-download-debug-file' ) ){
			$this->debugger->download_debug_info();
		}
		
		wp_enqueue_style('eventorganiser-style' );
	}

	function display(){
	?>
	<div class="wrap">
		<?php screen_icon( 'edit' );?>
		
		<h2><?php _e('System Info','eventorganiser');?> </h2>
		
		<?php  $eo_debugger = $this->debugger; ?>
		
		<p>
		<?php 
		_e( "This page highlights useful information for debugging. If you're reporting a bug, please include this information.", 'eventorganiser' );
		echo " ";
		_e( "The 'system info' link in under the Events admin tab is only visible to admins and only when <code>WP_DEBUG</code> is set to <code>true</code>.", 'eventorganiser' );
		?>
		</p>
		<p class="description">
		<?php 
		_e( "Most bugs arise from theme or plug-in conflicts. You can check this by disabling all other plug-ins and switching to TwentyTweleve.", 'eventorganiser' );
		echo " ";
		_e( "To help speed things along, if you report a bug please indicate if you have done so. Once the plug-in or theme has been identified it is often easy to resolve the issue.", 'eventorganiser' );
		echo " ";
		_e( "Below any <strong>known</strong> plug-in and theme conflicts are highlighted in red.", 'eventorganiser' );
		?>
		</p>
		
		<?php 
			printf( 
				'<p><a href="%s" class="button secondary">%s</a></p>',
				add_query_arg( 'eo-download-debug-file', wp_create_nonce( 'eo-download-debug-file' ) ),
				__( "Download system information file", 'eventorganiser' )
			);
		?>
				
		<table class="widefat">
				<tr>
					<th><?php esc_html_e('Site url');?></th>
					<td><?php echo site_url(); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Home url');?></th>
					<td><?php echo home_url(); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Multisite');?></th>
					<td><?php echo is_multisite() ? 'Yes' : 'No' ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Event Organiser version');?></th>
					<td><?php global $eventorganiser_db_version; echo $eventorganiser_db_version; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('WordPress');?></th>
					<td>
					<?php $eo_debugger->verbose_prequiste_check( 'WordPress', get_bloginfo( 'version' ) );?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('PHP Version');?></th>
					<td> <?php echo PHP_VERSION; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('MySQL Version');?></th>
					<td> <?php echo mysql_get_server_info(); ?></td>
				</tr>    
				<tr>
					<th><?php esc_html_e('Web Server');?></th>
					<td> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
				</tr>      
				<tr>
					<th><?php esc_html_e('PHP Memory Usage');?></th>
					<th><?php echo $eo_debugger->verbose_memory_check(); ?>
				</tr>   
				<tr>
					<th><?php esc_html_e('PHP Post Max Size');?></th>
					<td><?php echo ini_get('post_max_size'); ?></td>
				</tr>   
				<tr>
					<th><?php esc_html_e('PHP Upload Max Size');?></th>
					<td><?php echo ini_get('upload_max_filesize'); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('PHP cURL Support');?></th>
					<td>  <?php echo (function_exists('curl_init')) ? _e('Yes', 'eventorganiser') . "\n" : _e('No', 'eventorganiser') . "\n"; ?></td>
				</tr>        
				<tr>
					<th><?php esc_html_e('Plug-ins');?></th>
					<td>
					<?php $eo_debugger->verbose_plugin_check();?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('Theme');?></th>
					<td>
					<?php $eo_debugger->verbose_theme_check();?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('Databse Prefix:');?></th>
					<td><?php global $wpdb; echo $wpdb->prefix; ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e('Database tables');?></th>
					<td>
					<?php $eo_debugger->verbose_database_check();?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('Database character set');?></th>
					<td>
					<?php $eo_debugger->verbose_database_charset_check();?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e('Debug mode');?></th>
					<td><?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></td>
				</tr>
				
				<tr>
					<th><?php printf( esc_html__( '%s present', 'eventorganiser' ), '<code>wp_footer()</code>' );?></th>
					<td><?php $eo_debugger->verbose_footer_check();?></td>
				</tr>	
				<tr>
					<th><?php esc_html_e('Widget Sidebars');?></th>
					<td><?php $eo_debugger->verbose_sidebar_check();?></td>
				</tr>	
				<tr>
					<th><?php esc_html_e('Timezone');?></th>
					<td><?php echo eo_get_blog_timezone()->getName(); printf( ' ( %s / %s ) ', get_option( 'gmt_offset' ), get_option( 'timezone_string' ) )?></td>
				</tr>
		</table>
		<?php 
			printf( 
				'<p class="description"> <span class="eo-debug-warning">*</span> %s </p>',
				__( 'Known plug-in & theme conflicts, highlighted in red, may be minor or have a simple resolution. Please contact support.' )
			);
		?>
	</div><!--End .wrap -->
    <?php
	}

}
$venues_page = new EventOrganiser_Debug_Page();

class EventOrganiser_Debugger{

	var $prequiste = array();
	var $plugins = array();
	var $themes = array();
	var $db_tables = array();
	var $plugin = 'Event Organiser';

	var $ok_class = 'eo-debug-ok';
	var $warning_class = 'eo-debug-warning';
	var $alert_class = 'eo-debug-alert';
	
	function set_plugin( $plugin ){
		$this->plugin = $plugin;
	}
	
	function set_prequiste( $requirement, $min = false, $max = false ){
		$this->prequiste[$requirement] = compact( 'min', 'max' );
	}

	function set_known_plugin_conflicts( ){
		$args = func_get_args();
		if( $args ){
			$args = array_map( 'strtolower', $args );
			$this->plugins = array_merge( $this->plugins, $args );
		}
	}

	function set_known_theme_conflicts( ){
		$args = func_get_args();
		if( $args ){
			$args = array_map( 'strtolower', $args );
			$this->themes = array_merge( $this->themes,  $args );
		}
	}

	function set_db_tables( ){
		$args = func_get_args();
		$this->db_tables = array_merge( $this->db_tables, $args );
	}

	function check_prequiste( $requirement, $v ){
		if( !isset( $this->prequiste[$requirement] ) )
			return 1;

		$versions = $this->prequiste[$requirement];

		if( $versions['min'] && version_compare( $versions['min'], $v ) > 0 )
			return -1;

		if( $versions['max'] && version_compare( $versions['max'], $v ) < 0 )
			return 0;

		return 1;
	}

	function verbose_plugin_check(){
		$installed = get_plugins();

		foreach( $installed as $plugin_slug => $plugin_data ){
			if( ! is_plugin_active( $plugin_slug ) )
				continue;
				
			$class = in_array( strtolower( $plugin_slug ), $this->plugins ) ? $this->warning_class : '';
				
			printf(
			' <span class="%s"> %s %s </span> </br>',
			esc_attr( $class ),
			$plugin_data['Name'],
			$plugin_data['Version']
			);
		}
	}

	function verbose_theme_check(){
		if( version_compare( '3.4', get_bloginfo( 'version' ) ) <= 0 ){
			$theme = wp_get_theme();
			$class = in_array( strtolower( $theme->stylesheet ), $this->themes ) ? $this->warning_class : '';
			printf(
			' <span class="%s"> %s %s </span> </br>  %s',
				esc_attr( $class ),
				$theme->get('Name'),
				$theme->get('Version'),
				$theme->get('ThemeURI')
			);
		}else{
			$theme_name = get_current_theme();
			$class = in_array( strtolower( $theme_name ), $this->themes ) ? $this->warning_class : '';
			printf(
				' <span class="%s"> %s </span> </br>',
				esc_attr( $class ),
				$theme_name
			);
		}
	}

	function verbose_database_check(){
		if( $this->db_tables ){
			foreach( $this->db_tables as $db_table ){
				$class = $this->table_exists( $db_table ) ? $this->ok_class : $this->warning_class;
				printf( '<span class="%s"> %s </span></br>', esc_attr( $class ), esc_attr( $db_table ) );
			}
		}
	}

	function table_exists( $table ){
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix.$table ) ) == $wpdb->prefix.$table;
	}

	function database_charset_check(){
		global $wpdb;
		return $wpdb->query( $wpdb->prepare( 'SHOW CHARACTER SET WHERE LOWER( Charset ) = LOWER( %s )', DB_CHARSET ) );
	}
	
	function verbose_database_charset_check(){
		global $wpdb;

		if( $this->database_charset_check()  )
			$class = '';
		else
			$class = $this->warning_class;

		printf( '<span class="%s"> %s </span></br>', esc_attr( $class ), esc_attr( DB_CHARSET ) );
	}

	function verbose_prequiste_check( $requirement, $v ){

		$versions = $this->prequiste[$requirement];

		if( 1 == $this->check_prequiste( $requirement, $v ) ){
			printf( '<span class="%s">%s</span>', esc_attr( $this->ok_class ), $v );
		}elseif( 0 == $this->check_prequiste( $requirement, $v ) ){
			printf(
			'<span class="%s">%s</span>. %s',
			esc_attr( $this->alert_class ),
			$v,
			sprintf( __( '%s has only been tested up to %s %s' ), $this->plugin, $requirement, $versions['max'] )
			);
		}elseif( -1 == $this->check_prequiste( $requirement, $v ) ){
			printf(
			'<span class="%s">%s</span>. %s',
			esc_attr( $this->warning_class ),
			$v,
			sprintf( __( '%s requires %s version %s or higher' ), $this->plugin, $requirement, $versions['min'] )
			);
		}
	}
	
	function verbose_sidebar_check(){
		$footer_present = get_option( 'eo_sidebar_correct' );

		if( $footer_present === false ){
			printf(
				'<span class="%s">%s</span><br/> %s',
				esc_attr( $this->alert_class ),
				__( 'Unknown', 'eventorganiser' ),
				sprintf( __( 'Turn <a href="%s">WP_Debug mode</a> on and revisit the front-end to check' ), 'http://codex.wordpress.org/WP_DEBUG' )
		);
		}elseif( $footer_present == 1 ){
			printf(
				'<span class="%s">%s</span><br/> %s',
				esc_attr( $this->ok_class ),
				__( 'Correctly registered', 'eventorganiser' ),
				sprintf( __( 'Turn <a href="%s">WP_Debug mode</a> on and revisit the front-end to check' ), 'http://codex.wordpress.org/WP_DEBUG' )
			);
		}else{
			printf(
				'<span class="%s">%s</span><br/> %s',
				esc_attr( $this->warning_class ),
				__( 'Incorrectly registered', 'eventorganiser' ),
				sprintf(
					__( "The widget sidebars are incorrectly registered. See the <a href='%s'>FAQ</a> or contact support to resolve this." ),
					'http://wp-event-organiser.com/faq#i-cannot-navigate-between-months-on-the-widget-calendar'
				)
			);
		}
	}
	
	function verbose_memory_check(){

		if( function_exists( 'memory_get_usage' ) ){
			$memory_usage =  round( memory_get_usage() / 1024 / 1024, 2);
			$percentage = round( $memory_usage / ini_get( 'memory_limit' ) * 100, 0 );
			printf( '%d / %d   <span class="%s">( %s )</span>',
				ceil( $memory_usage ),
				ini_get( 'memory_limit' ),
				$percentage > 90 ? $this->alert_class : $this->ok_class,
				$percentage . "%"
			);
		}else{
			printf( ' ? / %d  <span class="%s">( %s )</span>',
				ini_get( 'memory_limit' ),
				$this->alert_class,
				__( 'unknown', 'eventorganiser' )
			);
		}

	}
	
	function verbose_footer_check(){
		$footer_present = get_option( 'eo_wp_footer_present' );
		
		if( $footer_present === false ){
			printf(
				'<span class="%s">%s</span><br/> %s',
				esc_attr( $this->alert_class ),
				__( 'Unknown', 'eventorganiser' ),
				sprintf( __( 'Turn <a href="%s">WP_Debug mode</a> on and revisit the front-end to check' ), 'http://codex.wordpress.org/WP_DEBUG' )
			);
		}elseif( $footer_present == 1 ){
			printf(
				'<span class="%s">%s</span><br/> %s',
				esc_attr( $this->ok_class ),
				__( 'Yes', 'eventorganiser' ),
				sprintf( __( 'Turn <a href="%s">WP_Debug mode</a> on and revisit the front-end to check' ), 'http://codex.wordpress.org/WP_DEBUG' )
			);
		}else{
			printf(
				'<span class="%s">%s</span><br/> %s',
				esc_attr( $this->warning_class ),
				__( 'No', 'eventorganiser' ),
				sprintf( 
					__( "The <a href='%s'>wp_footer hook</a> could be not be found. Without, for example, the calendar will not function" ), 
					'http://codex.wordpress.org/Function_Reference/wp_footer' 
				)
			);
		}
	}
	
	public function download_debug_info(){
	
		global $wpdb;
		$installed = get_plugins();
		$active_plugins = array();
		foreach( $installed as $plugin_slug => $plugin_data ){
			if( ! is_plugin_active( $plugin_slug ) )
				continue;
	
			$active_plugins[] = $plugin_slug;
		};
	
		$theme = wp_get_theme();
	
		$db_tables = array();
		if( $this->db_tables ){
			foreach( $this->db_tables as $db_table ){
	
				if( $this->table_exists( $db_table ) ){
					$db_table = "**".$db_table."**";
				}
				$db_tables[] = $db_table;
			}
		}
		
		$options = array();
		$options['event-organiser'] = eventorganiser_get_option( false );
		$options = apply_filters( 'eventorganiser_export_settings', $options );
	
		$filename = 'event-organiser-system-info-'.get_bloginfo( 'name' ).'.md';
		$filename = sanitize_file_name( $filename );

		header( "Content-type: text/plain" );
		header('Content-disposition: attachment; filename=' . $filename );
	
		echo '## Event Organiser Sytem Informaton ##' . "\n";
		echo "\n";
		echo "\n";
	
		echo '### Site Information ###' . "\n";
		echo "\n";
		echo esc_html__('Site url')."\t\t\t".site_url()."\n";
		echo esc_html__('Home url')."\t\t\t".home_url()."\n";
		echo esc_html__('Multisite')."\t\t\t".( is_multisite() ? 'Yes' : 'No' )."\n";
	
		echo "\n";
		echo "\n";
		echo '### Versions ###' . "\n";
		echo "\n";
		global $eventorganiser_db_version;
		echo esc_html__('Event Organiser')."\t\t" . $eventorganiser_db_version."\n";
		echo esc_html__('WordPress')."\t\t\t" . get_bloginfo( 'version' ) ."\n";
		echo esc_html__('PHP Version')."\t\t\t" . PHP_VERSION ."\n";
		echo esc_html__('MySQL Version')."\t\t" . mysql_get_server_info() ."\n";
	
		echo "\n";
		echo "\n";
		echo '### Server Information ###' . "\n";
		echo "\n";
		echo esc_html__('Web Server')."\t\t\t" . $_SERVER['SERVER_SOFTWARE'] ."\n";
		echo esc_html__('PHP Memory Limit')."\t" .ini_get( 'memory_limit' ) ."\n";
	
		echo "\n";
		echo "\n";
		echo '### Plug-ins & Themes ###' . "\n";
		echo "\n";
		echo esc_html__('Active Plug-ins')."\n\t-\t" . implode( "\n\t-\t", $active_plugins ) . "\n";
		echo esc_html__('Theme')."\n\t-\t" . $theme->get('Name').' ('.$theme->get('Version').')' . "\n";
	
		echo "\n";
		echo "\n";
		echo '### Database ###' . "\n";
		echo "\n";
		echo esc_html__('Databse Prefix')."\t\t\t" . $wpdb->prefix . "\n";
		echo esc_html__('Database tables')."\t\t\t" . implode( ', ', $db_tables ). "\n";
		echo esc_html__('Database character set')."\t" . ( $this->database_charset_check() ? DB_CHARSET : "**".DB_CHARSET."**" ) . "\n";
	
		echo "\n";
		echo "\n";
		echo '### Settings ###' . "\n";
		foreach( $options['event-organiser'] as $option => $value ){
			if( is_array( $value ) )
				$value = implode( ', ', $value );
			echo "\n\t-\t**".esc_html( $option ).":**\t " . $value; 
		}
		
		echo "\n";
		echo "\n";
		echo '### Debug Mode ###' . "\n";
		echo "\n";
		echo esc_html__('Debug mode')."\t\t\t" . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled' ) . "\n";
		echo esc_html__('Script mode')."\t\t\t" . ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'Enabled' : 'Disabled' ) . "\n";
		
		exit();
	}
}