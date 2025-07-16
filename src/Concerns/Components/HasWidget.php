<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait HasWidget
{
	use TraitRegistrationHelper;

	protected function register_has_widget(): void
	{
		$this->ensure_component_registration();
		add_action('widgets_init', [$this, 'register_widget']);
	}

	public function register_widget(): void
	{
		register_widget($this->get_widget_class());
	}

	abstract protected function get_widget_class(): string;
}