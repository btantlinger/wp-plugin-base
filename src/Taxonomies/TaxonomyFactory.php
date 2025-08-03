<?php

namespace WebMoves\PluginBase\Taxonomies;

use WebMoves\PluginBase\Concerns\HasInflector;
use WebMoves\PluginBase\Contracts\Taxonomies\Taxonomy;
use WebMoves\PluginBase\Enums\Lifecycle;

class TaxonomyFactory
{
	/**
	 * Create a Taxonomy from a configuration array
	 */
	public static function createFromArray(string $taxonomy, array $object_types, array $config): Taxonomy
	{
		return new class($taxonomy, $object_types, $config) implements Taxonomy {

			use HasInflector;
			public function __construct(
				private string $taxonomy,
				private array $object_types,
				private array $config
			) {}

			public function get_taxonomy(): string
			{
				return $this->taxonomy;
			}

			public function get_object_types(): array
			{
				return $this->object_types;
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
				register_taxonomy($this->get_taxonomy(), $this->get_object_types(), $this->get_config());
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
				return $this->config['labels']['singular_name'] ?? ucfirst(str_replace('_', ' ', $this->taxonomy));
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

				$rest_base = $this->config['rest_base'] ?? $this->taxonomy;
				return rest_url("wp/v2/{$rest_base}");
			}
		};
	}
}