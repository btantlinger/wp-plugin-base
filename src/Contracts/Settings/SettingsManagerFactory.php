<?php

namespace WebMoves\PluginBase\Contracts\Settings;

interface SettingsManagerFactory
{

    /**
     * Create a DefaultSettingsManager instance with scope
     *
     * 'scope' is an option 'namespace' or 'prefix'
     *
     * The scope will be automatically generated based on the provided context:
     * - If an object is provided, uses the object's class name
     * - If a string is provided, treats it as a class name
     *
     * @param object|string|null $scope The class instance, class name, or arbitrary string
     *
     * @return SettingsManager
     *
     * @example
     * // Using object instance (recommended)
     * $settings = $factory->create($this);
     *
     * // Using class name
     * $settings = $factory->create(MyClass::class);
     *
     * // Auto-detection from backtrace
     * $settings = $factory->create();
     */
    public function create(object|string $scope = null): SettingsManager;


    /**
     * Generate a prefix based on the given context
     *
     * This method can be used to preview what prefix would be generated
     * for a given context without actually creating a DefaultSettingsManager instance.
     *
     * @param object|string|null $context The class instance, class name, or null for auto-detection
     * @return string The generated prefix
     *
     * @example
     * $prefix = $factory->generate_prefix($this);
     * // Returns something like: "myplugin_sync_products_productsynchandler"
     */
    public function generate_scope(object|string $context = null): string;
}