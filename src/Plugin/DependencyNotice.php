<?php

namespace WebMoves\PluginBase\Plugin;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Plugin\DependencyManager;

class DependencyNotice extends AbstractComponent  {

	private DependencyManager $dependency_manager;
	private PluginCore $core;

	private ?string $plugin_name = null;

	public function __construct(DependencyManager $dependency_manager) {
		parent::__construct();
		$this->dependency_manager = $dependency_manager;
		$this->core = $dependency_manager->get_plugin_core();
	}

	public function register_on(): Lifecycle {
		return Lifecycle::ADMIN_INIT;
	}

	public function register(): void {
		add_action('admin_notices', [$this, 'display_dependency_issues']);
	}

	public function can_register(): bool {
		return $this->dependency_manager->has_dependency_issues() && is_admin();
	}

	public function display_dependency_issues(): void
	{
		if ($this->dependency_manager->has_dependency_issues()) {
			$issues = $this->dependency_manager->get_dependency_issues();
			foreach ($issues as $plugin_path => $issue) {
				if ($issue) {
					$this->display_notice($issue);
				}
			}
		}
	}

	/**
	 * Display appropriate notice based on issue type
	 *
	 * @param array $issue Issue details
	 */
	private function display_notice(array $issue): void {
		$plugin_name = $this->get_plugin_name();
		$required_name = $issue['name'];
		$required_version = $issue['required_version'];
		$current_version = $issue['current_version'];

		switch ($issue['type']) {
			case 'inactive':
				if ($required_version) {
					$message = sprintf(
						__('%s requires %s (version %s or higher) to be installed and active.', $this->core->get_text_domain()),
						$plugin_name,
						$required_name,
						$required_version
					);
				} else {
					$message = sprintf(
						__('%s requires %s to be active.', $this->core->get_text_domain()),
						$plugin_name,
						$required_name
					);
				}
				break;

			case 'outdated':
				$message = sprintf(
					__('%s requires %s version %s or higher. You have version %s installed. Please update %s.', $this->core->get_text_domain()),
					$plugin_name,
					$required_name,
					$required_version,
					$current_version,
					$required_name
				);
				break;

			case 'missing_file':
				$message = sprintf(
					__('%s requires %s (version %s or higher) to be installed. The plugin file could not be found.', $this->core->get_text_domain()),
					$plugin_name,
					$required_name,
					$required_version
				);
				break;

			default:
				$message = sprintf(
					__('%s has a dependency issue with %s.', $this->core->get_text_domain()),
					$plugin_name,
					$required_name
				);
		}

		echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
	}

	public function get_plugin_name(): string {
		if (!$this->plugin_name) {
			$file = $this->core->get_plugin_file();
			$data = get_plugin_data($file);
			if (!$data) {
				return $file;
			}
			$this->plugin_name = $data['Name'];
		}
		return $this->plugin_name;
	}
}