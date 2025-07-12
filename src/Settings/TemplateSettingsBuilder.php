<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Templates\TemplateRendererInterface;

class TemplateSettingsBuilder extends AbstractSettingBuilder
{
	private TemplateRendererInterface $template_renderer;


	public function __construct(string $settingsGroup, string $page, TemplateRendererInterface $renderer, string $textDomain = 'wm-plugin-base') {
		parent::__construct($settingsGroup, $page, $textDomain);
		$this->template_renderer = $renderer;
	}

	protected function get_field_template_name(string $field_type): string
	{
		return 'settings/fields/' . $field_type;
	}

	protected function get_page_template_name(): string
	{
		return 'settings/' . $this->get_page();
	}


	public function render_settings_field( array $args ): void {
		$tmpl = $this->get_field_template_name($args['field']['type']);
		$this->template_renderer->display($tmpl, $args);
	}

	public function render_settings_page(): void {
		$this->template_renderer->display($this->get_page_template_name(), [
			'providers' => $this->get_providers(),
			'settings_group' => $this->get_settings_group(),
			'page' => $this->get_page()
		]);
	}
}