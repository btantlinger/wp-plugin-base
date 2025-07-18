<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Concerns\Components\HasAdminMenu;
use WebMoves\PluginBase\Contracts\Components\ComponentInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;

abstract class AbstractAdminPage implements ComponentInterface  {

	use ComponentRegistration;
	use HasAdminMenu;

	protected string $page_title;

	protected string $page_slug;

	protected string $menu_title;

	protected ?string $parent_slug = null;

	protected string $capability = 'manage_options';

	protected ?string $menu_icon = null;

	protected int $priority = 10;

	protected ?int $menu_position = null;


	/**
	 * @param string $menu_slug
	 * @param string $page_title
	 * @param string $menu_title
	 * @param string $menu_icon
	 */
	public function __construct(string $menu_slug, string $page_title, string $menu_title, ?string $parent_slug=null, ?string $menu_icon=null, ?int $menu_position = null) {
		$this->page_slug  = $menu_slug;
		$this->page_title = $page_title;
		$this->menu_title = $menu_title;
		$this->menu_position = $menu_position;
		$this->menu_icon = $menu_icon;
		$this->parent_slug = $parent_slug;
	}


	abstract protected function render_admin_page(): void;

	public function get_page_title(): string
	{
		return $this->page_title;
	}

	public function set_page_title(string $page_title): void
	{
		$this->page_title = $page_title;
	}


	public function get_menu_title(): string
	{
		return $this->menu_title;
	}

	public function set_menu_title(string $menu_title): void
	{
		$this->menu_title = $menu_title;
	}



	public function get_menu_slug(): string
	{
		return $this->page_slug;
	}


	public function set_menu_slug(string $menu_slug): void
	{
		$this->page_slug = $menu_slug;
	}




	public function get_capability(): string
	{
		return $this->capability;
	}

	public function set_capability(string $capability): void
	{
		$this->capability = $capability;
	}



	public function get_menu_icon(): ?string
	{
		return $this->menu_icon;
	}

	public function set_menu_icon(string $menu_icon): void
	{
		$this->menu_icon = $menu_icon;
	}


	public function get_menu_position(): ?int
	{
		return $this->menu_position;
	}

	public function set_menu_position(?int $menu_position): void
	{
		$this->menu_position = $menu_position;
	}


	public function get_parent_slug(): ?string
	{
		return $this->parent_slug;
	}

	public function set_parent_slug(string $parent_slug): void
	{
		$this->parent_slug = $parent_slug;
	}


	public function get_priority(): int {
		return $this->priority;
	}

	public function is_current_page(): bool
	{
		return isset($_GET['page']) && ($_GET['page'] === $this->get_menu_slug());
	}

	public function can_register(): bool
	{
		return is_admin() && current_user_can($this->get_capability());
	}
}