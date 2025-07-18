<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsManagerInterface;
use WebMoves\PluginBase\Contracts\Settings\SettingsProviderInterface;

abstract class AbstractSettingsProvider implements SettingsProviderInterface {

	private SettingsManagerInterface $settings_manager;

	public function __construct(string $scope)
	{
		$this->settings_manager = new SettingsManager($scope);
	}

	public function settings(): SettingsManagerInterface
	{
		return $this->settings_manager;
	}
}