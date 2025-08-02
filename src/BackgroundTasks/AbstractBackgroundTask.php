<?php

namespace WebMoves\PluginBase\BackgroundTasks;

use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Contracts\BackgroundTasks\BackgroundTask;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Enums\Lifecycle;
use WebMoves\PluginBase\Concerns\HasLogger;

abstract class AbstractBackgroundTask extends AbstractComponent implements BackgroundTask
{
    use HasLogger;

    private PluginMetadata $metadata;
    protected string $hook_name;

    public function __construct(PluginMetadata $metadata, string $hook_name)
    {
        parent::__construct();
        $this->metadata = $metadata;
        $this->hook_name = ltrim(str_replace('-', '_', sanitize_key($hook_name)), '_');
    }

    public function register_on(): Lifecycle
    {
        return Lifecycle::PRE_BOOTSTRAP;
    }

    public function register(): void
    {
        // Register the hook handler for when cron executes the scheduled work
        add_action($this->get_hook_name(), [$this, 'handle_scheduled_work'], 10, 10);

        // Hook into init for initialization
        add_action('init', [$this, 'on_background_task_init'], 10);
    }

    public function on_background_task_init(): void
    {
        // Optional override point
    }

    public function get_hook_name(): string
    {
        return rtrim($this->metadata->get_prefix(), '_') . '_' . ltrim($this->hook_name, '_');
    }

    public function run(...$args): bool
    {
        $this->log()->debug("BackgroundTask [{$this->get_task_name()}]: Scheduling background execution");

        if (!$this->validate_args(...$args)) {
            $this->log()->error("BackgroundTask [{$this->get_task_name()}]: Invalid arguments provided");
            return false;
        }

        $hook_name = $this->get_hook_name();

        // Clear ALL scheduled events for this hook
        $cleared = wp_clear_scheduled_hook($hook_name);
        if ($cleared > 0) {
            $this->log()->debug("Cleared {$cleared} existing scheduled events for hook: {$hook_name}");
        }

        // Debug logging
        $before = wp_next_scheduled($hook_name, $args);
        $this->log()->debug("Before scheduling - next scheduled: " . ($before ? date('Y-m-d H:i:s', $before) : 'none'));

        // Schedule for immediate execution
        $result = wp_schedule_single_event(time() , $hook_name, $args);

        $this->log()->debug("wp_schedule_single_event result: " . ($result ? 'true' : 'false'));

        if ($result) {
            $after = wp_next_scheduled($hook_name, $args);
            $this->log()->debug("After scheduling - next scheduled: " . ($after ? date('Y-m-d H:i:s', $after) : 'none'));

            // Only spawn cron if we're not already in a cron context
            if (!$this->is_in_cron_context()) {
                $this->log()->debug("BackgroundTask [{$this->get_task_name()}]: Spawning cron for immediate execution");
                spawn_cron();
            } else {
                $this->log()->debug("BackgroundTask [{$this->get_task_name()}]: Already in cron context, skipping spawn_cron()");
            }

            return true;
        } else {
            $this->log()->error("BackgroundTask [{$this->get_task_name()}]: Failed to schedule background work");
            return false;
        }
    }

    /**
     * Check if we're currently running in a WordPress cron context
     */
    protected function is_in_cron_context(): bool
    {
        return defined('DOING_CRON') && DOING_CRON;
    }

    public function is_hooked(): bool
    {
        return has_action($this->get_hook_name(), [$this, 'handle_scheduled_work']) !== false;
    }

    public function is_scheduled(): bool
    {
        return wp_next_scheduled($this->get_hook_name()) !== false;
    }

    public function handle_scheduled_work(...$args): void
    {
        $this->log()->debug("BackgroundTask [{$this->get_task_name()}]: Starting scheduled work execution");

        // Set up for long-running execution
        $this->prepare_for_long_execution();

        $start_time = microtime(true);

        try {
            // Execute the actual work
            $result = $this->execute_background_work(...$args);

            $duration = round(microtime(true) - $start_time, 2);
            $this->log()->debug("BackgroundTask [{$this->get_task_name()}]: Completed in {$duration}s");

            $this->on_background_work_completed($result, ...$args);

            // Fire completion hook with clean parameter order
            $completion_hook = $this->get_completion_hook_name();
            $sync_type = $args[0] ?? null;
            $triggered_by = $args[1] ?? 'unknown';

            do_action($completion_hook, $sync_type, $triggered_by, $result);

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $start_time, 2);
            $this->log()->error("BackgroundTask [{$this->get_task_name()}]: Failed after {$duration}s - " . $e->getMessage());

            $this->on_background_work_failed($e, ...$args);

            // Fire failure hook
            $failure_hook = $this->get_failure_hook_name();
            $sync_type = $args[0] ?? null;
            $triggered_by = $args[1] ?? 'unknown';

            do_action($failure_hook, $sync_type, $triggered_by, $e);

            if ($this->should_rethrow_exceptions()) {
                throw $e;
            }
        }
    }

    public function get_completion_hook_name(): string
    {
        return $this->get_hook_name() . '_completed';
    }

    public function get_failure_hook_name(): string
    {
        return $this->get_hook_name() . '_failed';
    }


    protected function prepare_for_long_execution(): void
    {
        // Set up for long-running tasks
        ini_set('max_execution_time', -1);
        ini_set('max_input_time', -1);
        set_time_limit(0);
        ignore_user_abort(true);

        // Optionally increase memory limit
        if ($this->get_memory_limit()) {
            ini_set('memory_limit', $this->get_memory_limit());
        }
    }

    private function get_class_basename(string $class): string
    {
        $path = explode('\\', $class);
        return array_pop($path);
    }

    protected function get_task_name(): string
    {
        return $this->get_class_basename(static::class);
    }

    protected function validate_args(...$args): bool
    {
        return true; // Default: all arguments are valid
    }

    protected function get_memory_limit(): ?string
    {
        return null;
    }

    protected function on_background_work_completed($result, ...$args): void
    {
        // Optional override point
    }

    protected function on_background_work_failed(\Exception $e, ...$args): void
    {
        // Optional override point
    }

    protected function should_rethrow_exceptions(): bool
    {
        return false;
    }

    // Abstract method - implement the actual work
    abstract protected function execute_background_work(...$args);
}