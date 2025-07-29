<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;
use WebMoves\PluginBase\Settings\Traits\DefaultFieldRenderer;
use WebMoves\PluginBase\Contracts\Controllers\Controller;
use WebMoves\PluginBase\Controllers\SettingsFormController;

class FormControllerSettingsBuilder extends AbstractSettingBuilder
{
	use DefaultFieldRenderer;

	private SettingsFormController $controller;

	public function __construct(PluginCore $core, string $settingsGroup, string $page, array $settings_providers = [])
	{
		parent::__construct($core, $settingsGroup, $page, $settings_providers);
		$processor = $core->get(SettingsProcessor::class);

		// Create the form controller for handling submissions
		$this->controller = new SettingsFormController(
			$core->get_metadata(),
			$this->flash,
			'save_settings_' . $settingsGroup,
			$this->get_providers(),
			$processor
		);
	}

	public function can_register(): bool
	{
		return is_admin() && current_user_can('manage_options');
	}

	public function register(): void
	{
		parent::register();

		// Register the controller to handle form submissions
		$this->controller->register();
	}

	public function render_settings_field(array $args): void
	{
		$this->renderer_default_field($args);
	}

	public function render_form(): void
	{
		$this->render_controller_form();
	}

	protected function register_provider_configuration(SettingsProvider $provider): void
	{
		$config = $provider->get_settings_configuration();
		$section = $config['section'];
		$fields = $config['fields'];

		// Only register sections and fields for display - NO register_setting()
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

		// Register fields for display only
		foreach ($fields as $field_key => $field_config) {
			$option_name = $provider->settings()->get_settings_scope();
			$required = !empty($field_config['required']);

			add_settings_field(
				$field_key,
				$field_config['label'] . ($required ? ' <span class="required" style="color:crimson;">*</span>' : ''),
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

	protected function render_controller_form(): void
	{
		// Use our controller instead of options.php
		$action_url = $this->controller->create_action_url();

		echo '<form method="post" action="' . esc_url($action_url) . '">';

		echo $this->controller->get_action_fields();

		// Render sections (same as WordPress Settings API)
		do_settings_sections($this->get_settings_page());

		submit_button();
		echo '</form>';
	}

	/**
	 * Get the controller instance (useful for testing or advanced usage)
	 */
	public function get_controller(): Controller
	{
		return $this->controller;
	}
}