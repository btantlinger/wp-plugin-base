<?php

namespace WebMoves\PluginBase\Contracts\Taxonomies;

use WebMoves\PluginBase\Contracts\Components\Component;

interface Taxonomy extends Component
{
	/**
	 * Get the taxonomy identifier
	 */
	public function get_taxonomy(): string;

	/**
	 * Get the post types this taxonomy applies to
	 */
	public function get_object_types(): array;

	/**
	 * Get the complete configuration array for register_taxonomy
	 */
	public function get_config(): array;

	/**
	 * Get the generated singular name for this taxonomy
	 */
	public function get_singular_name(): string;

	/**
	 * Get the generated plural name for this taxonomy
	 */
	public function get_plural_name(): string;

	/**
	 * Get the REST API endpoint URL for this taxonomy
	 * Returns null if REST API is not enabled
	 */
	public function get_rest_url(): ?string;
}