<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait CanHaveAjax
{
	use TraitRegistrationHelper;

	protected function register_can_have_ajax(): void
	{
		$this->ensure_component_registration();

		$actions = $this->get_ajax_actions();

		foreach ($actions as $action => $config) {
			if ($config['public'] ?? false) {
				add_action("wp_ajax_nopriv_{$action}", [$this, $config['callback']]);
			}

			if ($config['logged_in'] ?? true) {
				add_action("wp_ajax_{$action}", [$this, $config['callback']]);
			}
		}
	}

	abstract protected function get_ajax_actions(): array;
}