<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsBuilder;

abstract class AbstractSettingsPage extends AbstractAdminPage
{
	protected SettingsBuilder $builder;

	public function __construct(SettingsBuilder $builder, ?string $parent_slug = null) {
		parent::__construct($builder->get_settings_page(), $parent_slug);
		$this->builder = $builder;
	}

	public function get_settings_builder(): SettingsBuilder {
		return $this->builder;
	}

	protected function before_register(): void
	{
		$core = $this->builder->get_plugin_core();
		if(!$core->get_component_manager()->contains($this->builder)) {
			$core->get_component_manager()->add($this->builder);
		}
	}

	protected function render_admin_page(): void
	{
		echo '<div class="wrap">';
		echo '<h1>' . esc_html(get_admin_page_title()) . "</h1>";
		$this->builder->render_form();
		echo '</div>';
	}
}