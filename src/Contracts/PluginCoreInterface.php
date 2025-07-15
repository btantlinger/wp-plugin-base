<?php

namespace WebMoves\PluginBase\Contracts;

use DI\Container;
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
	public function register_service(string $id, $value): void;

	/**
	 * Get a service from the container
	 *
	 * @param string $id Service identifier
	 * @return mixed
	 */
	public function get_service(string $id);

	/**
	 * Register an event handler
	 *
	 * @param \WebMoves\PluginBase\Contracts\Components\ComponentInterface $handler
	 *
	 * @return void
	 */
	public function register_component(ComponentInterface $handler): void;


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