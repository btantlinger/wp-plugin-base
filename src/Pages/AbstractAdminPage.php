<?php

namespace WebMoves\PluginBase\Pages;

use WebMoves\PluginBase\Assets\AdminScriptAsset;
use WebMoves\PluginBase\Assets\AdminStyleAsset;
use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Concerns\Components\HasAdminMenu;
use WebMoves\PluginBase\Contracts\Assets\Asset;
use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractAdminPage implements Component  {

	use ComponentRegistration;
	use HasAdminMenu;

	protected PluginCore $core;
	protected PluginMetadata $metadata;
	protected string $page_slug;
	protected ?string $parent_slug = null;
	protected string $capability = 'manage_options';
	protected ?string $menu_icon = null;
	protected int $priority = 10;
	protected ?int $menu_position = null;
	
	/** @var Asset[] */
	protected array $assets = [];

	public function __construct(PluginCore $core, string $page_slug, ?string $parent_slug = null, array $assets = []) {
		$this->core = $core;
		$this->metadata = $core->get_metadata();
		$this->page_slug = $page_slug;
		$this->parent_slug = $parent_slug;
		$this->assets = $assets;
	}

	public function get_metadata(): PluginMetadata
	{
		return $this->metadata;
	}

	public function get_core(): PluginCore
	{
		return $this->core;
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

	/**
	 * Add assets to this page
	 */
	public function add_assets(Asset ...$assets): void
	{
		foreach ($assets as $asset) {
			$this->assets[] = $asset;
		}
	}

	/**
	 * Get all assets for this page
	 */
	public function get_assets(): array
	{
		return $this->assets;
	}

	/**
	 * Create a style asset for this page
	 */
	protected function create_style_asset(
		string $relative_path,
		array $dependencies = [],
		string|bool|null $version = null,
		string $media = 'all',
		?string $custom_handle = null
	): AdminStyleAsset {
		return new AdminStyleAsset(
			$this->metadata,
			$relative_path,
			$dependencies,
			$version,
			$media,
			null, // allowed_pages = null means all admin pages
			$custom_handle
		);
	}

	/**
	 * Create a script asset for this page
	 */
	protected function create_script_asset(
		string $relative_path,
		array $dependencies = [],
		string|bool|null $version = null,
		bool $in_footer = true,
		?string $strategy = null,
		array $localized_data = [],
		?string $custom_handle = null
	): AdminScriptAsset {
		return new AdminScriptAsset(
			$this->metadata,
			$relative_path,
			$dependencies,
			$version,
			$in_footer,
			$strategy,
			$localized_data,
			null, // allowed_pages = null means all admin pages
			$custom_handle
		);
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

	/**
	 * Called after the admin menu has been added to conditionally register page-specific assets
	 */
	protected function on_admin_menu_added(): void
	{
		$assets = array_merge($this->create_assets(), $this->assets);;

		// Only register assets if we're on this specific page
		if ($this->is_current_page() && !empty($assets)) {
			$cm = $this->core->get_component_manager();
			foreach ($assets as $asset) {
				if(!$cm->contains($asset)) {
					$cm->add( $asset );
				}
			}
		}
	}

	/**
	 * Override this method to define assets for the page
	 */
	protected function create_assets(): array {
		return [];
	}
}