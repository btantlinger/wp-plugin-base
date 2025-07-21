<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Settings\DefaultSettingsBuilder;
use WebMoves\PluginBase\Settings\SettingsPage;

class TestSettingsPage extends SettingsPage {

	public function __construct(PluginCore $core, string $pageTitle, string $menuTitle, ?string $parent_slug=null) {
		$builder = new DefaultSettingsBuilder(
			$core,
			"foo_plugin_settings",
			"foo-plugin-settings",
			[
				new DemoSettingsProvider('foo-demo'),
				new ApiSettingsProvider('foo-api')
			]
		);
		parent::__construct($builder, $pageTitle, $menuTitle, $parent_slug);
	}
}