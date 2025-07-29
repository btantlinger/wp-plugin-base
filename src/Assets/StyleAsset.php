<?php

namespace WebMoves\PluginBase\Assets;

use WebMoves\PluginBase\Contracts\Assets\Style;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

class StyleAsset extends AbstractAsset implements Style
{
	protected string $media;

	public function __construct(
		PluginMetadata $metadata,
		string $relative_path,
		array $dependencies = [],
		string|bool|null $version = null,
		string $media = 'all',
		string $enqueue_hook = 'wp_enqueue_scripts',
		?string $custom_handle = null
	) {
		parent::__construct($metadata, $relative_path, $dependencies, $version, $enqueue_hook, $custom_handle);
		$this->media = $media;
	}

	public function get_media(): string
	{
		return $this->media;
	}

	protected function enqueue(): void
	{
		wp_enqueue_style(
			$this->get_handle(),
			$this->get_src(),
			$this->get_dependencies(),
			$this->get_version(),
			$this->get_media()
		);
	}
}
