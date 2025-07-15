<?php

namespace WebMoves\PluginBase\Factories;

use WebMoves\PluginBase\Concerns\Components\CanBeScheduled;
use WebMoves\PluginBase\Concerns\Components\CanHaveShortcode;
use WebMoves\PluginBase\Concerns\Components\ComponentRegistration;
use WebMoves\PluginBase\Concerns\Components\CanBeCommand;
use WebMoves\PluginBase\Concerns\Components\CanHaveAssets;
use WebMoves\PluginBase\Concerns\Components\CanHaveSettings;
use WebMoves\PluginBase\Concerns\Components\CanHaveAction;
use WebMoves\PluginBase\Concerns\Components\CanHaveFilter;
use WebMoves\PluginBase\Components\AbstractComponent;

class ComponentFactory
{
	/**
	 * Create a WP-CLI command component
	 */
	public static function command(string $name, callable $handler, string $description = '', array $synopsis = []): object
	{
		return new class($name, $handler, $description, $synopsis) extends AbstractComponent {
			use ComponentRegistration;
			use CanBeCommand;

			public function __construct(
				private string $name,
				private $handler,
				private string $description,
				private array $synopsis
			) {}

			protected function get_command_name(): string { return $this->name; }
			protected function get_command_description(): string { return $this->description; }
			protected function get_command_synopsis(): array { return $this->synopsis; }
			protected function execute_command($args, $assoc_args): void {
				call_user_func($this->handler, $args, $assoc_args);
			}
		};
	}

	/**
	 * Create a scheduled cron task component
	 */
	public static function schedule(string $hook, callable $handler, string $schedule = 'daily', ?int $start_time = null): object
	{
		return new class($hook, $handler, $schedule, $start_time) extends AbstractComponent {
			use ComponentRegistration;
			use CanBeScheduled;

			public function __construct(
				private string $hook,
				private $handler,
				private string $schedule,
				private ?int $start_time = null
			) {}

			protected function get_schedule_hook(): string { return $this->hook; }
			protected function get_schedule_recurrence(): string { return $this->schedule; }
			protected function get_schedule_start_time(): int { return $this->start_time ?? time(); }
			protected function execute_schedule(): void { call_user_func($this->handler); }
		};
	}

	/**
	 * Create an assets component
	 */
	public static function assets(array $front_assets = [], array $admin_assets = []): object
	{
		return new class($front_assets, $admin_assets) extends AbstractComponent {
			use ComponentRegistration;
			use CanHaveAssets;

			public function __construct(
				private array $front_assets,
				private array $admin_assets
			) {}

			protected function get_front_assets(): array { return $this->front_assets; }
			protected function get_admin_assets(): array { return $this->admin_assets; }
		};
	}

	/**
	 * Create a WordPress action component
	 */
	public static function action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): object
	{
		return new class($hook, $callback, $priority, $accepted_args) extends AbstractComponent {
			use ComponentRegistration;
			use CanHaveAction;

			public function __construct(
				private string $hook,
				private $callback,
				protected int $priority,
				private int $accepted_args
			) {}

			protected function get_action_hook(): string {
				return $this->hook;
			}

			public function execute_action( ...$args ): void {
				call_user_func($this->callback, ...$args);
			}

			protected function get_action_priority(): int {
				return $this->priority;
			}

			protected function get_action_accepted_args(): int {
				return $this->accepted_args;
			}
		};
	}

	/**
	 * Create a WordPress filter component
	 */
	public static function filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): object
	{
		return new class($hook, $callback, $priority, $accepted_args) extends AbstractComponent {
			use ComponentRegistration;
			use CanHaveFilter;

			public function __construct(
				private string $hook,
				private $callback,
				protected int $priority,
				private int $accepted_args
			) {}


			protected function get_filter_hook(): string {
				return $this->hook;
			}

			public function execute_filter( ...$args ): mixed {
				return call_user_func($this->callback, ...$args);
			}
		};
	}

	/**
	 * Create a shortcode component
	 */
	public static function shortcode(string $tag, callable $handler): object
	{
		return new class($tag, $handler) extends AbstractComponent {
			use ComponentRegistration;
			use CanHaveShortcode;

			public function __construct(
				private string $tag,
				private $handler
			) {}

			protected function render_shortcode( $atts, $content = null ): string {
				call_user_func($this->handler, $atts, $content);
			}

			protected function get_shortcode_tag(): string {
				return $this->tag;
			}
		};
	}

	/**
	 * Create a settings component
	 */
	public static function settings(string $group, array $fields, string $page): object
	{
		return new class($group, $fields, $page) extends AbstractComponent {
			use ComponentRegistration;
			use CanHaveSettings;

			public function __construct(
				private string $group,
				private array $fields,
				private string $page
			) {}

			protected function get_settings_group(): string { return $this->group; }
			protected function get_settings_fields(): array { return $this->fields; }
			protected function get_settings_page(): string { return $this->page; }
		};
	}
}