<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Concerns\Components\HasAdminMenu;
use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Enums\Lifecycle;


abstract class AbstractAdminPage implements Component  {

	use ComponentRegistration;
	use HasAdminMenu;

	protected string $page_slug;

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
	public function __construct(string $page_slug, ?string $parent_slug = null) {
		$this->page_slug  = $page_slug;
		$this->parent_slug = $parent_slug;
	}

	public function get_menu_slug(): string
	{
		return $this->page_slug;
	}


	public function get_capability(): string
	{
		return $this->capability;
	}

	public function get_menu_icon(): ?string
	{
		return $this->menu_icon;
	}

	public function get_menu_position(): ?int
	{
		return $this->menu_position;
	}


	public function get_parent_slug(): ?string
	{
		return $this->parent_slug;
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

	public function register_on(): Lifecycle {
		return Lifecycle::INIT;
	}
}