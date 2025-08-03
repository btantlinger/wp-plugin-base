<?php

namespace WebMoves\PluginBase\Examples;

use WebMoves\PluginBase\Examples\Components\BookReview;
use WebMoves\PluginBase\Examples\Components\Genre;
use WebMoves\PluginBase\PluginBase;
use WebMoves\PluginBase\Examples\Settings\MainPage;
use WebMoves\PluginBase\Examples\Settings\TestAbstractSettingsPage;

class TestPlugin extends PluginBase
{
    public function initialize(): void
    {
	    $logger = $this->core->get_logger('app');
		$logger->info('Plugin Initialized');
    }

	public function get_services(): array {
		$plugin_slug = 'test-plugin-base';
		return [
			MainPage::class  => new MainPage($this->get_core(), $plugin_slug, "Test Plugin Base", "Test Plugin"),
			TestAbstractSettingsPage::class => new TestAbstractSettingsPage($this->get_core(), 'Test Plugin Base Settings', 'Settings', $plugin_slug),
			BookReview::class => new BookReview(),
			Genre::class => new Genre(),
		];
	}
}