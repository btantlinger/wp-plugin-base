<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait HasRestApi
{
	use TraitRegistrationHelper;
	protected function register_has_rest_api(): void
	{
		$this->ensure_component_registration();
		add_action('rest_api_init', [$this, 'register_rest_routes']);
	}

	public function register_rest_routes(): void
	{
		$routes = $this->get_rest_routes();

		foreach ($routes as $route => $config) {
			register_rest_route(
				$this->get_rest_namespace(),
				$route,
				$config
			);
		}
	}

	abstract protected function get_rest_routes(): array;
	abstract protected function get_rest_namespace(): string;
}