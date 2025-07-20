<?php

namespace WebMoves\PluginBase\Components\Support;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Enums\Lifecycle;

class DependencyManager extends AbstractComponent {
	
	private array $plugin_dependencies;

	private PluginCoreInterface $core;

	private array $dependency_issues = [];

	/**
	 * @param PluginCoreInterface $core The plugin core instance
	 * @param array $plugin_dependencies Associative array of dependent plugins where key is plugin path and value is either:
	 *   - String: plugin name (for backward compatibility)
	 *   - Array: ['name' => 'Plugin Name', 'min_version' => '1.0.0']
	 * 
	 * Example: [
	 *   'woocommerce/woocommerce.php' => ['name' => 'WooCommerce', 'min_version' => '8.0.0'],
	 *   'acf/acf.php' => 'Advanced Custom Fields'  // backward compatibility
	 * ]
	 */
	public function __construct(PluginCoreInterface $core, array $plugin_dependencies = []) {
		parent::__construct();
		$this->core = $core;
		$deps = $this->core->get_config()->get('dependencies.required_plugins', [] );
		$this->plugin_dependencies = array_merge($deps, $plugin_dependencies);
	}

	/**
	 * @inheritDoc
	 */
	public function register(): void {		
		$this->check_dependencies();
	}
	
	public function check_dependencies(): void {
		foreach($this->plugin_dependencies as $plugin_path => $plugin_config) {
			$issue = $this->check_plugin_requirement($plugin_path, $plugin_config);
			$this->dependency_issues[$plugin_path] = $issue;
		}
	}

	public function get_plugin_core(): PluginCoreInterface {
		return $this->core;
	}

	public function get_plugin_dependencies(): array {
		return $this->plugin_dependencies;
	}


	public function get_dependency_issues(): array
	{
		return $this->dependency_issues;
	}

	public function has_dependency_issues(): bool
	{
		return !empty($this->dependency_issues);
	}



	public function is_missing_dependency(string $plugin_path): bool
	{
		return !empty($this->dependency_issues[$plugin_path]);
	}

	public function is_dependency_issue(string $plugin_path): bool
	{
		$issue = $this->dependency_issues[$plugin_path] ?? null;
		return $issue && $issue['type'] !== 'inactive';
	}

	/**
	 * Check if a plugin meets the requirements
	 * 
	 * @param string $plugin_path Path to the plugin file
	 * @param string|array $plugin_config Plugin configuration (name or array with name/version)
	 * @return array|null Issue details if there's a problem, null if all good
	 */
	private function check_plugin_requirement(string $plugin_path, $plugin_config): ?array {
		// Normalize config to array format
		$config = $this->normalize_plugin_config($plugin_config);
		$plugin_name = $config['name'];
		$min_version = $config['min_version'] ?? null;

		// Check if plugin is active
		if (!is_plugin_active($plugin_path)) {
			return [
				'type' => 'inactive',
				'name' => $plugin_name,
				'required_version' => $min_version,
				'current_version' => null
			];
		}

		// If no version requirement, we're good
		if (!$min_version) {
			return null;
		}

		// Check version requirement
		$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_path;
		if (!file_exists($plugin_file)) {
			return [
				'type' => 'missing_file',
				'name' => $plugin_name,
				'required_version' => $min_version,
				'current_version' => null
			];
		}

		$plugin_data = get_plugin_data($plugin_file);
		$current_version = $plugin_data['Version'] ?? '0.0.0';

		if (version_compare($current_version, $min_version, '<')) {
			return [
				'type' => 'outdated',
				'name' => $plugin_name,
				'required_version' => $min_version,
				'current_version' => $current_version
			];
		}

		return null; // All requirements met
	}

	/**
	 * Normalize plugin config to array format
	 * 
	 * @param string|array $config
	 * @return array
	 */
	private function normalize_plugin_config($config): array {
		if (is_string($config)) {
			return ['name' => $config, 'min_version' => null];
		}

		return array_merge(['name' => '', 'min_version' => null], $config);
	}

	public function can_register(): bool {
		return is_admin() && !empty($this->plugin_dependencies);
	}

	public function register_on(): Lifecycle {
		return Lifecycle::BOOTSTRAP;
	}
}