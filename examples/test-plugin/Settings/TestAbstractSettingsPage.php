<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Forms\DefaultSettingsForm;
use WebMoves\PluginBase\Forms\FormControllerSubmissionHandler;
use WebMoves\PluginBase\Forms\SettingsAPIFormRenderer;
use WebMoves\PluginBase\Forms\SettingsAPISubmissionHandler;
use WebMoves\PluginBase\Pages\AbstractSettingsPage;
use WebMoves\PluginBase\Settings\GlobalSyncSettings;

class TestAbstractSettingsPage extends \WebMoves\PluginBase\Pages\AbstractSettingsPage {

	private string $page_title;

	private string $menu_title;

	private string $text_domain;

	public function __construct(PluginCore $core, array $settings_providers = [], ?string $parent_slug = null) {
		$factory = $core->get(SettingsManagerFactory::class);
		$this->page_title = "Test Plugin Base Settings";
		$this->menu_title =  "Settings";
		$this->text_domain = $core->get_metadata()->get_text_domain();

		$providers = [
			//new DemoSettingsProvider($factory->create('test_plugin_demo_settings'), $core->get_metadata()),
			//new ApiSettingsProvider($factory->create('test_plugin_api_settings'), $core->get_metadata()),
		];
		$providers = array_merge($providers, $settings_providers);

		$page_id = empty($parent_slug) ? 'settings' : $parent_slug . '-settings';

		$form_handler = new FormControllerSubmissionHandler($core, $providers, $page_id);

		$form_renderer = new SettingsAPIFormRenderer($core->get(FlashData::class));

		$form = new DefaultSettingsForm($form_handler, $form_renderer, $page_id);

		parent::__construct($core, $form, $parent_slug);
	}

	public function get_page_title(): string {
		return $this->page_title;
	}

	public function get_menu_title(): string {
		return $this->menu_title;
	}
}