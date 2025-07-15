<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait CanHaveAdminMenu
{
	use TraitRegistrationHelper;
	protected function register_can_have_admin_menu(): void
	{
		$this->ensure_component_registration();

		add_action('admin_menu', [$this, 'add_admin_menu']);
	}

	public function add_admin_menu(): void
	{
		if ($this->is_main_menu()) {
			add_menu_page(
				$this->get_page_title(),
				$this->get_menu_title(),
				$this->get_capability(),
				$this->get_menu_slug(),
				[$this, 'render_admin_page'],
				$this->get_menu_icon(),
				$this->get_menu_position()
			);
		} else {
			add_submenu_page(
				$this->get_parent_slug(),
				$this->get_page_title(),
				$this->get_menu_title(),
				$this->get_capability(),
				$this->get_menu_slug(),
				[$this, 'render_admin_page']
			);
		}
	}

	abstract protected function render_admin_page(): void;
	abstract protected function get_page_title(): string;
	abstract protected function get_menu_title(): string;
	abstract protected function get_menu_slug(): string;

	protected function get_capability(): string { return 'manage_options'; }
	protected function get_menu_icon(): string { return 'dashicons-admin-generic'; }
	protected function get_menu_position(): ?int { return null; }
	protected function is_main_menu(): bool { return false; }
	protected function get_parent_slug(): string { return 'options-general.php'; }
}