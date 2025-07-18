<?php

namespace WebMoves\PluginBase\Concerns\Components;

trait HasAdminMenu
{
	use TraitRegistrationHelper;
	
	protected ?string $page_hook = null;
	
	protected function register_has_admin_menu(): void
	{
		$this->ensure_component_registration();
		add_action('admin_menu', [$this, 'add_admin_menu']);
	}

	public function add_admin_menu(): void
	{
		if ($this->is_main_menu()) {
			$this->page_hook = add_menu_page(
				$this->get_page_title(),
				$this->get_menu_title(),
				$this->get_capability(),
				$this->get_menu_slug(),
				[$this, 'render' ],
				$this->get_menu_icon(),
				$this->get_menu_position()
			);
		} else {
			$this->page_hook = add_submenu_page(
				$this->get_parent_slug(),
				$this->get_page_title(),
				$this->get_menu_title(),
				$this->get_capability(),
				$this->get_menu_slug(),
				[$this, 'render' ],
				$this->get_menu_position()
			);
		}

		// Call hook for additional page setup
		$this->on_admin_menu_added();
	}
	
	/**
	 * Wrapper that handles pre-render setup and then renders the page
	 */
	public function render(): void
	{
		// Call optional pre-render hook
		$this->before_render();

		// Render the actual page
		$this->render_admin_page();
	}
	
	/**
	 * Called before rendering the admin page
	 * Override in classes to add page-specific setup like:
	 * - Asset enqueuing
	 * - Help tabs
	 * - Form processing
	 * - Data preparation
	 */
	protected function before_render(): void
	{
		// Default implementation does nothing
		// Override in implementing classes as needed
	}
	
	/**
	 * Get the page hook returned by add_menu_page() or add_submenu_page()
	 */
	public function get_page_hook(): ?string
	{
		return $this->page_hook;
	}
	
	/**
	 * Called after the admin menu has been added
	 * Override in classes to add page-specific hooks
	 */
	protected function on_admin_menu_added(): void
	{
		// Default implementation does nothing
	}

	abstract protected function render_admin_page(): void;
	abstract public function get_page_title(): string;
	abstract public function get_menu_title(): string;
	abstract public function get_menu_slug(): string;

	public function get_capability(): string { return 'manage_options'; }
	public function get_menu_icon(): ?string { return null; }
	public function get_menu_position(): ?int { return null; }
	public function get_parent_slug(): ?string { return 'options-general.php'; }
	protected function is_main_menu(): bool { return empty($this->get_parent_slug()); }

	public function is_submenu_page(): bool { return !empty($this->get_parent_slug()); }
}