<?php

namespace WebMoves\PluginBase\Contracts\Plugin;

use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Components\ComponentManager;
use WebMoves\PluginBase\Contracts\Configuration\Configuration;


interface PluginCore
{

	/**
	 * Handle plugin uninstallation
	 *
	 * Called when plugin is being uninstalled/deleted from WordPress.
	 * Should clean up any plugin data, settings, database tables etc.
	 *
	 * @return void
	 */
	public static function handle_uninstall(): void;


	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	public function initialize(): void;

	/**
	 * Register a service in the container
	 *
	 * @param string $id Service identifier
	 * @param mixed $value Service instance or factory
	 * @return void
	 */
	public function set(string $id, $value, bool $auto_add_components=true): void;

	/**
	 * Get a service from the container
	 *
	 * @param string $id Service identifier
	 * @return mixed
	 */
	public function get(string $id);


	/**
	 * Get logger instance
	 *
	 * @param string|null $channel Optional logging channel
	 *
	 * @return LoggerInterface
	 */
	public function get_logger( ?string $channel = null ): LoggerInterface;

	/**
	 * Get configuration instance
	 *
	 * @return Configuration
	 */
	public function get_config(): Configuration;


	/**
	 * Get the container instance
	 *
	 * @return Container
	 */
	public function get_container(): ContainerInterface;


	/**
	 * Get component manager instance
	 *
	 * @return ComponentManager
	 */
	public function get_component_manager(): ComponentManager;


	/**
	 * Get WordPress database object
	 *
	 * @return \wpdb
	 */
	public function get_db(): \wpdb;


	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function get_version(): string;


	//public function get_database_version(): ?string;

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function get_plugin_name(): string;


	/**
	 * Get plugin file path
	 *
	 * @return string
	 */
	public function get_plugin_file(): string;

	/**
	 * Get plugin text domain for translations
	 *
	 * @return string
	 */
	public function get_text_domain(): string;

	/**
	 * Get plugin metadata
	 *
	 * @return PluginMetadata
	 */
	public function get_metadata(): PluginMetadata;
}