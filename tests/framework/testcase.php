<?php

require dirname( __FILE__ ) . '/factory.php';

class EO_UnitTestCase extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		//Change WP factory to our child factory
		$this->factory = new EO_UnitTest_Factory;
	}

	public function clean_up_global_scope() {
		parent::clean_up_global_scope();
	}

	public function assertPreConditions() {
		parent::assertPreConditions();
	}

	public function go_to( $url ) {
		$GLOBALS['_SERVER']['REQUEST_URI'] = $url = str_replace( network_home_url(), '', $url );

		$_GET = $_POST = array();

		foreach ( array( 'query_string', 'id', 'postdata', 'authordata', 'day', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages', 'pagenow' ) as $v ) {
			if ( isset( $GLOBALS[ $v ] ) ) unset( $GLOBALS[ $v ] );
		}

		$parts = parse_url($url);

		if ( isset( $parts['scheme'] ) ) {
			$req = $parts['path'];
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				parse_str( $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}

		if ( ! isset( $parts['query'] ) ) {
			$parts['query'] = '';
		}

		// Scheme
		if ( 0 === strpos( $req, '/wp-admin' ) && force_ssl_admin() ) {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset( $_SERVER['HTTPS'] );
		}

		$_SERVER['REQUEST_URI'] = $req;
		unset($_SERVER['PATH_INFO']);

		$this->flush_cache();

		unset($GLOBALS['wp_query'], $GLOBALS['wp_the_query']);

		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query'] =& $GLOBALS['wp_the_query'];
		$GLOBALS['wp'] = new WP();

		foreach ( $GLOBALS['wp']->public_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}
		foreach ( $GLOBALS['wp']->private_query_vars as $v ) {
			unset( $GLOBALS[ $v ] );
		}

		$GLOBALS['wp']->main( $parts['query'] );
	}

	public function set_current_user( $user_id ) {
		wp_set_current_user( $user_id );
	}
	
	/**
	 * Backwards compatability for 4.3 and earlier
	 * Method introduced for Attachment factory in 33641
	 * @see https://core.trac.wordpress.org/ticket/33641
	 * @return unknown
	 */
	function create_upload_object( $file, $parent = 0 ) {
		$contents = file_get_contents($file);
		$upload = wp_upload_bits(basename($file), null, $contents);
	
		$type = '';
		if ( ! empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
				$type = $mime['type'];
		}
	
		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);
	
		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	
		return $id;
	}

	protected function checkRequirements() {
		parent::checkRequirements();

		$annotations = $this->getAnnotations();
		//$this->getAnnotations returns an array indexed by 'class' and 'method'
		//with annotations for the class and method respectively. We don't care about the location of the annotation
		//so we just merge them:
		$annotations = array_merge( $annotations['class'], $annotations['method'] );

		if ( empty($annotations['requires']) ) {
			return;
		}

		foreach ( $annotations['requires'] as $required ) {
			if ( $target = $this->requiresWordPressVersion( $required ) ) {
				if ( ! version_compare( get_bloginfo( 'version' ), $target['version'], $target['operator'] ) ) {
					$message = sprintf( 'Requires WordPress %s %s; Running %s.', $target['operator'], $target['version'], get_bloginfo( 'version' ) );
					if ( $target['message'] ) {
						$message .= "\n" . $target['message'];
					}
					$this->markTestSkipped( $message );
				}
			}
		}
	}



	protected function requiresWordPressVersion( $string ) {
		preg_match( '/WordPress (?P<operator><|lt|<=|le|>|gt|>=|ge|==|=|eq|!=|<>|ne)?\s*(?P<version>\d+\.\d+(\.\d+)?(-(stable|beta|b|RC|alpha|a|patch|pl|p))?)\s*(?P<message>.*)?/', $string, $matches );

		if ( ! $matches ) {
			return;
		}

		$operator = ! empty( $matches['operator'] ) ? $matches['operator'] : '>=';
		return array( 'version' => $matches['version'], 'operator' => $operator, 'message' => $matches['message'] );
	}

}
