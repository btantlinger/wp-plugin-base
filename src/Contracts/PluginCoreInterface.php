<?php

namespace WebMoves\PluginBase\Contracts;

use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\Components\ComponentInterface;
use WebMoves\PluginBase\Contracts\Configuration\ConfigurationManagerInterface;

interface PluginCoreInterface
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
	public function set(string $id, $value, bool $auto_register_components=true): void;

	/**
	 * Get a service from the container
	 *
	 * @param string $id Service identifier
	 * @return mixed
	 */
	public function get(string $id);

	public function get_logger(?string $channel=null): LoggerInterface;

	public function get_config(): ConfigurationManagerInterface;


	public function is_registered(ComponentInterface $component): bool;


	/**
	 * Register a component
	 *
	 * @param ComponentInterface $component The component
	 *
	 * @return void
	 */
	public function register_component(ComponentInterface $component): void;


	/**
	 * Get the container instance
	 *
	 * @return Container
	 */
	public function get_container(): ContainerInterface;
	


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


	public function get_database_version(): ?string;

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get plugin file path
	 *
	 * @return string
	 */
	public function get_plugin_file(): string;

	public function get_text_domain(): string;
}