<?php

namespace WebMoves\PluginBase\Contracts\Components;


use WebMoves\PluginBase\Enums\Lifecycle;

interface ComponentManagerInterface {
	/**
	 * Register a component
	 *
	 * @param ComponentInterface $component
	 * @return void
	 */
	public function register_component(ComponentInterface $component): void;

	/**
	 * Initialize all registered components
	 *
	 * @return void
	 */
	public function initialize_components_for_lifecycle(Lifecycle $lifecycle): void;


	/**
	 * Check if a specific lifecycle has been initialized
	 */
	public function is_lifecycle_initialized(Lifecycle $lifecycle): bool;


	/**
	 * @param Lifecycle $lifecycle
	 *
	 * @return array
	 */
	public function get_components_by_lifecycle(Lifecycle $lifecycle): array;

	/**
	 * Get all registered components
	 *
	 * @return ComponentInterface[]
	 */
	public function get_components(): array;

	/**
	 * Check if a component instance is registered
	 *
	 * @param ComponentInterface $component
	 * @return bool
	 */
	public function is_registered(ComponentInterface $component): bool;

	/**
	 * Remove a component instance
	 *
	 * @param ComponentInterface $component
	 * @return void
	 */
	public function remove_component(ComponentInterface $component): void;

}