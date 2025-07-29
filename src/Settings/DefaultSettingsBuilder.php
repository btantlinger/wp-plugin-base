<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;
use WebMoves\PluginBase\Settings\Traits\DefaultFieldRenderer;

class DefaultSettingsBuilder extends AbstractSettingBuilder
{
	use DefaultFieldRenderer;

	private SettingsProcessor $processor;

	public function __construct(PluginCore $core, string $settingsGroup, string $page, array $settings_providers = [])
	{
		parent::__construct($core, $settingsGroup, $page, $settings_providers);
		$this->processor = $core->get(SettingsProcessor::class);
	}

	public function register(): void {
		parent::register();
		add_action('current_screen', [$this, 'handle_settings_success']);
	}

	public function handle_settings_success(): void
	{
		// Only add success message if we processed a submission and have no errors
		$settings_updated = isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true';
		if ($settings_updated && $this->is_current_settings_page() && !$this->flash->has_errors()) {
			$this->flash->add_success('Settings saved successfully!');
		}
	}

	private function is_current_settings_page(): bool
	{
		$screen = get_current_screen();
		if (!$screen) {
			return false;
		}

		// Check if the current page matches this builder's page
		return strpos($screen->id, $this->get_settings_page()) !== false;
	}

	public function render_settings_field( array $args ): void
	{
		$this->renderer_default_field($args);
	}

	public function render_form(): void
	{
		$this->render_default_page();
	}

	protected function render_default_page(): void
	{
		echo '<form method="post" action="options.php">';
		settings_fields($this->get_settings_group());
		do_settings_sections($this->get_settings_page());
		submit_button();
		echo '</form>';
	}

	protected function register_provider_configuration(SettingsProvider $provider): void
	{
		$config = $provider->get_settings_configuration();
		$section = $config['section'];
		$fields = $config['fields'];

		// Get the option name from the settings manager
		$option_name = $provider->settings()->get_settings_scope();

		// Register single setting for the entire group
		register_setting(
			$this->get_settings_group(),
			$option_name,
			[
				'type' => 'array',
				'sanitize_callback' => function($input) use ($provider) {
					if (!$input) {
						$input = [];
					}
					return $this->validate_and_sanitize_group($input, $provider);
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
			$this->get_settings_page()
		);

		// Register fields for display
		foreach ($fields as $field_key => $field_config) {
			$required = !empty($field_config['required']);
			add_settings_field(
				$field_key,
				$field_config['label']  . ($required ? ' <span class="required" style="color:crimson;">*</span>' : ''),
				[$this, 'render_settings_field'],
				$this->get_settings_page(),
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

	protected function validate_and_sanitize_group(array $input, SettingsProvider $provider): array
	{
		$result = $this->processor->process($input, $provider);

		if (isset($result['errors'])) {
			// Add errors as notices
			$this->flash->add_field_errors($result['errors']);

			// Store form data for redisplay
			$this->flash->set_form_data($provider->settings()->get_settings_scope(), $input);

			return $provider->settings()->get_all_scoped_options();
		}

		// Success
		$this->flash->clear('form_' . $provider->settings()->get_settings_scope());

		return $result['data'];
	}
}