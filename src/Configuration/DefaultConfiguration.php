<?php

namespace WebMoves\PluginBase\Configuration;

use WebMoves\PluginBase\Contracts\Configuration\Configuration;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;

class DefaultConfiguration implements Configuration
{
	private array $config = [];
	private bool $loaded = false;
	private string $plugin_path;
	private string $framework_path;

	public function __construct(PluginCore $core)
	{
		$this->plugin_path = $core->get_plugin_base_dir();
		$this->framework_path = dirname(__DIR__, 2); // Go up to plugin-base root
	}

	/**
	 * Load and merge configuration files
	 */
	public function load(): void
	{
		if ($this->loaded) {
			return;
		}

		// 1. Load framework default config
		$framework_config = $this->loadConfigFile($this->framework_path . '/config/plugin.config.php');

		// 2. Load plugin-specific config (if exists)
		$plugin_config = $this->loadConfigFile($this->plugin_path . '/config/plugin.config.php');

		// 3. Load environment-specific config (if exists)
		$environment = $this->determineEnvironment();
		$env_config = $this->loadConfigFile($this->plugin_path . "/config/plugin.{$environment}.php");

		// 4. Merge configurations (plugin overrides framework, environment overrides all)
		$this->config = $this->mergeConfigs($framework_config, $plugin_config, $env_config);

		$this->loaded = true;
	}

	/**
	 * Get configuration value using dot notation
	 */
	public function get(string $key, $default = null)
	{
		$this->load();

		$keys = explode('.', $key);
		$value = $this->config;

		foreach ($keys as $segment) {
			if (!is_array($value) || !array_key_exists($segment, $value)) {
				return $default;
			}
			$value = $value[$segment];
		}

		return $value;
	}

	/**
	 * Get all configuration
	 */
	public function all(): array
	{
		$this->load();
		return $this->config;
	}

	/**
	 * Check if configuration key exists
	 */
	public function has(string $key): bool
	{
		return $this->get($key) !== null;
	}

	/**
	 * Set configuration value at runtime
	 */
	public function set(string $key, $value): void
	{
		$this->load();

		$keys = explode('.', $key);
		$config = &$this->config;

		while (count($keys) > 1) {
			$key = array_shift($keys);
			if (!isset($config[$key]) || !is_array($config[$key])) {
				$config[$key] = [];
			}
			$config = &$config[$key];
		}

		$config[array_shift($keys)] = $value;
	}

	/**
	 * Load a configuration file
	 */
	private function loadConfigFile(string $path): array
	{
		if (!file_exists($path)) {
			return [];
		}

		$config = require $path;
		return is_array($config) ? $config : [];
	}

	/**
	 * Merge multiple configuration arrays
	 */
	private function mergeConfigs(array ...$configs): array
	{
		$result = [];

		foreach ($configs as $config) {
			$result = $this->arrayMergeRecursive($result, $config);
		}

		return $result;
	}

	/**
	 * Recursively merge arrays, replacing values for non-array items
	 */
	private function arrayMergeRecursive(array $array1, array $array2): array
	{
		foreach ($array2 as $key => $value) {
			if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
				$array1[$key] = $this->arrayMergeRecursive($array1[$key], $value);
			} else {
				$array1[$key] = $value;
			}
		}

		return $array1;
	}

	/**
	 * Determine the current environment
	 */
	private function determineEnvironment(): string
	{
		if (defined('WP_ENVIRONMENT_TYPE')) {
			//return defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE;
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			return 'development';
		}

		return 'production';
	}

	/**
	 * Get required plugins from configuration
	 */
	public function getRequiredPlugins(): array
	{
		return $this->get('dependencies.required_plugins', []);
	}

	/**
	 * Get components from configuration (replaces both getCoreComponents and getPluginComponents)
	 */
	public function getComponents(): array
	{
		return $this->get('components', []);
	}

	/**
	 * Get services from configuration
	 */
	public function getServices(): array
	{
		return $this->get('services', []);
	}


	/**
	 * Get logging configuration
	 */
	public function getLoggingConfig(): array
	{
		return $this->get('logging', []);
	}

}