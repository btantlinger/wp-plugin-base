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

    public function register_component(string $id, ComponentInterface $component): void
    {
        if( $this->has_component($id) ) {
            throw new \RuntimeException("Component with id $id already registered");
        }

		$this->components[$id] = $component;

        // If we're already initialized, register the component immediately
        if ($this->initialized) {
            $this->register_single_component($component);
        }
    }

    /**
     * Initialize all registered components
     *
     * @return void
     */
    public function initialize_components(): void
    {
        if ($this->initialized) {
            return;
        }

        // Sort components by priority
        usort($this->components, function (ComponentInterface $a, ComponentInterface $b) {
            return $a->get_priority() <=> $b->get_priority();
        });

        // Register each component
        foreach ($this->components as $handler) {
            $this->register_single_component($handler);
        }

        $this->initialized = true;
    }

    /**
     * Register a single component
     *
     * @param \WebMoves\PluginBase\Contracts\Components\ComponentInterface $component
     *
     * @return void
     */
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


    public function has_component(string $id): bool
    {
		return isset($this->components[$id]);
    }

	public function get_component(string $id): ?ComponentInterface
    {
		if(!$this->has_component($id)) {
			return null;
		}
		return $this->components[$id];
    }

    public function remove_component(string $id): void
    {
		unset($this->components[$id]);
    }
}