<?php

namespace WebMoves\PluginBase\Events;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Concerns\HasLogger;
use WebMoves\PluginBase\Contracts\Events\Event;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractEvent extends AbstractComponent implements Event
{
	use HasLogger;

	private PluginMetadata $metadata;

	protected string $hook_name;
	protected int $hook_priority = 10;
	protected int $hook_accepted_args = 1;

	// Custom schedules to register
	protected array $custom_schedules = [];

	public function __construct(PluginMetadata $metadata, string $hook_name, int $hook_priority = 10, int $hook_accepted_args = 1)
	{
		parent::__construct();
		$this->metadata = $metadata;
		$this->hook_name = ltrim(str_replace('-', '_', sanitize_key($hook_name)), '_');
		$this->hook_priority = $hook_priority;
		$this->hook_accepted_args = $hook_accepted_args;
		$this->logger = $this->log();
		$this->metadata->get_prefix();
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

		// Hook into WordPress init for scheduling setup
		add_action('init', [$this, 'on_init']);
	}

	/**
	 * Called on WordPress 'init' hook - perfect place for cron scheduling
	 * Override in subclasses to set up scheduling
	 */
	abstract public function on_init(): void;


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
		$this->logger->info('=== UNSCHEDULE EVENT ===');

		$hook = $this->get_hook_name();
		$cleared_any = false;

		// Get all scheduled instances, not just the next one
		while ($timestamp = wp_next_scheduled($hook)) {
			$this->logger->info('Clearing scheduled event at: ' . date('Y-m-d H:i:s', $timestamp));
			$result = wp_unschedule_event($timestamp, $hook);
			$this->logger->info('Unschedule result: ' . ($result ? 'SUCCESS' : 'FAILED'));

			if ($result) {
				$cleared_any = true;
			} else {
				// Prevent infinite loop - if unschedule fails, break and use nuclear option
				$this->logger->info('Failed to unschedule, using wp_clear_scheduled_hook as fallback');
				$nuclear_result = wp_clear_scheduled_hook($hook);
				$cleared_any = $cleared_any || ($nuclear_result > 0);
				break;
			}
		}

		// Double-check with nuclear option if needed
		$remaining = wp_next_scheduled($hook);
		if ($remaining) {
			$this->logger->info('WARNING: Events still scheduled after clear attempt, using wp_clear_scheduled_hook');
			$nuclear_result = wp_clear_scheduled_hook($hook);
			$cleared_any = $cleared_any || ($nuclear_result > 0);
		}

		$this->logger->info('=== END UNSCHEDULE EVENT ===');
		return $cleared_any;
	}

	/**
	 * Handle the event - override in subclasses
	 */
	public abstract function handle_event(...$args): void;
}