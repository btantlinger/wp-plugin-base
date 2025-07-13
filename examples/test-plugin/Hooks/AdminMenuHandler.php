<?php

namespace WebMoves\PluginBase\Examples\Hooks;

use WebMoves\PluginBase\Contracts\Hooks\ComponentInterface;
use WebMoves\PluginBase\Settings\BasicSettingsBuilder;

class AdminMenuHandler implements ComponentInterface
{
	private BasicSettingsBuilder $settings_builder;

	public function __construct(BasicSettingsBuilder $settings_builder)
	{
		$this->settings_builder = $settings_builder;
	}

	public function register(): void
	{
		add_action('admin_menu', [$this, 'add_admin_menu']);
	}

	public function add_admin_menu(): void
	{
		add_options_page(
			__('Test Plugin Settings', 'test-plugin'),
			__('Test Plugin', 'test-plugin'),
			'manage_options',
			'test-plugin-settings',
			[$this, 'render_settings_page']
		);
	}

	public function render_settings_page(): void
	{
		$this->settings_builder->render_settings_page();
	}

	public function get_priority(): int {
		return 10;
	}

	public function can_register(): bool {
		return true;
	}
}