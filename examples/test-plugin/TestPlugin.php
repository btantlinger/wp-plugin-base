<?php

namespace WebMoves\PluginBase\Examples;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Settings\BasicSettingsBuilder;
use WebMoves\PluginBase\Examples\Settings\DemoSettingsProvider;
use WebMoves\PluginBase\Examples\Settings\ApiSettingsProvider;
use WebMoves\PluginBase\Examples\Hooks\AdminMenuHandler;

class TestPlugin
{
    private PluginCoreInterface $core;
    private BasicSettingsBuilder $settings_builder;

    public function __construct(PluginCoreInterface $core)
    {
        $this->core = $core;
        $this->init_settings();
        $this->init_hooks();
    }

    private function init_settings(): void
    {
        // Create settings builder
        $this->settings_builder = new BasicSettingsBuilder(
            'test_plugin_settings',
            'test-plugin-settings',
            'test-plugin'
        );

        // Add settings providers
        $this->settings_builder->add_provider(new DemoSettingsProvider());
        $this->settings_builder->add_provider(new ApiSettingsProvider());

        // Initialize settings
        $this->settings_builder->init();
    }

    private function init_hooks(): void
    {
        // Register admin menu handler
        $this->core->register_handler(new AdminMenuHandler($this->settings_builder));
    }

    public function get_settings_builder(): BasicSettingsBuilder
    {
        return $this->settings_builder;
    }
}