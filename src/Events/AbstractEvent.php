<?php

namespace WebMoves\PluginBase\Events;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\Events\Event;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractEvent extends AbstractComponent implements Event {

	private PluginMetadata $metadata;

	protected string $hook_name;
	protected int $hook_priority = 10;
	protected int $hook_accepted_args;

	// Custom schedules to register
	protected array $custom_schedules = [];

	public function __construct(PluginMetadata $metadata, string $hook_name, int $hook_priority = 10, int $hook_accepted_args = 1)
	{
		parent::__construct();
		$this->metadata = $metadata;
		$this->hook_name = ltrim(str_replace('-', '_', sanitize_key($hook_name)), '_');
		$this->hook_priority = $hook_priority;
		$this->hook_accepted_args = $hook_accepted_args;
	}

	public function register_on(): Lifecycle
	{
		return Lifecycle::PRE_BOOTSTRAP;
	}

	public function register(): void
	{
		// Register custom schedules
		add_filter('cron_schedules', [$this, 'add_custom_schedules']);

		// Register the event handler
		add_action($this->get_hook_name(), [$this, 'handle_event'], $this->hook_priority, $this->hook_accepted_args);
	}

	/**
	 * Add custom cron schedules
	 */
	public function add_custom_schedules(array $schedules): array
	{
		foreach ($this->get_custom_schedules() as $key => $schedule) {
			if (!isset($schedules[$key])) {
				$schedules[$key] = $schedule;
			}
		}
		return $schedules;
	}

	/**
	 * Get custom schedules - override in child classes to add custom intervals
	 */
	public function get_custom_schedules(): array
	{
		return $this->custom_schedules;
	}

	/**
	 * Add a custom schedule
	 */
	protected function add_custom_schedule(string $key, int $interval_seconds, string $display_name): void
	{
		$this->custom_schedules[$key] = [
			'interval' => $interval_seconds,
			'display' => $display_name
		];
	}

	// ... rest of your existing methods stay the same ...

	public function get_hook_name(): string
	{
		return rtrim($this->metadata->get_prefix(), '_') . '_' . ltrim($this->hook_name, '_');
	}

	public function is_scheduled(): bool
	{
		return wp_next_scheduled($this->get_hook_name()) !== false;
	}

	public function is_recurring(): bool
	{
		return wp_get_schedule($this->get_hook_name()) !== false;
	}

	public function get_schedule_name(): ?string
	{
		$name = wp_get_schedule($this->get_hook_name());
		return $name ?: null;
	}

	public function get_next_run(): ?\DateTime
	{
		$next_run = wp_next_scheduled($this->get_hook_name());
		return $next_run ? new \DateTime('@' . $next_run) : null;
	}

	public function get_interval_seconds(): ?int
	{
		$schedule = wp_get_schedule($this->get_hook_name());
		if (!$schedule) {
			return null;
		}
		$schedules = wp_get_schedules();
		return $schedules[$schedule]['interval'] ?? null;
	}

	public function get_interval_display(): ?string
	{
		$schedule = wp_get_schedule($this->get_hook_name());
		if (!$schedule) {
			return null;
		}
		$schedules = wp_get_schedules();
		return $schedules[$schedule]['display'] ?? null;
	}

	public function schedule(\DateTime|int|null $when = null, ?string $recurrence = null, array $args = []): bool
	{
		if ($this->is_scheduled()) {
			$this->unschedule();
		}

		$timestamp = match(true) {
			$when instanceof \DateTime => $when->getTimestamp(),
			is_int($when) => $when,
			default => time()
		};

		if ($recurrence) {
			return wp_schedule_event($timestamp, $recurrence, $this->get_hook_name(), $args) !== false;
		} else {
			return wp_schedule_single_event($timestamp, $this->get_hook_name(), $args) !== false;
		}
	}

	public function unschedule(): bool
	{
		return wp_clear_scheduled_hook($this->get_hook_name()) !== false;
	}
}