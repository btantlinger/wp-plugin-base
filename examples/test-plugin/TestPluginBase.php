<?php

namespace WebMoves\PluginBase\Examples;

use WebMoves\PluginBase\PluginBase;
use WebMoves\PluginBase\Examples\Settings\MainPage;
use WebMoves\PluginBase\Examples\Settings\TestSettingsPage;

class TestPluginBase extends PluginBase
{
    public function initialize(): void
    {
	    $logger = $this->core->get_logger('app');
		$logger->info('Plugin Initialized');
    }

	public function get_services(): array {
		$plugin_slug = 'test-plugin-base';
		return [
			MainPage::class => new MainPage($plugin_slug, "Test Plugin Base", "Test Plugin"),
			TestSettingsPage::class => new TestSettingsPage($this->get_core(), 'Test Plugin Base Settings', 'Settings', $plugin_slug),
		];
	}
}