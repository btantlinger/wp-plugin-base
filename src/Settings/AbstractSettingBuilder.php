<?php

namespace WebMoves\PluginBase\Settings;

use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

abstract class AbstractSettingBuilder implements \WebMoves\PluginBase\Contracts\Settings\SettingsBuilderInterface
{
	private string $settingsGroup;
	private string $page;
	private array $providers = [];
	private string $text_domain;

	protected LoggerInterface $logger;

	protected FlashData $flash;


	public function __construct(PluginCoreInterface $core, string $settingsGroup, string $page)
	{
		$this->settingsGroup = $settingsGroup;
		$this->page = $page;
		$this->text_domain = $core->get_text_domain();
		$this->logger = $core->get_logger('app');
		$this->flash = new FlashData($page);
	}


	public function get_settings_group(): string
	{
		return $this->settingsGroup;
	}

	public function get_settings_page(): string
	{
		return $this->page;
	}

	public function get_providers(): array
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
		add_action('current_screen', [$this, 'handle_settings_success']);
	}


	public function register_settings(): void
	{
		foreach ($this->providers as $provider) {
			$this->register_provider_configuration($provider);
		}
	}

	public function handle_settings_success(): void
	{
		// Only add success message if we processed a submission and have no errors
		$settings_updated = isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true';
		if ($settings_updated && $this->is_current_settings_page() && !$this->flash->has_errors()) {
			$this->flash->add_success('Settings saved successfully!');
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
			if ((!empty($field_config['required'])) && empty($sanitized_value)) {

				$req_validator = FieldValidators::required($this->get_text_domain());
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
			// Add errors as notices
			$this->flash->add_field_errors($errors);

			// Store form data for redisplay
			$this->flash->set_form_data($provider->settings()->get_settings_scope(), $sanitized);

			return $provider->settings()->get_all_scoped_options();
		}
			// Success
		$this->flash->clear( 'form_' . $provider->settings()->get_settings_scope() );

		return $sanitized;
	}


	private function is_current_settings_page(): bool
	{
		$screen = get_current_screen();
		if (!$screen) {
			return false;
		}

		// Check if the current page matches this builder's page
		return strpos($screen->id, $this->page) !== false;
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
	 * Get the value to display in the form field
	 */
	protected function get_field_display_value(SettingsProvider $provider, string $field_key, $default_value)
	{
		// Check for flash data first (from validation errors)
		$flash_value = $this->get_flash_value($provider, $field_key, null);
		if ($flash_value !== null) {
			return $flash_value;
		}

		// Fall back to saved value or default
		return $provider->settings()->get_scoped_option($field_key, $default_value);
	}

	protected function get_flash_value(SettingsProvider $provider, string $field_key, $default = null)
	{
		$form_key = $provider->settings()->get_settings_scope();
		$flash_data = $this->flash->get_form_data($form_key);

		// If no flash data exists, return the default (null)
		if (empty($flash_data)) {
			return $default;
		}

		// Return the specific field value or default
		return $flash_data[$field_key] ?? $default;
	}

	public function get_priority(): int {
		return 10;
	}

	public function get_text_domain(): string {
		return $this->text_domain;
	}

	public function can_register(): bool {
		return is_admin() && current_user_can('manage_options');
	}
}