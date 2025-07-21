<?php

namespace WebMoves\PluginBase\Contracts\Components;


use WebMoves\PluginBase\Enums\Lifecycle;

interface ComponentManagerInterface {
	/**
	 * add a component
	 *
	 * @param ComponentInterface $component
	 * @return void
	 */
	public function add(ComponentInterface $component): void;

	/**
	 * Remove a component instance
	 *
	 * @param ComponentInterface $component
	 * @return void
	 */
	public function remove(ComponentInterface $component): void;


	/**
	 * Check if a component instance is added
	 *
	 * @param ComponentInterface $component
	 * @return bool
	 */
	public function contains(ComponentInterface $component): bool;


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
	 * @return ComponentInterface[]
	 */
	public function get_components(): array;

}