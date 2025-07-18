<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerInterface;
use WebMoves\PluginBase\Settings\SettingsManager;

class ApiSettingsProvider implements SettingsProvider
{
	private SettingsManagerInterface $settings_manager;

	public function __construct(string $scope)
	{
		$this->settings_manager = new SettingsManager($scope);
	}

	public function settings(): SettingsManagerInterface
	{
		return $this->settings_manager;
	}

	public function get_settings_configuration(): array
	{
		return [
			'section' => [
				'id' => 'api_settings_section',
				'title' => __('API Settings', 'test-plugin'),
				'description' => __('Configure API connection settings.', 'test-plugin'),
			],
			'fields' => [
				'papi_url' => [
					'label' => __('API URL', 'test-plugin'),
					'type' => 'url',
					'description' => __('The API endpoint URL.', 'test-plugin'),
					'default' => 'https://api.example.com',
					'required' => true,
				],
				'papi_key' => [
					'label' => __('API Key', 'test-plugin'),
					'type' => 'text',
					'description' => __('Your API key for authentication.', 'test-plugin'),
					'default' => '',
					'required' => true,
					'attributes' => ['placeholder' => 'Enter your API key'],
				],
				'papi_timeout' => [
					'label' => __('API Timeout', 'test-plugin'),
					'type' => 'number',
					'description' => __('Request timeout in seconds.', 'test-plugin'),
					'default' => 30,
					'attributes' => ['min' => 1, 'max' => 300],
					'sanitize_callback' => 'absint',
				],
			]
		];
	}
}