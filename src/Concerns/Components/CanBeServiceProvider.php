<?php

namespace WebMoves\PluginBase\Concerns\Components;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;

trait CanBeServiceProvider
{
	use TraitRegistrationHelper;


	protected function register_can_be_service_provider(): void
	{
		$this->ensure_component_registration();

		$services = $this->get_provided_services();

		if (empty($services)) {
			return;
		}

		$core = $this->get_plugin_core();

		foreach ($services as $id => $definition) {
			$core->register_service($id, $definition);
		}
	}

	/**
	 * Get the plugin core instance
	 *
	 * @return PluginCoreInterface
	 */
	protected function get_plugin_core(): PluginCoreInterface
	{
		// This assumes the component has access to the core
		// Either through dependency injection or a property
		if (property_exists($this, 'core')) {
			return $this->core;
		}

		// Or get it from the container if available
		if (method_exists($this, 'get_container')) {
			return $this->get_container()->get(PluginCoreInterface::class);
		}

		throw new \RuntimeException('Plugin core not available in service provider component');
	}

	/**
	 * Get services to register
	 *
	 * @return array<string, mixed> Service definitions
	 */
	abstract protected function get_provided_services(): array;
}