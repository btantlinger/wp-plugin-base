<?php

namespace WebMoves\PluginBase\Examples\Hooks;

use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\Components\ComponentInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Settings\BasicSettingsBuilder;

class AdminMenuHandler implements ComponentInterface
{
	private BasicSettingsBuilder $settings_builder;

	protected PluginCoreInterface $core;

	protected LoggerInterface $logger;

	public function __construct(PluginCoreInterface $core, BasicSettingsBuilder $settings_builder)
	{
		$this->settings_builder = $settings_builder;
		$this->core = $core;
		$this->logger = $core->get_logger('app');

	}

	public function register(): void
	{
		$this->logger->info('am handler register: ' . $this->settings_builder->get_settings_page());
		//$this->settings_builder->register();
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

		$this->logger->info('am handler callback (render_settings_page): ' . $this->settings_builder->get_settings_page());
		$this->settings_builder->render_form();
	}

	public function get_priority(): int {
		return 10;
	}

	public function can_register(): bool {
		return is_admin();
	}
}