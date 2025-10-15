<?php

namespace WebMoves\PluginBase\Configuration;

/**
 * Interface for feature configuration providers
 * 
 * Each feature provider implements this interface to provide configuration arrays
 * that can be merged using ConfigurationProviderFactory::merge_configs().
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
