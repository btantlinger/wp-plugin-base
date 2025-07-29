<?php

namespace WebMoves\PluginBase\Assets;

use WebMoves\PluginBase\Contracts\Assets\Script;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;


class ScriptAsset extends AbstractAsset implements Script
{
	protected bool $in_footer;
	protected ?string $strategy;
	protected array $localized_data;

	public function __construct(
		PluginMetadata $metadata,
		string $relative_path,
		array $dependencies = [],
		string|bool|null $version = null,
		bool $in_footer = true,
		?string $strategy = null,
		array $localized_data = [],
		string $enqueue_hook = 'wp_enqueue_scripts',
		?string $custom_handle = null
	) {
		parent::__construct($metadata, $relative_path, $dependencies, $version, $enqueue_hook, $custom_handle);
		$this->in_footer = $in_footer;
		$this->strategy = $strategy;
		$this->localized_data = $localized_data;
	}

	public function in_footer(): bool
	{
		return $this->in_footer;
	}

	public function get_strategy(): ?string
	{
		return $this->strategy;
	}

	public function get_localized_data(): array
	{
		return $this->localized_data;
	}

	protected function enqueue(): void
	{
		wp_enqueue_script(
			$this->get_handle(),
			$this->get_src(),
			$this->get_dependencies(),
			$this->get_version(),
			[
				'in_footer' => $this->in_footer(),
				'strategy' => $this->get_strategy()
			]
		);

		// Handle localized data
		if (!empty($this->localized_data)) {
			foreach ($this->localized_data as $object_name => $data) {
				wp_localize_script($this->get_handle(), $object_name, $data);
			}
		}
	}
}
