<?php

namespace WebMoves\PluginBase\Contracts\Components;


use WebMoves\PluginBase\Enums\Lifecycle;

interface ComponentManager {
	/**
	 * add a component
	 *
	 * @param Component $component
	 *
	 * @return void
	 */
	public function add( Component $component): void;

	/**
	 * Remove a component instance
	 *
	 * @param Component $component
	 *
	 * @return void
	 */
	public function remove( Component $component): void;


	/**
	 * Check if a component instance is added
	 *
	 * @param Component $component
	 *
	 * @return bool
	 */
	public function contains(Component $component): bool;


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
	 * Get all components
	 *
	 * @return Component[]
	 */
	public function get_components(): array;

}