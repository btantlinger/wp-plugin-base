<?php

namespace WebMoves\PluginBase\Contracts\Assets;

interface Script extends Asset
{
	/**
	 * Whether to load script in footer
	 */
	public function in_footer(): bool;

	/**
	 * Get script loading strategy (defer, async, etc.)
	 */
	public function get_strategy(): string|null;

	/**
	 * Get localized script data
	 */
	public function get_localized_data(): array;
}

