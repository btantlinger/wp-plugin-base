<?php

namespace WebMoves\PluginBase\Contracts\Components;


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
	public function initialize_components(): void;

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