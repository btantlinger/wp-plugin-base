<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\SettingsManager;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

abstract class AbstractSettingsProvider implements SettingsProvider {

	private SettingsManager $settings_manager;

	private PluginMetadata $metadata;


	public function __construct(SettingsManager $settings_manager, PluginMetadata $metadata)
	{
		$this->settings_manager = $settings_manager;
		$this->metadata = $metadata;
	}

	public function settings(): SettingsManager
	{
		return $this->settings_manager;
	}

	public function get_plugin_metadata(): PluginMetadata
	{
		return $this->metadata;
	}
}