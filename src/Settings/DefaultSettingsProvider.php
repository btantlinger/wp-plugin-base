<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Contracts\Settings\SettingsManager;

class DefaultSettingsProvider extends AbstractSettingsProvider {

	private array $settings_configuration;

	public function __construct(SettingsManager $settings_manager,  array $settings_configuration) {
		parent::__construct($settings_manager);
		$this->settings_configuration = $settings_configuration;
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings_configuration(): array {
		return $this->settings_configuration;
	}
}