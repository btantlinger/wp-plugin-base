<?php

namespace WebMoves\PluginBase\Contracts\PostTypes;

use WebMoves\PluginBase\Contracts\Components\Component;

interface PostType extends Component
{
	/**
	 * Get the post type identifier
	 */
	public function get_post_type(): string;

	/**
	 * Get the complete configuration array for register_post_type
	 */
	public function get_config(): array;

	/**
	 * Get the generated singular name for this post type
	 */
	public function get_singular_name(): string;

	/**
	 * Get the generated plural name for this post type
	 */
	public function get_plural_name(): string;

	/**
	 * Get the REST API endpoint URL for this post type
	 * Returns null if REST API is not enabled
	 */
	public function get_rest_url(): ?string;
}