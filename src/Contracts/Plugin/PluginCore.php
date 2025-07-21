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

	public function get_logger(?string $channel=null): LoggerInterface;

	public function get_config(): Configuration;


	/**
	 * Get the container instance
	 *
	 * @return Container
	 */
	public function get_container(): ContainerInterface;


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


	public function get_hook_prefix(): string;
	/**
	 * Get plugin file path
	 *
	 * @return string
	 */
	public function get_plugin_file(): string;

	public function get_text_domain(): string;

	public function get_metadata(): PluginMetadata;
}