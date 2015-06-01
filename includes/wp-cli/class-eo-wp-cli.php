<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
	exit;
}

class EO_Event_CLI_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'event';
	
	protected $obj_fields = array(
		'ID',
		'post_title',
		'post_name',
		'post_date',
		'post_status',
	);
	
	public function __construct() {
		$this->fetcher = new \WP_CLI\Fetchers\Post;
	}
	
	/**
	 * Create an event.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : Read event content from <file>. If this value is present, the
	 *     `--post_content` argument will be ignored.
	 *
	 *   Passing `-` as the filename will cause event content to
	 *   be read from STDIN.
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new event. See eo_insert_event().
	 *
	 * [--edit]
	 * : Immediately open system's editor to write or edit event content.
	 *
	 *   If content is read from a file, from STDIN, or from the `--post_content`
	 *   argument, that text will be loaded into the editor.
	 *
	 * [--porcelain]
	 * : Output just the new event id.
	 *
	 * ## EXAMPLES
	 *
	 *     wp eo event create --post_title='An all day event tomorrow' --start='tomorrow' --end='tomorrow' --all_day=1
	 *
	 *     wp eo event create ./post-content.txt --category=201,345 --post_title='Event from file'
	 */
	public function create( $args, $assoc_args ) {

		try{
			if( empty( $assoc_args['start'] ) ){
				throw new Exception( 'Start date/time not provided' );
			}

			$assoc_args['start'] = new DateTime( $assoc_args['start'], eo_get_blog_timezone() );
			
			if( isset( $assoc_args['end'] ) ){
				$assoc_args['end'] = new DateTime( $assoc_args['end'], eo_get_blog_timezone() );
			}

			if( isset( $assoc_args['until'] ) ){
				$assoc_args['until'] = new DateTime( $assoc_args['until'], eo_get_blog_timezone() );
			}			

			//TODO include/exclude
						
			if ( isset( $assoc_args['category'] ) ) {
				$assoc_args['category'] = explode( ',', $assoc_args['category'] );
			}
		}catch( \Exception $e ){
			WP_CLI::error( $e->getMessage() );
		}

		parent::_create( $args, $assoc_args, function ( $params ) {
			return eo_insert_event( $params, true );
		} );
	}
	
	
	/**
	 * Update one or more events.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of events to update.
	 *
	 * [<file>]
	 * : Read event content from <file>. If this value is present, the
	 *     `--post_content` argument will be ignored.
	 *
	 *   Passing `-` as the filename will cause event content to
	 *   be read from STDIN.
	 *
	 * --<field>=<value>
	 * : One or more fields to update. See eo_update_event().
	 *
	 * ## EXAMPLES
	 *
	 *     wp wp eo event update 123 --start='+5 hours' --end='+7 hours'
	 */
	public function update( $args, $assoc_args ) {
		parent::_update( $args, $assoc_args, function ( $params ) {
			$response = eo_update_event( $params['ID'], $params );
			if( !is_wp_error( $response ) &&  isset( $params['venue'] ) ){
				wp_set_object_terms( $params['ID'], array( $params['venue'] ), 'event-venue' );
			}
			return $response;
		} );
	}
	
	/**
	 * Launch system editor to edit event content.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the event to edit.
	 *
	 * ## EXAMPLES
	 *
	 *     wp eo event edit 123
	 */
	public function edit( $args, $_ ) {
		$post = $this->fetcher->get_check( $args[0] );
		$r = $this->_edit( $post->post_content, "WP-CLI post {$post->ID}" );
		if ( false === $r ){
			\WP_CLI::warning( 'No change made to post content.', 'Aborted' );
		}else{
			$this->update( $args, array( 'post_content' => $r ) );
		}
	}
	
	protected function _edit( $content, $title ) {
		$content = apply_filters( 'the_editor_content', $content );
		$output = \WP_CLI\Utils\launch_editor_for_input( $content, $title );
		return ( is_string( $output ) ) ?
		apply_filters( 'content_save_pre', $output ) : $output;
	}

}

WP_CLI::add_command( 'eo event', 'EO_Event_CLI_Command' );