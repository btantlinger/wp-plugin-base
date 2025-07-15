<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait CanHaveSettings
{
	use TraitRegistrationHelper;
	protected function register_can_have_settings(): void
	{
		$this->ensure_component_registration();
		add_action('admin_init', [$this, 'register_settings']);
	}

	public function register_settings(): void
	{
		$settings = $this->get_settings_fields();

		foreach ($settings as $section => $fields) {
			add_settings_section(
				$section,
				$this->get_settings_section_title($section),
				[$this, 'render_settings_section'],
				$this->get_settings_page()
			);

			foreach ($fields as $field) {
				add_settings_field(
					$field['id'],
					$field['title'],
					[$this, 'render_settings_field'],
					$this->get_settings_page(),
					$section,
					$field
				);

				register_setting($this->get_settings_page(), $field['id']);
			}
		}
	}

	abstract protected function get_settings_fields(): array;
	abstract protected function get_settings_page(): string;

	protected function render_settings_field(array $field): void {}

	protected function get_settings_section_title(string $section): string
	{
		return ucwords(str_replace('_', ' ', $section));
	}
	protected function render_settings_section(): void {}
}