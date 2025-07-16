<?php

namespace WebMoves\PluginBase\Concerns;

use Psr\Container\ContainerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

trait PluginCoreHelper {

	/**
	 * Get the plugin core instance
	 *
	 * @return PluginCoreInterface
	 */
	protected function get_plugin_core(): ?PluginCoreInterface
	{
		// This assumes the component has access to the core
		// Either through dependency injection or a property
		$possible_names = ['core', 'plugin_core', 'plugin_core_instance', 'pluginCore', 'pluginCoreInstance'];
		foreach ($possible_names as $name) {
			if (property_exists($this, $name) && $this->{$name} instanceof PluginCoreInterface) {
				return $this->{$name};
			}
		}

		// Or get it from the container if available
		if (method_exists($this, 'get_container') && $this->get_container() instanceof ContainerInterface) {
			if($this->get_container()->has(PluginCoreInterface::class)) {
				return $this->get_container()->get( PluginCoreInterface::class );
			}
		}

		return null;
	}
}