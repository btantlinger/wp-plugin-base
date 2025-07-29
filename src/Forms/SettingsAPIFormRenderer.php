<?php

namespace WebMoves\PluginBase\Forms;

use WebMoves\PluginBase\Contracts\Forms\FormRenderer;
use WebMoves\PluginBase\Contracts\Forms\FormSubmissionHandler;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;

class SettingsAPIFormRenderer implements FormRenderer {

	private FlashData $flash;

	public function __construct(FlashData $flash)
	{
		$this->flash = $flash;
	}

	/**
	 * @inheritDoc
	 */
	public function render_form( FormSubmissionHandler $handler, string $page ): void {

		$action = $handler->get_form_action();
		echo '<form method="post" action="' . $action . '">';
		echo $handler->get_action_fields();
		do_settings_sections($page);
		submit_button();
		echo '</form>';
	}

	/**
	 * @inheritDoc
	 */
	public function render_field( array $args ): void {
		$this->renderer_default_field($args);
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

	/**
	 * Default field renderer - outputs standard HTML form fields
	 */
	protected function renderer_default_field(array $args): void
	{
		$field = $args['field'];
		$provider = $args['provider'];
		$field_key = $args['field_key'];
		$field_name = $args['field_name'];

		$value = $this->get_field_display_value($provider, $field_key, $field['default'] ?? '');

		// Build attributes
		$attributes = $field['attributes'] ?? [];
		unset($attributes['id']); // specified in config
		unset($attributes['name']); // this is the id
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
			if ($field['type'] == 'checkbox') {
				echo '<label for="' . $field_name . '" class="description">' . esc_html($field['description']) . '</label>';
			} else {
				echo '<p class="description">' . esc_html($field['description']) . '</p>';
			}
		}
	}

	protected function build_attribute_string(array $attributes): string
	{
		$attribute_string = '';
		foreach ($attributes as $attr => $attr_value) {
			if ($attr_value === true || $attr_value === 'required') {
				$attribute_string .= ' ' . esc_attr($attr);
			} else {
				$attribute_string .= ' ' . esc_attr($attr) . '="' . esc_attr($attr_value) . '"';
			}
		}
		return $attribute_string;
	}

	protected function render_input_field(array $field, string $field_name, $value, string $attribute_string): void
	{
		echo '<input type="' . esc_attr($field['type']) . '" ' .
		     'id="' . esc_attr($field_name) . '" ' .
		     'name="' . esc_attr($field_name) . '" ' .
		     'value="' . esc_attr($value) . '" ' .
		     'class="regular-text" ' .
		     $attribute_string . ' />';
	}

	protected function render_textarea_field(string $field_name, $value, string $attribute_string): void
	{
		echo '<textarea ' .
		     'id="' . esc_attr($field_name) . '" ' .
		     'name="' . esc_attr($field_name) . '" ' .
		     'class="large-text" ' .
		     'rows="5" ' .
		     $attribute_string . '>' . esc_textarea($value) . '</textarea>';
	}

	protected function render_checkbox_field(string $field_name, $value, string $attribute_string): void
	{
		echo '<input type="checkbox" ' .
		     'id="' . esc_attr($field_name) . '" ' .
		     'name="' . esc_attr($field_name) . '" ' .
		     'value="1" ' .
		     checked($value, true, false) . ' ' .
		     $attribute_string . ' />';
	}

	protected function render_select_field(array $field, string $field_name, $value, string $attribute_string): void
	{
		echo '<select ' .
		     'id="' . esc_attr($field_name) . '" ' .
		     'name="' . esc_attr($field_name) . '" ' .
		     $attribute_string . '>';

		foreach ($field['options'] ?? [] as $option_value => $option_label) {
			echo '<option value="' . esc_attr($option_value) . '" ' .
			     selected($value, $option_value, false) . '>' .
			     esc_html($option_label) . '</option>';
		}
		echo '</select>';
	}


	/**
	 * @inheritDoc
	 */
	public function register_display_elements( array $providers, string $page ): void {
		foreach($providers as $provider) {
			$this->register_provider_configuration($provider, $page);
		}
	}

	protected function register_provider_configuration(SettingsProvider $provider, string $page): void
	{
		$config = $provider->get_settings_configuration();
		$section = $config['section'];
		$fields = $config['fields'];

		// Get the option name from the settings manager
		$option_name = $provider->settings()->get_settings_scope();

		// Register section
		add_settings_section(
			$section['id'],
			$section['title'],
			function() use ($section) {
				if (!empty($section['description'])) {
					echo '<p>' . esc_html($section['description']) . '</p>';
				}
			},
			$page
		);

		// Register fields for display
		foreach ($fields as $field_key => $field_config) {
			$required = !empty($field_config['required']);
			add_settings_field(
				$field_key,
				$field_config['label']  . ($required ? ' <span class="required" style="color:crimson;">*</span>' : ''),
				[$this, 'render_field'],
				$page,
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
}