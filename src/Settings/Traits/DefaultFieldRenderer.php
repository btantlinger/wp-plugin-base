<?php

namespace WebMoves\PluginBase\Settings\Traits;

trait DefaultFieldRenderer
{
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
}