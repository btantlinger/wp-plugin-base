<?php

namespace WebMoves\PluginBase\Concerns;

use Psr\Container\ContainerInterface;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;

trait PluginCoreHelper {

	/**
	 * Get the plugin core instance
	 *
	 * @return PluginCore
	 */
	protected function get_plugin_core(): ?PluginCore
	{
		// This assumes the component has access to the core
		// Either through dependency injection or a property
		$possible_names = ['core', 'plugin_core', 'plugin_core_instance', 'pluginCore', 'pluginCoreInstance'];
		foreach ($possible_names as $name) {
			if (property_exists($this, $name) && $this->{$name} instanceof PluginCore) {
				return $this->{$name};
			}
		}

		// Or get it from the container if available
		if (method_exists($this, 'get_container') && $this->get_container() instanceof ContainerInterface) {
			if($this->get_container()->has(PluginCore::class)) {
				return $this->get_container()->get( PluginCore::class );
			}
		}

		return null;
	}
}