<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactoryInterface;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerInterface;

/**
 * Factory for creating SettingsManager instances with automatic prefix generation
 */
class SettingsManagerFactory implements SettingsManagerFactoryInterface
{

    /**
     * Create a SettingsManager instance with automatic prefix based on calling class
     *
     * @param object|string|null $context The class instance, class name, or null for auto-detection
     * @return SettingsManagerInterface
     */
    public function create(object|string|null $context = null): SettingsManagerInterface
    {
        $prefix = $this->generate_prefix($context);
        return new SettingsManager($prefix);
    }

    /**
     * Create a SettingsManager instance with explicit prefix
     *
     * @param string $prefix The prefix to use
     * @return SettingsManagerInterface
     */
    public function create_with_prefix(string $prefix): SettingsManagerInterface
    {
        return new SettingsManager($prefix);
    }

    /**
     * Generate a prefix based on the given context
     *
     * @param object|string|null $context The class instance, class name, or null for auto-detection
     * @return string The generated prefix
     */
    public function generate_prefix(object|string|null $context = null): string
    {
        if (is_string($context)) {
            // Explicit class name provided
            return $this->class_name_to_prefix($context);
        }
        
        if (is_object($context)) {
            // Object instance provided
            return $this->class_name_to_prefix(get_class($context));
        }
        
        // Auto-detect from backtrace
        return $this->detect_prefix_from_backtrace();
    }

    /**
     * Convert class name to WordPress-safe prefix
     *
     * @param string $class_name
     * @return string
     */
    private function class_name_to_prefix(string $class_name): string
    {
        // Remove namespace separators and convert to snake_case
        $clean_name = str_replace('\\', '_', $class_name);
        $clean_name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $clean_name);
        $clean_name = strtolower($clean_name);
        
        // Ensure WordPress-safe
        return sanitize_key($clean_name);
    }

    /**
     * Detect prefix from call stack
     *
     * @return string
     */
    private function detect_prefix_from_backtrace(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        
        foreach ($backtrace as $frame) {
            if (isset($frame['class']) && $frame['class'] !== self::class) {
                return $this->class_name_to_prefix($frame['class']);
            }
        }
        
        // Fallback
        return 'plugin_base_settings';
    }
}