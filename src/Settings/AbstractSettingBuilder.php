<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

abstract class AbstractSettingBuilder implements \WebMoves\PluginBase\Contracts\Settings\SettingsBuilderInterface
{
	private string $settingsGroup;
	private string $page;
	private array $providers = [];
	private string $text_domain;

	public function __construct(string $settingsGroup, string $page, string $textDomain = 'wm-plugin-base')
	{
		$this->settingsGroup = $settingsGroup;
		$this->page = $page;
		$this->text_domain = $textDomain;
	}

	protected function get_settings_group(): string
	{
		return $this->settingsGroup;
	}

	protected function get_page(): string
	{
		return $this->page;
	}

	protected function get_text_domain(): string
	{
		return $this->text_domain;
	}

	protected function get_providers(): array
	{
		return $this->providers;
	}

	public function add_provider(SettingsProvider $provider): void
	{
		$this->providers[] = $provider;
	}

	public function init(): void
	{
		add_action('admin_init', [$this, 'register_settings']);
	}

	public function register_settings(): void
	{
		foreach ($this->providers as $provider) {
			$this->register_provider_configuration($provider);
		}
	}

	private function register_provider_configuration(SettingsProvider $provider): void
	{
		$config = $provider->get_settings_configuration();
		$section = $config['section'];
		$fields = $config['fields'];

		// Get the option name from the settings manager
		$option_name = $provider->settings()->get_settings_scope();

		// Register single setting for the entire group
		register_setting(
			$this->settingsGroup,
			$option_name,
			[
				'type' => 'array',
				'sanitize_callback' => function($input) use ($provider, $fields) {
					return $this->validate_and_sanitize_group($input, $provider, $fields);
				}
			]
		);

		// Register section
		add_settings_section(
			$section['id'],
			$section['title'],
			function() use ($section) {
				if (!empty($section['description'])) {
					echo '<p>' . esc_html($section['description']) . '</p>';
				}
			},
			$this->page
		);

		// Register fields for display
		foreach ($fields as $field_key => $field_config) {
			add_settings_field(
				$field_key,
				$field_config['label'],
				[$this, 'render_settings_field'],
				$this->page,
				$section['id'],
				[
					'field' => $field_config,
					'provider' => $provider,
					'field_key' => $field_key,
					'field_name' => $option_name . '[' . $field_key . ']'
				]
			);
		}
	}

	private function validate_and_sanitize_group(array $input, SettingsProvider $provider, array $fields): array
	{
		$errors = [];
		$sanitized = [];

		foreach ($fields as $field_key => $field_config) {
			$value = $input[$field_key] ?? '';

			// Sanitize
			$sanitized_value = $this->sanitize_field_value($value, $field_config);

			// Check required fields
			if (!empty($field_config['required']) && empty($sanitized_value)) {
				$errors[$field_key] = sprintf(
					__('%s is required.', $this->text_domain),
					$field_config['label']
				);
			}

			// Custom validation
			if (isset($field_config['validate_callback'])) {
				$result = call_user_func($field_config['validate_callback'], $sanitized_value, $field_config);
				if (is_wp_error($result)) {
					$errors[$field_key] = $result->get_error_message();
				}
			}

			$sanitized[$field_key] = $sanitized_value;
		}

		// If there are errors, add them to settings errors and return original values
		if (!empty($errors)) {
			foreach ($errors as $field_key => $error_message) {
				add_settings_error(
					$this->settingsGroup,
					$field_key,
					$error_message,
					'error'
				);
			}

			// Return original values from database (no changes saved!)
			return $provider->settings()->get_all_scoped_options();
		}

		return $sanitized;
	}

	private function sanitize_field_value($value, array $field_config)
	{
		if (isset($field_config['sanitize_callback'])) {
			return call_user_func($field_config['sanitize_callback'], $value);
		}

		switch ($field_config['type']) {
			case 'text':
			case 'email':
			case 'url':
				return sanitize_text_field($value);
			case 'textarea':
				return sanitize_textarea_field($value);
			case 'number':
				return intval($value);
			case 'checkbox':
				return !empty($value);
			default:
				return sanitize_text_field($value);
		}
	}


	/*

	public function render_settings_field(array $args): void
	{
		$field = $args['field'];
		$provider = $args['provider'];
		$field_key = $args['field_key'];
		$field_name = $args['field_name'];

		$value = $provider->settings()->get_scoped_option($field_key, $field['default'] ?? null);

		// Build attributes
		$attributes = $field['attributes'] ?? [];
		if (!empty($field['required'])) {
			$attributes['required'] = 'required';
		}

		$attribute_string = $this->build_attribute_string($attributes);

		switch ($field['type']) {
			case 'text':
			case 'email':
			case 'url':
			case 'number':
				$this->render_input_field($field, $field_name, $value, $attribute_string);
				break;

			case 'textarea':
				$this->render_textarea_field($field_name, $value, $attribute_string);
				break;

			case 'checkbox':
				$this->render_checkbox_field($field_name, $value, $attribute_string);
				break;

			case 'select':
				$this->render_select_field($field, $field_name, $value, $attribute_string);
				break;
		}

		if (!empty($field['description'])) {
			echo '<p class="description">' . esc_html($field['description']) . '</p>';
		}
	}
	*/


}