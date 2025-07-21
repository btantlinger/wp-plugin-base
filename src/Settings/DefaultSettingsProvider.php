<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Settings\AbstractSettingsProvider;

class DefaultSettingsProvider extends AbstractSettingsProvider {

	private array $settings_configuration;
	public function __construct(string $scope, array $settings_configuration) {
		parent::__construct($scope);
		$this->settings_configuration = $settings_configuration;
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings_configuration(): array {
		return $this->settings_configuration;
	}
}