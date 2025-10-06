<?php

namespace WebMoves\PluginBase\Configuration;

/**
 * Interface for feature configuration providers
 * 
 * Each feature provider implements this interface to provide configuration
 * and handle merging with existing configurations safely.
 */
interface FeatureConfigurationProviderInterface
{
    /**
     * Get the configuration for this feature
     * 
     * @param array $options Optional configuration options
     * @return array The feature configuration array
     */
    public function getConfiguration(array $options = []): array;

    /**
     * Merge this feature's configuration with an existing base configuration
     * 
     * This method should handle conflicts intelligently and ensure that
     * required sections exist before adding to them.
     * 
     * @param array $baseConfig The base configuration to merge into
     * @param array $options Optional configuration options for this feature
     * @return array The merged configuration
     */
    public function mergeConfiguration(array $baseConfig, array $options = []): array;

    /**
     * Get the feature name
     * 
     * @return string The feature name (e.g., 'sync', 'reporting')
     */
    public function getFeatureName(): string;

    /**
     * Get any dependencies this feature requires
     * 
     * @return array Array of required feature names
     */
    public function getDependencies(): array;
}
