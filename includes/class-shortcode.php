<?php

interface EO_Shortcode {

	public function set_attributes( $attributes );
	public function set_content( $content = null );
	public function render( $assets_lazy_loader );

}
