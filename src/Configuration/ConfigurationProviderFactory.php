<?php

namespace WebMoves\PluginBase\Configuration;

use WebMoves\PluginBase\Configuration\Providers\SyncConfigurationProvider;

/**
 * Factory for managing feature-based configuration providers
 * 
 * This factory manages different configuration providers for optional framework features.
 * Each provider knows how to merge its configuration safely with existing configurations.
 * 
 * Usage:
 * ```php
 * $factory = new ConfigurationProviderFactory();
 * $config = $factory->mergeFeatureConfigurations($baseConfig, ['sync', 'reporting']);
 * ```
 */
class ConfigurationProviderFactory
{
    /**
     * Available configuration providers
     * 
     * @var array<string, string> Feature name => Provider class name
     */
    private static array $providers = [
        'sync' => SyncConfigurationProvider::class,
        // Future features would be added here:
        // 'reporting' => ReportingConfigurationProvider::class,
        // 'analytics' => AnalyticsConfigurationProvider::class,
    ];

    /**
     * Merge feature configurations into base configuration
     * 
     * This is a convenience method that gets feature configs and merges them.
     * For more control over merge order, use getFeatureConfiguration() and merge_configs() directly.
     * 
     * @param array $baseConfig The base configuration array
     * @param array $enabledFeatures Array of feature names to enable
     * @param array $featureOptions Optional configuration for each feature
     * @return array The merged configuration
     * 
     * @throws \InvalidArgumentException If an unknown feature is requested
     */
    public static function mergeFeatureConfigurations(
        array $baseConfig, 
        array $enabledFeatures, 
        array $featureOptions = []
    ): array {
        $mergedConfig = $baseConfig;

        foreach ($enabledFeatures as $featureName) {
            $featureConfig = self::getFeatureConfiguration($featureName, $featureOptions[$featureName] ?? []);
            $mergedConfig = self::merge_configs($mergedConfig, $featureConfig);
        }

        return $mergedConfig;
    }

    /**
     * Get configuration for a single feature without merging
     * 
     * @param string $featureName The feature name
     * @param array $options Optional configuration for the feature
     * @return array The feature configuration
     * 
     * @throws \InvalidArgumentException If an unknown feature is requested
     */
    public static function getFeatureConfiguration(string $featureName, array $options = []): array
    {
        if (!isset(self::$providers[$featureName])) {
            throw new \InvalidArgumentException("Unknown feature: {$featureName}");
        }

        $providerClass = self::$providers[$featureName];

        /** @var FeatureConfigurationProviderInterface $provider */
        $provider = new $providerClass();
        return $provider->getConfiguration($options);
    }

    /**
     * Check if a feature is available
     * 
     * @param string $featureName The feature name to check
     * @return bool True if the feature is available
     */
    public static function isFeatureAvailable(string $featureName): bool
    {
        return isset(self::$providers[$featureName]);
    }

    /**
     * Get all available features
     * 
     * @return array<string> Array of available feature names
     */
    public static function getAvailableFeatures(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * Register a new configuration provider
     * 
     * This allows third-party extensions to register their own feature providers
     * 
     * @param string $featureName The feature name
     * @param string $providerClass The provider class name (must implement FeatureConfigurationProviderInterface)
     * 
     * @throws \InvalidArgumentException If provider class doesn't implement the interface
     */
    public static function registerProvider(string $featureName, string $providerClass): void
    {
        if (!class_exists($providerClass)) {
            throw new \InvalidArgumentException("Provider class does not exist: {$providerClass}");
        }

        if (!in_array(FeatureConfigurationProviderInterface::class, class_implements($providerClass))) {
            throw new \InvalidArgumentException(
                "Provider class must implement FeatureConfigurationProviderInterface: {$providerClass}"
            );
        }

        self::$providers[$featureName] = $providerClass;
    }

    /**
     * Deep merge configuration arrays, with later values overwriting earlier ones.
     * Unlike array_merge_recursive, this doesn't create nested arrays for duplicate keys.
     * Indexed arrays (like components, services) are concatenated in order.
     * 
     * @param array $array1 First configuration array
     * @param array $array2 Second configuration array (takes precedence)
     * @return array Merged configuration array
     */
    public static function merge_configs(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                // If both are associative arrays, merge recursively
                if (self::isAssociativeArray($merged[$key]) && self::isAssociativeArray($value)) {
                    $merged[$key] = self::merge_configs($merged[$key], $value);
                } else {
                    // For indexed arrays (like components), concatenate them
                    $merged[$key] = array_merge($merged[$key], $value);
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Check if an array is associative (has string keys)
     * 
     * @param array $array Array to check
     * @return bool True if associative, false if indexed
     */
    private static function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
