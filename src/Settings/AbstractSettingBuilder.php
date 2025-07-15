<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

abstract class AbstractSettingBuilder implements \WebMoves\PluginBase\Contracts\Settings\SettingsBuilderInterface
{
	private string $settingsGroup;
	private string $page;
	private array $providers = [];
	private string $text_domain;

	public function __construct(PluginCoreInterface $core, string $settingsGroup, string $page)
	{
		$this->settingsGroup = $settingsGroup;
		$this->page = $page;
		$this->text_domain = $core->get_text_domain();;
	}

	protected function get_settings_group(): string
	{
		return $this->settingsGroup;
	}

	protected function get_page(): string
	{
		return $this->page;
	}

	protected function get_providers(): array
	{
		return $this->providers;
	}

	public function add_provider(SettingsProvider $provider): void
	{
		$this->providers[] = $provider;
	}

	public function register(): void
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

			$required = !empty($field_config['required']);
			add_settings_field(
				$field_key,
				$field_config['label']  . ($required ? ' <span class="required" style="color:crimson;">*</span>' : ''),
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

		// If there are errors, preserve user input in session
		if (!empty($errors)) {
			foreach ($errors as $field_key => $error_message) {
				add_settings_error(
					$this->settingsGroup,
					$field_key,
					$error_message,
					'error'
				);
			}

			// ✅ Store as flash data - will survive ONE request only
			$this->store_flash_data($provider, $sanitized);

			// Return existing values (no save), but form will show user input
			return $provider->settings()->get_all_scoped_options();
		}

		// ✅ SUCCESS - clear any flash data and save
		$this->clear_flash_data($provider);
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

	/**
	 * Store user input as flash data (survives exactly one request)
	 */
	private function store_flash_data(SettingsProvider $provider, array $input): void
	{
		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}

		$flash_key = $this->get_flash_key($provider);

		// Store with flash flag
		update_user_meta($user_id, $flash_key, [
			'data' => $input,
			'is_flash' => true,
			'timestamp' => time()
		]);
	}

	/**
	 * Get flash data (and mark it for deletion)
	 */
	private function get_flash_data(SettingsProvider $provider): ?array
	{
		$user_id = get_current_user_id();
		if (!$user_id) {
			return null;
		}

		$flash_key = $this->get_flash_key($provider);
		$flash_data = get_user_meta($user_id, $flash_key, true);

		if ($flash_data && isset($flash_data['is_flash']) && $flash_data['is_flash']) {
			// Mark for deletion at end of request
			add_action('wp_footer', function() use ($user_id, $flash_key) {
				delete_user_meta($user_id, $flash_key);
			});
			add_action('admin_footer', function() use ($user_id, $flash_key) {
				delete_user_meta($user_id, $flash_key);
			});

			return $flash_data['data'];
		}

		return null;
	}

	/**
	 * Clear flash data immediately
	 */
	private function clear_flash_data(SettingsProvider $provider): void
	{
		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}

		$flash_key = $this->get_flash_key($provider);
		delete_user_meta($user_id, $flash_key);
	}

	/**
	 * Get flash data key
	 */
	private function get_flash_key(SettingsProvider $provider): string
	{
		return 'flash_settings_' . $this->settingsGroup . '_' . $provider->settings()->get_settings_scope();
	}

	/**
	 * Get the value to display in the form field
	 */
	protected function get_field_display_value(SettingsProvider $provider, string $field_key, $default_value)
	{
		// Check for flash data first
		$flash_data = $this->get_flash_data($provider);

		if ($flash_data !== null && isset($flash_data[$field_key])) {
			return $flash_data[$field_key];
		}

		// Fall back to saved value or default
		return $provider->settings()->get_scoped_option($field_key, $default_value);
	}

	public function get_priority(): int {
		return 10;
	}

	public function can_register(): bool {
		return is_admin() && current_user_can('manage_options');
	}
}