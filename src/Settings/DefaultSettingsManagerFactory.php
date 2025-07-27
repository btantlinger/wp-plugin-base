<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Contracts\Settings\SettingsManager;

/**
 * Factory for creating DefaultSettingsManager instances with automatic prefix generation
 */
class DefaultSettingsManagerFactory implements SettingsManagerFactory
{

	private PluginMetadata $metadata;

	/**
	 * @param PluginMetadata $metadata
	 */
	public function __construct(PluginMetadata $metadata) {
		$this->metadata = $metadata;
	}


	/**
     * Create a DefaultSettingsManager instance with automatic prefix based on calling class
     *
     * @param object|string|null $context The class instance, class name, or null for auto-detection
     *
     * @return SettingsManager
     */
    public function create(object|string $scope = null): SettingsManager
    {
        $scope = $this->generate_scope($scope);
		$prefix = $this->metadata->get_prefix();
        return new DefaultSettingsManager($scope, $prefix);
    }



    /**
     * Generate a prefix based on the given context
     *
     * This implementation automatically prepends the scope with the metadata prefix
     * E.g PluginMetadata->get_prefix() . $context
     *
     * @param object|string|null $context The class instance, class name, or string
     * @return string The generated scope/prefix
     */
    public function generate_scope(object|string $context = null): string
    {
		if(empty($context)) {
			throw new \InvalidArgumentException('Context must be provided');
		}

        if (is_string($context)) {
            // Explicit class name provided
            $context = $this->class_name_to_prefix($context);
        } else if (is_object($context)) {
            // Object instance provided
            $context = $this->class_name_to_prefix(get_class($context));
        }
		return $context;
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
}