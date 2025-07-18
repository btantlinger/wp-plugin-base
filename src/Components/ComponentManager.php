<?php

namespace WebMoves\PluginBase\Components;

use WebMoves\PluginBase\Contracts\Components\ComponentInterface;
use WebMoves\PluginBase\Contracts\Components\ComponentManagerInterface;

class ComponentManager implements ComponentManagerInterface
{
	private array $components = [];
	private bool $initialized = false;

	public function __construct()
	{

	}

	public function register_component(ComponentInterface $component): void
	{
		if ($this->is_registered($component)) {
			throw new \RuntimeException("Component already registered");
		}

		$this->components[] = $component;

		// If we're already initialized, register the component immediately
		if ($this->initialized) {
			$this->register_single_component($component);
		}
	}

	public function initialize_components(): void
	{
		if ($this->initialized) {
			return;
		}

		// Sort components by priority initially
		usort($this->components, function (ComponentInterface $a, ComponentInterface $b) {
			return $a->get_priority() <=> $b->get_priority();
		});

		// Process components until none are left unprocessed
		$processed = [];

		while (count($processed) < count($this->components)) {
			$current_count = count($this->components);

			// Find unprocessed components
			$unprocessed = array_filter($this->components, function($component) use ($processed) {
				return !in_array($component, $processed, true);
			});

			// Process each unprocessed component
			foreach ($unprocessed as $component) {
				$this->register_single_component($component);
				$processed[] = $component;
			}

			// If new components were added, re-sort the entire array
			if (count($this->components) > $current_count) {
				usort($this->components, function (ComponentInterface $a, ComponentInterface $b) {
					return $a->get_priority() <=> $b->get_priority();
				});
			}
		}

		$this->initialized = true;
	}


	private function register_single_component(ComponentInterface $component): void
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

	public function is_registered(ComponentInterface $component): bool
	{
		return in_array($component, $this->components, true);
	}

	public function remove_component(ComponentInterface $component): void
	{
		$key = array_search($component, $this->components, true);
		if ($key !== false) {
			unset($this->components[$key]);
			// Re-index array to avoid gaps
			$this->components = array_values($this->components);
		}
	}
}