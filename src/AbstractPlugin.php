<?php

namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

abstract class AbstractPlugin {

	private static ?AbstractPlugin $instance = null;

	protected PluginCoreInterface $core;
	protected ?DatabaseManagerInterface $database_manager = null;


	public static function init_plugin(
		string $plugin_file, 
		string $plugin_version, 
		?string $text_domain = null,
		?string $database_version = null
	): static {
		if(!is_null(static::$instance)) {
			throw new \LogicException('Plugin already initialized');
		}

		$core = static::create_core($plugin_file, $plugin_version, $text_domain, $database_version);
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

	protected static function create_core(string $plugin_file, string $plugin_version, ?string $text_domain = null, ?string $database_version = null): PluginCoreInterface
	{
		return new PluginCore($plugin_file, $plugin_version, $text_domain, $database_version);
	}

	private function __construct(PluginCoreInterface $core)
	{
		$this->core = $core;

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
	 * Initialize database management
	 * Note: maybe_upgrade() is called later in PluginCore::on_plugins_loaded()
	 */
	private function init_database(): void
	{
		if ($this->should_init_database()) {
			$this->database_manager = $this->core->get(DatabaseManagerInterface::class);

			// Register tables and callbacks - upgrades happen later in plugins_loaded
			foreach($this->get_database_tables() as $table_name => $table_definition) {
				$this->database_manager->register_table($table_name, $table_definition);
			}
			foreach($this->get_database_upgrade_callbacks() as $callback) {
				$this->database_manager->register_upgrade_callback($callback);
			}
		}
	}

	/**
	 * Check if database management should be initialized
	 * Returns true if a database version was provided during init_plugin()
	 */
	protected function should_init_database(): bool
	{
		return $this->core->get_database_version() !== null;
	}

	/**
	 * Get database tables definitions
	 *
	 * Should return an associative array where:
	 * - key: table name/identifier
	 * - value: SQL CREATE TABLE statement
	 *
	 * @return array<string,string> Array of table definitions
	 */
	protected function get_database_tables(): array {
		// Override in child classes
		return [];
	}

	/**
	 * Get database version upgrade callbacks
	 *
	 * Returns an array of callables that will be executed in order to perform
	 * database version upgrades. Each callable should handle upgrading the database
	 * schema or data from one version to another.
	 *
	 * Each callback function should accept two parameters:
	 *
	 * @param string $old_version The previous database version
	 * @param string $current_version The current database version being upgraded to
	 *
	 * @return array<callable> Array of upgrade callback functions
	 */
	protected function get_database_upgrade_callbacks(): array {
		return [];
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