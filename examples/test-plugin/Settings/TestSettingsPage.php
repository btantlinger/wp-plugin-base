<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Settings\BasicSettingsBuilder;
use WebMoves\PluginBase\Settings\SettingsPage;

class TestSettingsPage extends SettingsPage {

	public function __construct(PluginCoreInterface $core, string $pageTitle, string $menuTitle, ?string $parent_slug=null) {
		$builder = new BasicSettingsBuilder(
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