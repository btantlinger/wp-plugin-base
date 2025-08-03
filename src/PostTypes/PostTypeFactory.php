<?php

namespace WebMoves\PluginBase\PostTypes;

use WebMoves\PluginBase\Concerns\HasInflector;
use WebMoves\PluginBase\Contracts\PostTypes\PostType;
use WebMoves\PluginBase\Enums\Lifecycle;

class PostTypeFactory
{
	/**
	 * Create a PostType from a configuration array
	 */
	public static function createFromArray(string $post_type, array $config): PostType
	{
		return new class($post_type, $config) implements PostType {

			use HasInflector;

			public function __construct(
				private string $post_type,
				private array $config
			) {}

			public function get_post_type(): string
			{
				return $this->post_type;
			}

			public function get_config(): array
			{
				return $this->config;
			}

			public function register_on(): Lifecycle
			{
				return Lifecycle::INIT;
			}

			public function register(): void
			{
				register_post_type($this->get_post_type(), $this->get_config());
			}

			public function get_priority(): int
			{
				return 10;
			}

			public function can_register(): bool
			{
				return true;
			}

			public function get_singular_name(): string
			{
				return $this->config['labels']['singular_name'] ?? ucfirst(str_replace('_', ' ', $this->post_type));
			}

			public function get_plural_name(): string
			{
				if(!empty($this->config['labels']['name'])) {
					return $this->config['labels']['name'];
				}
				return $this->pluralize($this->get_singular_name());
			}

			public function get_rest_url(): ?string
			{
				if (empty($this->config['show_in_rest'])) {
					return null;
				}

				$rest_base = $this->config['rest_base'] ?? $this->post_type;
				return rest_url("wp/v2/{$rest_base}");
			}
		};
	}
}