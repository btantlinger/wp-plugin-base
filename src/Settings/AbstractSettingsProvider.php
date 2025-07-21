<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsManager;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

abstract class AbstractSettingsProvider implements SettingsProvider {

	private SettingsManager $settings_manager;

	public function __construct(string $scope)
	{
		$this->settings_manager = new DefaultSettingsManager($scope);
	}

	public function settings(): SettingsManager
	{
		return $this->settings_manager;
	}
}