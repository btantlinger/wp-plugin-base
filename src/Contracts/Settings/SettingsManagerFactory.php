<?php

namespace WebMoves\PluginBase\Contracts\Settings;

interface SettingsManagerFactory
{
    /**
     * Create a DefaultSettingsManager instance with automatic prefix based on calling class
     *
     * The prefix will be automatically generated based on the provided context:
     * - If an object is provided, uses the object's class name
     * - If a string is provided, treats it as a class name
     * - If null is provided, attempts to detect the calling class from backtrace
     *
     * @param object|string|null $context The class instance, class name, or null for auto-detection
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
    public function create($context = null ): SettingsManager;

    /**
     * Create a DefaultSettingsManager instance with explicit prefix
     *
     * Use this method when you need full control over the prefix or when
     * the automatic prefix generation doesn't meet your needs.
     *
     * @param string $prefix The prefix to use for all option keys
     *
     * @return SettingsManager
     *
     * @example
     * $settings = $factory->create_with_prefix('my_custom_prefix');
     */
    public function create_with_prefix(string $prefix ): SettingsManager;

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
    public function generate_prefix($context = null): string;
}