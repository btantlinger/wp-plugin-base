<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Templates\TemplateRenderer;

class TemplateSettingsBuilder extends AbstractSettingBuilder
{
	private TemplateRenderer $template_renderer;


	public function __construct(PluginCore $core, string $settingsGroup, string $page, TemplateRenderer $renderer, array $settings_providers = []) {
		parent::__construct($core, $settingsGroup, $page, $settings_providers);
		$this->template_renderer = $renderer;
	}

	protected function get_field_template_name(string $field_type): string
	{
		return 'settings/fields/' . $field_type;
	}

	protected function get_form_template_name(): string
	{
		return 'settings/' . $this->get_settings_group();
	}


	public function render_settings_field( array $args ): void {

		$args['value'] = $this->get_field_display_value($args['provider'], $args['field_key'], $args['field']['default'] ?? '');
		$tmpl = $this->get_field_template_name($args['field']['type']);
		$this->template_renderer->display($tmpl, $args);
	}

	public function render_form(): void {
		$this->template_renderer->display($this->get_form_template_name(), [
			'providers' => $this->get_providers(),
			'settings_group' => $this->get_settings_group(),
			'page' => $this->get_settings_page()
		]);
	}
}