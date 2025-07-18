<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Settings\BasicSettingsBuilder;
use WebMoves\PluginBase\Settings\SettingsPage;

class TestSettingsPage extends SettingsPage {



	public function __construct(PluginCoreInterface $core, ?string $parent_slug=null) {


		// Use EXACTLY the same configuration as init_settings()
		$builder = new BasicSettingsBuilder($core, "foo_plugin_settings", "foo-plugin-settings");
    	$builder->add_provider(new DemoSettingsProvider('foo-demo'));
		$builder->add_provider(new ApiSettingsProvider('foo-api'));


		parent::__construct($builder, "Test Plugin Settings", "Test Plugin", $parent_slug);



	}
}