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

    /**
     * Register a component
     *
     * @param \WebMoves\PluginBase\Contracts\Components\ComponentInterface $component
     *
     * @return void
     */
    public function register_component(ComponentInterface $component): void
    {
        $this->components[] = $component;

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
    public function initialize_handlers(): void
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
    private function register_single_component( ComponentInterface $component): void
    {
        if (!$component->can_register()) {
            return;
        }

        $component->register();

    }

    /**
     * Get all registered components
     *
     * @return \WebMoves\PluginBase\Contracts\Components\ComponentInterface[]
     */
    public function get_components(): array
    {
        return $this->components;
    }

    /**
     * Get components by class name
     *
     * @param string $class_name
     *
     * @return \WebMoves\PluginBase\Contracts\Components\ComponentInterface[]
     */
    public function get_components_by_class(string $class_name): array
    {
        return array_filter($this->components, function (ComponentInterface $handler) use ($class_name) {
            return is_a($handler, $class_name);
        });
    }

    /**
     * Check if component is registered
     *
     * @param string $class_name
     * @return bool
     */
    public function has_component(string $class_name): bool
    {
        foreach ($this->components as $handler) {
            if (is_a($handler, $class_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a component by class name
     *
     * @param string $class_name
     * @return bool
     */
    public function remove_component(string $class_name): bool
    {
        $removed = false;
        $this->components = array_filter($this->components, function (ComponentInterface $handler) use ($class_name, &$removed) {
            if (is_a($handler, $class_name)) {
                $removed = true;
                return false;
            }
            return true;
        });

        return $removed;
    }
}