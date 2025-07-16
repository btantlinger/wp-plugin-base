<?php

namespace WebMoves\PluginBase\Contracts;

use DI\Container;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\Components\ComponentInterface;

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


	/**
	 * Register a component with a unique identifier and its corresponding handler.
	 *
	 * @param string $id The unique identifier for the component.
	 * @param ComponentInterface $handler The handler associated with the component.
	 *
	 * @return void
	 */
	public function register_component(string $id, ComponentInterface $handler): void;


	/**
	 * Get the container instance
	 *
	 * @return Container
	 */
	public function get_container(): Container;

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function get_version(): string;

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
}