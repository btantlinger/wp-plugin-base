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
	public function register_component(ComponentInterface $component): void;

	/**
	 * Initialize all registered handlers
	 *
	 * @return void
	 */
	public function initialize_components(): void;

	/**
	 * Get all registered handlers
	 *
	 * @return ComponentInterface[]
	 */
	public function get_components(): array;


	/**
	 * Get handlers by class name
	 *
	 * @param string $class_name
	 *
	 * @return ComponentInterface[]
	 */
	public function get_components_by_class(string $class_name): array;

	/**
	 * Check if handler is registered
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public function has_component(string $class_name): bool;

	/**
	 * Remove a handler by class name
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public function remove_component(string $class_name): bool;

}