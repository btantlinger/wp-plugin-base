<?php

namespace WebMoves\PluginBase\Examples;

use WebMoves\PluginBase\AbstractPlugin;
use WebMoves\PluginBase\Logging\LoggerFactory;
use WebMoves\PluginBase\Settings\BasicSettingsBuilder;
use WebMoves\PluginBase\Examples\Settings\DemoSettingsProvider;
use WebMoves\PluginBase\Examples\Settings\ApiSettingsProvider;
use WebMoves\PluginBase\Examples\Hooks\AdminMenuHandler;

class TestPlugin extends AbstractPlugin
{

    private BasicSettingsBuilder $settings_builder;

    public function initialize(): void
    {
	    $this->init_settings();


	    $this->init_hooks();
	    $logger = LoggerFactory::createLogger($this->core->get_name(), $this->core->get_plugin_file(), 'app');
    }

    private function init_settings(): void
    {
        // Create settings builder
        $this->settings_builder = new BasicSettingsBuilder(
			$this->get_core(),
            'test_plugin_settings',
            'test-plugin-settings',
        );

        // Add settings providers
        $this->settings_builder->add_provider(new DemoSettingsProvider());
        $this->settings_builder->add_provider(new ApiSettingsProvider());


        // Initialize settings
        //$this->settings_builder->init();
    }

    private function init_hooks(): void
    {

	    $this->get_core()->register_component(new AdminMenuHandler($this->settings_builder));
	    $this->core->register_component($this->settings_builder);
    }

    public function get_settings_builder(): BasicSettingsBuilder
    {
        return $this->settings_builder;
    }
}