<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait CanHaveShortcode {

	use TraitRegistrationHelper;

	protected function register_can_have_shortcode(): void {

		$this->ensure_component_registration();

		add_shortcode(
			$this->get_shortcode_tag(),
			[$this, 'render_shortcode']
		);
	}

	abstract protected function render_shortcode($atts, $content = null): string;
	abstract protected function get_shortcode_tag(): string;

}