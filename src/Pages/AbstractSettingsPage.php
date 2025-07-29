<?php

namespace WebMoves\PluginBase\Pages;

use WebMoves\PluginBase\Contracts\Forms\SettingsForm;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;

abstract class AbstractSettingsPage extends AbstractAdminPage
{
	protected SettingsForm $form;

	public function __construct(PluginCore $core, SettingsForm $form, ?string $parent_slug = null) {
		parent::__construct($core, $form->get_settings_page(), $parent_slug);
		$this->form = $form;
	}

	public function get_settings_form(): SettingsForm {
		return $this->form;
	}

	protected function before_register(): void
	{
		if(!$this->core->get_component_manager()->contains($this->form)) {
			$this->core->get_component_manager()->add($this->form);
		}
	}

	protected function render_admin_page(): void
	{
		echo '<div class="wrap">';
		echo '<h1>' . esc_html(get_admin_page_title()) . "</h1>";
		$this->form->render_form();
		echo '</div>';
	}
}