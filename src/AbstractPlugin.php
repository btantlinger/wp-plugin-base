<?php

namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

abstract class AbstractPlugin {

	private static ?AbstractPlugin $instance = null;

	protected PluginCoreInterface $core;

	public static function init_plugin( string $plugin_file): static {
		if(!is_null(static::$instance)) {
			throw new \LogicException('Plugin already initialized');
		}
		$core = static::create_core($plugin_file);
		static::$instance = new static($core);
		return static::$instance;
	}

	public static function get_instance(): static
	{
		if(is_null(static::$instance)) {
			throw new \LogicException('Plugin not initialized');
		}
		return static::$instance;
	}

	protected static function create_core(string $plugin_file): PluginCoreInterface
	{
		return new PluginCore($plugin_file);
	}

	private function __construct(PluginCoreInterface $core)
	{
		$this->core = $core;

		// Initialize database manager if the plugin needs it
		//$this->init_database();

		$this->core->initialize();
		$services = $this->get_services();
		foreach($services as $id => $service) {
			$this->core->set($id, $service);
		}
		$this->initialize();
	}


	public function initialize(): void
	{
		// Override in child classes
	}

	public function get_services(): array
	{
		return [];
	}

	public function get_core(): PluginCoreInterface
	{
		return $this->core;
	}
}