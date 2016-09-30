<?php
/*
 * A walker class to use that extends wp_dropdown_categories and allows it to use the term's slug as a value
 * rather than ID.
 *
 * Usage, as normal:
 * wp_dropdown_categories( $args );
 *
 *
 * But specify the custom walker class, and (optionally) a 'term_id' or 'slug' for the 'value_field' parameter:
 * $args=array('walker'=> new EO_Walker_TaxonomyDropdown(), 'value_field'=>'slug', .... );
 * wp_dropdown_categories($args);
 *
 * If the 'value_field' parameter is not set it will use term ID for categories, and the term's slug for other
 * taxonomies in the value attribute of the term's <option>.
 *
 * @link https://core.trac.wordpress.org/ticket/13258
 * @link https://core.trac.wordpress.org/ticket/30306
 */
class EO_Walker_TaxonomyDropdown extends Walker_CategoryDropdown {

	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat( '&nbsp;', $depth * 3 );
		/**
		 * @ignore
		*/
		$cat_name = apply_filters( 'list_cats', $category->name, $category );

		//Backwards compatability for 'value'
		if ( isset( $args['value_field'] ) ) {
			$args['value'] = $args['value_field'];
		}

		if ( ! isset( $args['value'] ) ) {
			$args['value'] = ( 'category' != $category->taxonomy ? 'slug' : 'term_id' );
		} else {
			$args['value'] = ( 'id' == $args['value'] ? 'term_id' : $args['value'] );
		}

		$value = isset( $category->{$args['value']} ) ? $category->{$args['value']} : $category->term_id;

		$output .= sprintf(
			"\t<option class=\"level-%s\" value=\"%s\" %s>",
			$depth,
			esc_attr( $value ),
			selected( (string) $args['selected'], $value, false )
		);
		$output .= $pad . $cat_name;
		if ( $args['show_count'] ) {
			$output .= '&nbsp;&nbsp;(' . $category->count . ')';
		}
		$output .= "</option>\n";
	}
}
