<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;

class SettingsRestRoute extends AbstractRestRoute
{
	private array $settings_providers;
	private SettingsProcessor $processor;
	private string $text_domain;

	public function __construct(
		array $settings_providers,
		SettingsProcessor $processor,
		string $text_domain,
		string $namespace = 'your-plugin/v1'
	) {
		$this->settings_providers = $settings_providers;
		$this->processor = $processor;
		$this->text_domain = $text_domain;

		parent::__construct(
			route: 'settings',
			namespace: $namespace,
			args: $this->get_endpoint_args(),
			methods: ['GET', 'POST']
		);
	}

	public function handle_rest_request(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		try {
			$method = $request->get_method();

			switch ($method) {
				case 'GET':
					return $this->handle_get_request($request);

				case 'POST':
					return $this->handle_post_request($request);

				default:
					return new \WP_Error(
						'invalid_method',
						__('Method not allowed', $this->text_domain),
						['status' => 405]
					);
			}
		} catch (\Exception $e) {
			return new \WP_Error(
				'internal_error',
				__('An error occurred while processing your request.', $this->text_domain),
				['status' => 500]
			);
		}
	}

	private function handle_get_request(\WP_REST_Request $request): \WP_REST_Response
	{
		$all_settings = [];
		$all_schemas = [];

		foreach ($this->settings_providers as $provider) {
			$scope = $provider->settings()->get_settings_scope();
			$settings = $provider->settings()->get_all_scoped_options();
			
			// Group settings by provider scope
			$all_settings[$scope] = $settings;
			$all_schemas[$scope] = $this->get_provider_schema($provider);
		}

		return new \WP_REST_Response([
			'success' => true,
			'data' => $all_settings,
			'schema' => $all_schemas
		], 200);
	}

	private function handle_post_request(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
	{
		$input = $request->get_json_params() ?: $request->get_params();
		$results = [];
		$all_errors = [];

		// Process each provider's settings
		foreach ($this->settings_providers as $provider) {
			$scope = $provider->settings()->get_settings_scope();
			
			// Extract this provider's data from the input
			$provider_input = $input[$scope] ?? [];
			
			if (empty($provider_input)) {
				continue; // Skip if no data for this provider
			}

			// Process the settings using your existing processor
			$result = $this->processor->process($provider_input, $provider);

			if (isset($result['errors'])) {
				// Namespace the errors by provider scope
				foreach ($result['errors'] as $field => $error) {
					$all_errors["{$scope}.{$field}"] = $error;
				}
			} else {
				// Save the validated data
				foreach ($result['data'] as $key => $value) {
					$provider->settings()->set_scoped_option($key, $value);
				}
				$results[$scope] = $result['data'];
			}
		}

		// If there were any errors, return them
		if (!empty($all_errors)) {
			return new \WP_Error(
				'validation_failed',
				__('Validation failed', $this->text_domain),
				[
					'status' => 400,
					'errors' => $all_errors
				]
			);
		}

		return new \WP_REST_Response([
			'success' => true,
			'message' => __('Settings updated successfully', $this->text_domain),
			'data' => $results
		], 200);
	}

	/**
	 * Combine endpoint args from all providers
	 */
	private function get_endpoint_args(): array
	{
		$args = [];

		foreach ($this->settings_providers as $provider) {
			$scope = $provider->settings()->get_settings_scope();
			$config = $provider->get_settings_configuration();

			// Create a nested structure for each provider's fields
			$provider_args = [];
			foreach ($config['fields'] as $field_key => $field_config) {
				$provider_args[$field_key] = [
					'required' => !empty($field_config['required']),
					'type' => $this->map_field_type($field_config['type']),
					'description' => $field_config['description'] ?? '',
					'sanitize_callback' => $this->get_sanitize_callback($field_config['type']),
					'validate_callback' => $this->create_rest_validator($field_config),
				];

				// Add enum validation for select fields
				if ($field_config['type'] === 'select' && !empty($field_config['options'])) {
					$provider_args[$field_key]['enum'] = array_keys($field_config['options']);
				}
			}

			// Nest under the provider scope
			$args[$scope] = [
				'type' => 'object',
				'properties' => $provider_args,
				'description' => $config['section']['description'] ?? "Settings for {$scope}"
			];
		}

		return $args;
	}

	/**
	 * Create REST API validator using the field's existing validator
	 */
	private function create_rest_validator(array $field_config): callable
	{
		return function($value, $request, $param) use ($field_config) {
			// If the field has a custom validator, use it
			if (!empty($field_config['validator']) && is_callable($field_config['validator'])) {
				$validator = $field_config['validator'];
				$result = call_user_func($validator, $value, $field_config);

				// If validator returns WP_Error, convert to REST format
				if (is_wp_error($result)) {
					return new \WP_Error(
						$result->get_error_code(),
						$result->get_error_message(),
						['status' => 400]
					);
				}

				// If validator returns false or error string
				if ($result !== true) {
					$message = is_string($result) ? $result : sprintf(
						__('Invalid value for %s', $this->text_domain),
						$field_config['label'] ?? $param
					);

					return new \WP_Error(
						'validation_failed',
						$message,
						['status' => 400]
					);
				}
			}

			// Basic required field validation if no custom validator
			if (!empty($field_config['required']) && empty($value)) {
				return new \WP_Error(
					'required_field',
					sprintf(
						__('The %s field is required.', $this->text_domain),
						$field_config['label'] ?? $param
					),
					['status' => 400]
				);
			}

			return true;
		};
	}

	private function map_field_type(string $field_type): string
	{
		return match($field_type) {
			'number' => 'integer',
			'checkbox' => 'boolean',
			'textarea', 'text', 'email', 'url' => 'string',
			'select' => 'string',
			default => 'string'
		};
	}

	private function get_sanitize_callback(string $field_type): callable
	{
		return match($field_type) {
			'email' => 'sanitize_email',
			'url' => 'esc_url_raw',
			'number' => 'absint',
			'checkbox' => 'rest_sanitize_boolean',
			default => 'sanitize_text_field'
		};
	}

	/**
	 * Get schema for a single provider
	 */
	private function get_provider_schema(SettingsProvider $provider): array
	{
		$config = $provider->get_settings_configuration();
		$schema = [];

		foreach ($config['fields'] as $field_key => $field_config) {
			$schema[$field_key] = [
				'type' => $this->map_field_type($field_config['type']),
				'description' => $field_config['description'] ?? '',
				'required' => !empty($field_config['required']),
			];

			if ($field_config['type'] === 'select' && !empty($field_config['options'])) {
				$schema[$field_key]['enum'] = array_keys($field_config['options']);
				$schema[$field_key]['options'] = $field_config['options'];
			}
		}

		return $schema;
	}

	protected function can_execute_action(): bool
	{
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			return current_user_can('read');
		}

		return current_user_can('manage_options');
	}
}