<?php
class EO_Shortcode_EventList implements EO_Shortcode {

	private $atts;

	private $content;

	private $temlate_file = null;

	public function set_attributes( $atts ) {
		$this->atts = $atts;


					/**
					 * @ignore
					 * Try to find template - backwards compat. Don't use this filter. Will be removed!
					 */
					$template_file = apply_filters( 'eventorganiser_event_list_loop', false );
					$template_file = locate_template( $template_file );
					if ( $template_file || empty( $template ) ) {
						ob_start();
						if ( empty( $template_file ) ) {
							$template_file = eo_locate_template( array( $eo_event_loop_args['type'] . '-event-list.php', 'event-list.php' ), true, false );
						} else {
							require( $template_file );
						}

						$html = ob_get_contents();
						ob_end_clean();

					} else {
						//Using the 'placeholder' template
						$no_events = isset( $args['no_events'] ) ? $args['no_events'] : '';

						$id        = ( ! empty( $args['id'] ) ? 'id="' . esc_attr( $args['id'] ) . '"' : '' );
						$container = '<ul ' . $id . ' class="%2$s">%1$s</ul>';

						$html = '';
						if ( $eo_event_loop->have_posts() ) {
							while ( $eo_event_loop->have_posts() ) {
								$eo_event_loop->the_post();
								$event_classes = eo_get_event_classes();
s.								$html .= sprintf(
									'<li class="%2$s">%1$s</li>',
									EventOrganiser_Shortcodes::read_template( $template ),
									esc_attr( implode( ' ', $event_classes ) )
								);
							}
						} elseif ( $no_events ) {
							$html .= sprintf( '<li class="%2$s">%1$s</li>', $no_events, 'eo-no-events' );
						}

						$html = sprintf( $container, $html, esc_attr( $args['class'] ) );
					}
	}

	public function set_content( $content = null ) {
		$this->content = $content;
	}

	public function render( $assets_lazy_loader ) {

		$args = array(
			'class'     => 'eo-events eo-events-shortcode',
			'template'  => $this->content,
			'no_events' => isset( $this->atts['no_events'] ) ? $this->atts['no_events'] : '',
			'type'      => 'shortcode',
		);


			$args = array_merge(array(
				'id'        => '',
				'class'     => 'eo-event-list',
				'type'      => 'shortcode',
				'no_events' => '',
			),$args);

			/* Pass these defaults - backwards compat with using eo_get_events()*/
			$query = wp_parse_args($query, array(
				'posts_per_page'   => -1,
				'post_type'        => 'event',
				'suppress_filters' => false,
				'orderby'          => 'eventstart',
				'order'            => 'ASC',
				'showrepeats'      => 1,
				'group_events_by'  => '',
				'showpastevents'   => true,
				'no_found_rows'    => true,
			));

			//Make sure false and 'False' etc actually get parsed as 0/false (input from shortcodes, for instance, can be varied).
			//This maybe moved to the shortcode handler if this function is made public.
			if ( 'false' === strtolower( $query['showpastevents'] ) ) {
				$query['showpastevents'] = 0;
			}

			if ( ! empty( $query['numberposts'] ) ) {
				$query['posts_per_page'] = (int) $query['numberposts'];
			}

			$template = isset( $args['template'] ) ? $args['template'] :'';

			global $eo_event_loop,$eo_event_loop_args;
			$eo_event_loop_args = $args;
			$eo_event_loop = new WP_Query( $query );


			wp_reset_postdata();

			if ( $echo ) {
				echo $html;
			}

			return $html;

		//return eventorganiser_list_events( $this->atts, $args, 0 );
	}

	function set_template() {

	}
}
