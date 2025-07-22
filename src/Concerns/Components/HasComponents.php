<?php

namespace WebMoves\PluginBase\Concerns\Components;

use WebMoves\PluginBase\Concerns\PluginCoreHelper;
use WebMoves\PluginBase\Contracts\Components\Component;

trait HasComponents
{
	use TraitRegistrationHelper;
	use PluginCoreHelper;

	protected function register_has_components(): void
	{
		$this->ensure_component_registration();

		$services = $this->get_components();

		if (empty($services)) {
			return;
		}

		$core = $this->get_plugin_core();
		if (!$core) {
			throw new \RuntimeException('Plugin core not available in service provider component');
		}

		foreach ($services as $id => $definition) {
			$core->set($id, $definition, true);
		}
	}

	/**
	 * Get services to register
	 *
	 * @return array<string, Component> Service definitions
	 */
	abstract protected function get_components(): array;
}