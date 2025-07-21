<?php

namespace WebMoves\PluginBase\Examples\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsManager;
use WebMoves\PluginBase\Settings\AbstractSettingsProvider;
use WebMoves\PluginBase\Settings\DefaultSettingsManager;

class DemoSettingsProvider extends AbstractSettingsProvider
{
	public function get_settings_configuration(): array
	{
		return [
			'section' => [
				'id' => 'demo_settings_section',
				'title' => __('Demo Settings', 'test-plugin'),
				'description' => __('Configure demo plugin settings.', 'test-plugin'),
			],
			'fields' => [
				'demo_enabled' => [
					'label' => __('Enable Demo Mode', 'test-plugin'),
					'type' => 'checkbox',
					'description' => __('Enable demo mode for testing.', 'test-plugin'),
					'default' => false,
				],
				'demo_message' => [
					'label' => __('Demo Message', 'test-plugin'),
					'type' => 'text',
					'description' => __('Message to display in demo mode.', 'test-plugin'),
					'default' => 'Hello, World!',
					'required' => true,
				],
				'demo_level' => [
					'label' => __('Demo Level', 'test-plugin'),
					'type' => 'select',
					'description' => __('Select demo level.', 'test-plugin'),
					'options' => [
						'basic' => __('Basic', 'test-plugin'),
						'advanced' => __('Advanced', 'test-plugin'),
						'expert' => __('Expert', 'test-plugin'),
					],
					'default' => 'basic',
				],
				'demo_description' => [
					'label' => __('Demo Description', 'test-plugin'),
					'type' => 'textarea',
					'description' => __('Detailed description of the demo.', 'test-plugin'),
					'default' => 'This is a demo plugin for testing the settings framework.',
				],
			]
		];
	}
}