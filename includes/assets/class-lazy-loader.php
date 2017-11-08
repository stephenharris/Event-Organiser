<?php

class EO_Assets_LazyLoader {

	private $scripts = array();

	private $listeners = array();

	public function enqueue_script( $script ) {
		if ( !isset( $this->scripts[$script] ) ) {
			$this->scripts[$script] = array();
		}
	}

	public function enqueue_style( $style ) {
		$this->styles[] = $style;
	}

	public function register_lazy_data_listener( $script, $listener ) {
		$this->enqueue_script( $script );
		$this->scripts[$script][] = $listener;
	}

	public function load_scripts() {
		foreach( $this->scripts as $script => $listeners ) {
			$this->attach_data( $script, $listeners );
			wp_enqueue_script( $script );
		}
	}

	public function load_styles() {
		foreach( $this->styles as $style ) {
			eo_enqueue_style( $style );
		}
	}

	private function attach_data( $script, $listeners ) {

		if ( ! $listeners ) {
			return;
		}

		$data = [];
		foreach( $listeners as $listener ) {
			$data = array_merge( $data, $listener->load_data() );
		}

		eo_localize_script( $script, $data );
	}

}
