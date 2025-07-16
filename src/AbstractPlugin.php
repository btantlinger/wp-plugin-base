<?php

namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

abstract class AbstractPlugin {

	private static ?AbstractPlugin $instance = null;

	protected PluginCoreInterface $core;
	protected ?DatabaseManagerInterface $database_manager = null;
	protected ?string $database_version = null;

	public static function init_plugin(
		string $plugin_file, 
		string $plugin_version, 
		?string $text_domain = null,
		?string $database_version = null
	): static {
		if(!is_null(static::$instance)) {
			throw new \LogicException('Plugin already initialized');
		}

		$core = static::create_core($plugin_file, $plugin_version, $text_domain);
		static::$instance = new static($core, $database_version);
		return static::$instance;
	}

	public static function get_instance(): static
	{
		if(is_null(static::$instance)) {
			throw new \LogicException('Plugin not initialized');
		}
		return static::$instance;
	}

	protected static function create_core(string $plugin_file, string $plugin_version, ?string $text_domain = null): PluginCoreInterface
	{
		return new PluginCore($plugin_file, $plugin_version, $text_domain);
	}

	private function __construct(PluginCoreInterface $core, ?string $database_version = null)
	{
		$this->core = $core;
		$this->database_version = $database_version;

		// Set custom database version if provided
		$this->set_database_version();

		// Initialize database manager if the plugin needs it
		$this->init_database();

		$this->core->initialize();
		$services = $this->get_services();
		foreach($services as $id => $service) {
			$this->core->set($id, $service);
		}
		$this->initialize();
	}

	/**
	 * Set database version in the DI container if plugin defines one
	 */
	private function set_database_version(): void
	{
		if ($this->database_version !== null) {
			$this->core->set('plugin.database_version', $this->database_version);
		}
	}

	/**
	 * Initialize database management
	 * Note: maybe_upgrade() is called later in PluginCore::on_plugins_loaded()
	 */
	private function init_database(): void
	{
		if ($this->should_init_database()) {
			$this->database_manager = $this->core->get(DatabaseManagerInterface::class);

			// Register tables and callbacks - upgrades happen later in plugins_loaded
			$this->register_tables();
			$this->register_version_callbacks();
		}
	}

	/**
	 * Check if database management should be initialized
	 * Returns true if a database version was provided during init_plugin()
	 */
	protected function should_init_database(): bool
	{
		return $this->database_version !== null;
	}

	/**
	 * Register your database tables here
	 */
	protected function register_tables(): void
	{
		// Override in child classes
	}

	/**
	 * Register version-specific upgrade callbacks
	 */
	protected function register_version_callbacks(): void
	{
		// Override in child classes
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

	public function get_database_manager(): ?DatabaseManagerInterface
	{
		return $this->database_manager;
	}

	/**
	 * Get the database version for this plugin
	 */
	public function get_database_version(): ?string
	{
		return $this->database_version;
	}
}