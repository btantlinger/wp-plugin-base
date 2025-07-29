<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

class DefaultSettingsProcessor implements SettingsProcessor
{
	private string $text_domain;

	public function __construct(string $text_domain)
	{
		$this->text_domain = $text_domain;
	}

	public function process(array $input, SettingsProvider $provider): array
	{
		$config = $provider->get_settings_configuration();
		$fields = $config['fields'];

		$errors = [];
		$sanitized = [];

		foreach ($fields as $field_key => $field_config) {
			$value = $input[$field_key] ?? '';

			// Sanitize
			$sanitized_value = $this->sanitize_field_value($value, $field_config);

			// Check required fields
			if ((!empty($field_config['required'])) && empty($sanitized_value)) {
				$req_validator = FieldValidators::required($this->text_domain);
				if(empty($field_config['validate_callback'])) {
					$field_config['validate_callback'] = $req_validator;
				} else {
					$validators = FieldValidators::combine([$field_config['validate_callback'], $req_validator]);
					$field_config['validate_callback'] = $validators;
				}
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

		if (!empty($errors)) {
			return ['errors' => $errors];
		}

		// Save the sanitized data
		foreach ($sanitized as $field_key => $value) {
			$provider->settings()->set_scoped_option($field_key, $value);
		}

		return ['success' => true, 'data' => $sanitized];
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
}