<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Settings\DefaultSettingsBuilder;
use WebMoves\PluginBase\Settings\SettingsPage;

class TestSettingsPage extends SettingsPage {

	public function __construct(PluginCore $core, string $pageTitle, string $menuTitle, ?string $parent_slug=null) {
		$factory = $core->get(SettingsManagerFactory::class);
		$builder = new DefaultSettingsBuilder(
			$core,
			"test_plugin_settings",
			"test-plugin-settings",
			[
				new DemoSettingsProvider($factory->create('test_plugin_demo_settings'), $core->get_metadata()),
				new ApiSettingsProvider($factory->create('test_plugin_api_settings'), $core->get_metadata()),
			]
		);
		parent::__construct($builder, $pageTitle, $menuTitle, $parent_slug);
	}
}