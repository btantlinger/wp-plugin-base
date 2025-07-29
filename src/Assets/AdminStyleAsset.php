<?php

namespace WebMoves\PluginBase\Assets;

use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

/**
 * Asset that only loads on admin pages
 */
class AdminStyleAsset extends StyleAsset
{
	private ?array $allowed_pages;

	public function __construct(
		PluginMetadata $metadata,
		string $relative_path,
		array $dependencies = [],
		string|bool|null $version = null,
		string $media = 'all',
		?array $allowed_pages = null,
		?string $custom_handle = null
	) {
		parent::__construct($metadata, $relative_path, $dependencies, $version, $media, 'admin_enqueue_scripts', $custom_handle);
		$this->allowed_pages = $allowed_pages;
	}

	public function should_enqueue(): bool
	{
		if (!is_admin()) {
			return false;
		}

		// If no specific pages defined, enqueue on all admin pages
		if ($this->allowed_pages === null) {
			return true;
		}

		// Check if current page is in allowed pages
		$current_screen = get_current_screen();
		return $current_screen && in_array($current_screen->id, $this->allowed_pages);
	}

	public function can_register(): bool
	{
		return is_admin();
	}
}