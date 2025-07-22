<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsManager;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

abstract class AbstractSettingsProvider implements SettingsProvider {

	private SettingsManager $settings_manager;


	public function __construct(SettingsManager $settings_manager)
	{
		$this->settings_manager = $settings_manager;
	}

	public function settings(): SettingsManager
	{
		return $this->settings_manager;
	}
}