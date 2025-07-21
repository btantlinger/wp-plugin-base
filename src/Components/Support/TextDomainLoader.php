<?php

namespace WebMoves\PluginBase\Components\Support;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

class TextDomainLoader extends AbstractComponent {

	private PluginMetadata $metadata;

	/**
	 * @param PluginMetadata $metadata
	 */
	public function __construct(PluginMetadata $metadata ) {
		parent::__construct();
		$this->metadata = $metadata;
		$this->priority = -100;
	}


	public function register_on(): Lifecycle {
		return Lifecycle::INIT;
	}

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->load_textdomain();
	}

	/**
	 * Load plugin textdomain for translations
	 *
	 * @return void
	 */
	public function load_textdomain(): void
	{
		load_plugin_textdomain(
			$this->metadata->get_text_domain(),
			false,
			dirname(plugin_basename($this->metadata->get_plugin_file())) . $this->metadata->get_domain_path()
		);
	}
}