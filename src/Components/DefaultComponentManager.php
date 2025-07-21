<?php

namespace WebMoves\PluginBase\Components;

use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Components\ComponentManager;
use WebMoves\PluginBase\Enums\Lifecycle;

class DefaultComponentManager implements ComponentManager
{
	private array $components = [];
	private array $initialized_lifecycles = [];

	public function __construct()
	{

	}

	public function add(Component $component): void
	{
		if ($this->contains($component)) {
			throw new \RuntimeException("Component already registered");
		}

		$this->components[] = $component;

		// If this lifecycle has already been initialized, register the component immediately
		$lifecycle = $component->register_on();
		if (in_array($lifecycle, $this->initialized_lifecycles, true)) {
			$this->register_single_component($component);
		}
	}

	/**
	 * Initialize components for a specific lifecycle
	 */
	public function initialize_components_for_lifecycle(Lifecycle $lifecycle): void
	{
		if (in_array($lifecycle, $this->initialized_lifecycles, true)) {
			return;
		}

		// Get components for this specific lifecycle
		$lifecycle_components = $this->get_components_for_lifecycle($lifecycle);

		// Sort components by priority
		usort($lifecycle_components, function (Component $a, Component $b) {
			return $a->get_priority() <=> $b->get_priority();
		});

		// Process components until none are left unprocessed
		$processed = [];

		while (count($processed) < count($lifecycle_components)) {
			$current_count = count($lifecycle_components);

			// Find unprocessed components
			$unprocessed = array_filter($lifecycle_components, function($component) use ($processed) {
				return !in_array($component, $processed, true);
			});

			// Process each unprocessed component
			foreach ($unprocessed as $component) {
				$this->register_single_component($component);
				$processed[] = $component;
			}

			// If new components were added during registration, refresh the lifecycle components list
			$new_lifecycle_components = $this->get_components_for_lifecycle($lifecycle);
			if (count($new_lifecycle_components) > $current_count) {
				$lifecycle_components = $new_lifecycle_components;
				
				// Re-sort with new components
				usort($lifecycle_components, function (Component $a, Component $b) {
					return $a->get_priority() <=> $b->get_priority();
				});
			}
		}

		$this->initialized_lifecycles[] = $lifecycle;
	}

	/**
	 * Legacy method for backward compatibility - initializes INIT lifecycle components
	 */
	public function initialize_components(): void
	{
		$this->initialize_components_for_lifecycle(Lifecycle::INIT);
	}

	/**
	 * Get components that should register on a specific lifecycle
	 */
	private function get_components_for_lifecycle(Lifecycle $lifecycle): array
	{
		return array_filter($this->components, function(Component $component) use ($lifecycle) {
			return $component->register_on() === $lifecycle;
		});
	}

	private function register_single_component(Component $component): void
	{
		if (!$component->can_register()) {
			return;
		}

		$component->register();
	}

	public function get_components(): array
	{
		return $this->components;
	}

	/**
	 * Get components for a specific lifecycle
	 */
	public function get_components_by_lifecycle(Lifecycle $lifecycle): array
	{
		return $this->get_components_for_lifecycle($lifecycle);
	}

	/**
	 * Get all initialized lifecycles
	 */
	public function get_initialized_lifecycles(): array
	{
		return $this->initialized_lifecycles;
	}

	/**
	 * Check if a specific lifecycle has been initialized
	 */
	public function is_lifecycle_initialized(Lifecycle $lifecycle): bool
	{
		return in_array($lifecycle, $this->initialized_lifecycles, true);
	}

	public function contains(Component $component): bool
	{
		return in_array($component, $this->components, true);
	}

	public function remove(Component $component): void
	{
		$key = array_search($component, $this->components, true);
		if ($key !== false) {
			unset($this->components[$key]);
			// Re-index array to avoid gaps
			$this->components = array_values($this->components);
		}
	}
}