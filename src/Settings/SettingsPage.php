<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsBuilderInterface;

class SettingsPage extends AbstractAdminPage
{
	protected SettingsBuilderInterface $builder;

	public function __construct(SettingsBuilderInterface $builder, string $page_title, string $menu_title, ?string $parent_slug, ?string $menu_icon=null, ?int $menu_position = null) {
		parent::__construct($builder->get_settings_page(), $page_title, $menu_title,  $parent_slug, $menu_icon, $menu_position);
		$this->builder = $builder;
	}

	public function get_settings_builder(): SettingsBuilderInterface {
		return $this->builder;
	}

	protected function before_register(): void
	{

/*		if(!$this->core->is_registered($this->builder)) {
			$this->core->register_component($this->builder);
		}*/

		$this->builder->register();
	}

	protected function render_admin_page(): void
	{
		echo '<div class="wrap">';
		echo '<h1>' . esc_html(get_admin_page_title()) . "</h1>";
		$this->builder->render_form();
		echo '</div>';
	}
}