<?php

namespace WebMoves\PluginBase\Examples;

use WebMoves\PluginBase\AbstractPlugin;
use WebMoves\PluginBase\Examples\Settings\TestSettingsPage;
use WebMoves\PluginBase\Logging\LoggerFactory;
use WebMoves\PluginBase\Settings\BasicSettingsBuilder;
use WebMoves\PluginBase\Examples\Settings\DemoSettingsProvider;
use WebMoves\PluginBase\Examples\Settings\ApiSettingsProvider;
use WebMoves\PluginBase\Examples\Hooks\AdminMenuHandler;
use WebMoves\PluginBase\Settings\MenuAdminPage;

class TestPlugin extends AbstractPlugin
{

    private BasicSettingsBuilder $settings_builder;

    public function initialize(): void
    {
	    $logger = LoggerFactory::createLogger($this->core->get_name(), $this->core->get_plugin_file(), 'app');


		$slug = "foo-plugin";
		$page = new MenuAdminPage( $slug, "Foo Main Page", "Foo Main");
		$sub_page = new TestSettingsPage($this->get_core(), $slug);


		$this->get_core()->set(MenuAdminPage::class, $page);
		$this->get_core()->set(TestSettingsPage::class, $sub_page);;
    }

    private function init_settings(): void
    {
        // Create settings builder
        $this->settings_builder = new BasicSettingsBuilder(
			$this->get_core(),
            'test_plugin_settings',
            'test-plugin-settingsss',
        );

        // Add settings providers
        $this->settings_builder->add_provider(new DemoSettingsProvider('test-scope-demo'));
        $this->settings_builder->add_provider(new ApiSettingsProvider('test-scope-api'));;

	    //$this->core->set("settings-builder", $this->settings_builder);
		$this->settings_builder->register();
	    $this->core->set(AdminMenuHandler::class, new AdminMenuHandler($this->core, $this->settings_builder));

    }


    public function get_settings_builder(): BasicSettingsBuilder
    {
        return $this->settings_builder;
    }
}