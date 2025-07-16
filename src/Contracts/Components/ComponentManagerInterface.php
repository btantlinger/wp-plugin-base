<?php

namespace WebMoves\PluginBase\Contracts\Components;


interface ComponentManagerInterface {
	/**
	 * Register a handler
	 *
	 * @param ComponentInterface $component
	 *
	 * @return void
	 */
	public function register_component(string $id, ComponentInterface $component): void;

	/**
	 * Initialize all registered handlers
	 *
	 * @return void
	 */
	public function initialize_components(): void;

	/**
	 * Get all registered components
	 *
	 * @return array<string,ComponentInterface> Associative array of components with component ID as key
	 */
	public function get_components(): array;


	/**
	 * Get a component
	 *
	 * @param string $id
	 *
	 * @return ComponentInterface|null
	 */
	public function get_component(string $id): ?ComponentInterface;

	/**
	 * Check if a component is registered
	 *
	 * @param string $id
	 * @return bool
	 */
	public function has_component(string $id): bool;

	/**
	 * Remove a component by class name
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public function remove_component(string $id): void;

}