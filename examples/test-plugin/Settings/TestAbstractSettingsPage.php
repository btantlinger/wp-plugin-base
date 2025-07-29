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
use WebMoves\PluginBase\Settings\FormControllerSettingsBuilder;

class TestAbstractSettingsPage extends \WebMoves\PluginBase\Pages\AbstractSettingsPage {

	private string $page_title;

	private string $menu_title;

	private string $text_domain;

	public function __construct(PluginCore $core, string $pageTitle, string $menuTitle, string $parent_slug) {
		$factory = $core->get(SettingsManagerFactory::class);
		$this->page_title = $pageTitle;
		$this->menu_title = $menuTitle;
		$this->text_domain = $core->get_metadata()->get_text_domain();

		$providers = [
			new DemoSettingsProvider($factory->create('test_plugin_demo_settings'), $core->get_metadata()),
			new ApiSettingsProvider($factory->create('test_plugin_api_settings'), $core->get_metadata()),
		];

		$page_id = 'test-plugin-settings';

		//$form_handler = new SettingsAPISubmissionHandler($core, "test_plugin_settings", $page_id, $providers);

		$form_handler = new FormControllerSubmissionHandler($core, $providers, $page_id);

		$form_renderer = new SettingsAPIFormRenderer($core->get(FlashData::class));

		$form = new DefaultSettingsForm($form_handler, $form_renderer, $page_id);

		parent::__construct($core, $form, $parent_slug);
	}

	public function get_page_title(): string {
		return __($this->page_title, $this->text_domain);
	}

	public function get_menu_title(): string {
		return __($this->menu_title, $this->text_domain);
	}
}