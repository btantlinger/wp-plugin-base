<?php

namespace WebMoves\PluginBase\Events;

use WebMoves\PluginBase\BackgroundTasks\StartSyncBackgroundTask;
use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Contracts\Synchronizers\SchedulableSynchronizer;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\SettingsManager;

/**
 * Scheduled Sync Event Handler
 * 
 * This class manages automatic scheduling and execution of background synchronization tasks.
 * It implements a sophisticated interval management system that ensures proper timing between
 * sync operations regardless of how long each sync takes to complete.
 * 
 * ## Key Features:
 * 
 * ### 1. Dynamic Interval Management
 * - Schedules syncs at configured intervals (e.g., every 30 minutes)
 * - Automatically reschedules the NEXT sync to maintain proper intervals from completion time
 * - Prevents interval drift that could occur with fixed scheduling
 * 
 * ### 2. Smart Rescheduling Logic
 * - When a sync completes (success or failure), immediately reschedules the next run
 * - Uses completion time + full interval (not original schedule time)
 * - Example: 30-minute interval sync that takes 5 minutes will schedule next run 30 minutes from completion
 * 
 * ### 3. Background Task Integration
 * - Delegates actual sync work to StartSyncBackgroundTask for proper background processing
 * - Listens for background task completion/failure events
 * - Maintains scheduling even when sync tasks fail or timeout
 * 
 * ### 4. Settings-Driven Configuration
 * - Automatically updates schedule when interval settings change
 * - Enables/disables scheduling based on user preferences
 * - Supports multiple synchronizer types with independent schedules
 * 
 * ### 5. Initialization & State Management
 * - Prevents duplicate initialization for the same sync type
 * - Tracks initialization state across multiple instances
 * - Handles WordPress cron system integration
 * 
 * ## Typical Flow:
 * 
 * 1. **Initialization** (`on_init`):
 *    - Sets up initial schedule based on synchronizer settings
 *    - Registers completion/failure event handlers
 * 
 * 2. **Scheduled Execution** (`handle_event`):
 *    - WordPress cron triggers the sync at scheduled time
 *    - Delegates to background task for actual processing
 * 
 * 3. **Background Completion** (`on_background_sync_completed`):
 *    - Background task completes and triggers completion event
 *    - Immediately reschedules next run from current time + interval
 *    - Maintains consistent intervals regardless of sync duration
 * 
 * 4. **Settings Changes** (`on_scoped_option_changed`):
 *    - User changes interval or enable/disable setting
 *    - Automatically updates WordPress cron schedule
 * 
 * ## Example Timeline:
 * ```
 * 10:00 AM - Sync scheduled to run
 * 10:00 AM - Background sync starts (takes 8 minutes)
 * 10:08 AM - Background sync completes
 * 10:08 AM - Next sync immediately scheduled for 10:38 AM (30 min interval)
 * 10:38 AM - Next sync runs...
 * ```
 * 
 * This ensures consistent 30-minute intervals between sync completions, not between
 * sync start times, which prevents overlapping syncs and maintains predictable timing.
 * 
 * @see StartSyncBackgroundTask For the actual sync execution logic
 * @see SchedulableSynchronizer For synchronizer-specific configuration
 */
class ScheduledSyncEvent extends \WebMoves\PluginBase\Events\AbstractEvent
{
    /**
     * Whether the scheduler has been initialized for each sync type
     * Prevents duplicate initialization when multiple instances exist
     */
    private static array $initialized_sync_types = [];

    private SchedulableSynchronizer $synchronizer;

    private SyncService $sync_service;

    private StartSyncBackgroundTask $task;

    //use RunSyncTrait;


    public function __construct(PluginMetadata $metadata, SchedulableSynchronizer $synchronizer, StartSyncBackgroundTask $task, SyncService $sync_service)
    {
        // Create hook name based on synchronizer
        $hook_name = $synchronizer->get_sync_type_key() . '_sync';

        parent::__construct($metadata, $hook_name);

        $this->task = $task;
        $this->synchronizer = $synchronizer;
        $this->sync_service = $sync_service;
    }


    public function register(): void
    {
        parent::register();
        $this->register_option_change_hooks();
        $this->register_background_task_hooks();
    }

    /**
     * Register hooks to listen for background task completion/failure events
     * This enables the rescheduling logic that maintains proper intervals
     */
    private function register_background_task_hooks(): void
    {
        // Listen for background task completion
        $completion_hook = $this->task->get_completion_hook_name();
        $failure_hook = $this->task->get_failure_hook_name();
        add_action($completion_hook, [$this, 'on_background_sync_completed'], 10, 3);
        add_action($failure_hook, [$this, 'on_background_sync_failed'], 10, 3);
    }


    /**
     * Handle the main sync event triggered by WordPress cron
     * This method is called when the scheduled time arrives
     */
    public function handle_event(): void
    {
        // Add this at the very beginning to see if it's ever called
        $this->log()->info("========== HANDLE EVENT CALLED =============");
        $this->log()->info("Hook: " . $this->get_hook_name());

        try {
            $this->task->run_sync($this->synchronizer->get_sync_type_key(), 'schedule');
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
        }
    }


    public function get_custom_schedules(): array
    {
        return $this->synchronizer->get_available_schedule_intervals();
    }

    /**
     * Handle background sync completion - always reschedule to maintain proper intervals
     * 
     * This is the key method that implements the dynamic interval management.
     * It ensures the next sync is scheduled for a full interval from NOW (completion time)
     * rather than from the original scheduled time, preventing interval drift.
     */
    public function on_background_sync_completed(string $sync_type, string $triggered_by, mixed $result): void
    {
        // Only handle events for our synchronizer
        if ($sync_type !== $this->synchronizer->get_sync_type_key()) {
            return;
        }

        $this->log()->debug("Background sync completed for {$sync_type}, triggered by: {$triggered_by}");
        $this->reschedule_after_sync_completion();
    }

    /**
     * Handle background sync failure - still reschedule to maintain intervals
     * Even failed syncs need to maintain the schedule to keep trying
     */
    public function on_background_sync_failed(string $sync_type, string $triggered_by, \Exception $e): void
    {
        if ($sync_type !== $this->synchronizer->get_sync_type_key()) {
            return;
        }

        $this->log()->error("Background sync FAILED for {$sync_type}, triggered by: {$triggered_by}", [$e->getMessage()]);
        $this->reschedule_after_sync_completion();
    }

    /**
     * Reschedule the next sync to maintain proper intervals from completion time
     * 
     * This method implements the core interval management logic:
     * 1. Clears the current schedule (which may have been based on the original start time)
     * 2. Schedules the next run for current time + full interval
     * 3. Includes safety checks to prevent excessive rescheduling
     * 
     * This ensures consistent intervals between sync completions, regardless of
     * how long each sync takes to run.
     */
    private function reschedule_after_sync_completion(): void
    {
        if (!$this->synchronizer->is_schedule_enabled()) {
            $this->log()->debug("Scheduling disabled, not rescheduling after sync completion");
            return;
        }

        // Safety check - don't reschedule too frequently
        static $last_reschedule = [];
        $sync_key = $this->synchronizer->get_sync_type_key();
        $now = time();

        if (isset($last_reschedule[$sync_key]) && ($now - $last_reschedule[$sync_key]) < 30) {
            $this->log()->debug("Skipping reschedule - too recent (< 30 seconds)");
            return;
        }

        $last_reschedule[$sync_key] = $now;

        $this->log()->debug("Rescheduling next sync after completion");

        // Clear current schedule
        $this->unschedule();

        // Schedule next run for full interval from NOW (completion time)
        $interval = $this->synchronizer->get_schedule_interval();
        $intervals = wp_get_schedules();
        $interval_seconds = $intervals[$interval]['interval'] ?? 3600;
        $next_run = time() + $interval_seconds;

        $this->schedule(new \DateTime('@' . $next_run), $interval);

        $this->log()->debug("Rescheduled next sync to: " . date('Y-m-d H:i:s', $next_run) . " ({$interval} interval from completion)");
    }



    /**
     * Register hooks to listen for settings changes
     * When interval or enabled settings change, automatically update the schedule
     */
    private function register_option_change_hooks(): void
    {
        $scope = $this->synchronizer->settings()->get_settings_scope();
        $action = SettingsManager::UPDATE_HOOK . '_' . $scope;

        add_action($action, [$this, 'on_scoped_option_changed'], 10, 4);
    }

    public function get_synchronizer(): SchedulableSynchronizer
    {
        return $this->synchronizer;
    }

    /**
     * Handle settings changes for scheduling options
     * Automatically updates the WordPress cron schedule when settings change
     */
    public function on_scoped_option_changed($scope, $old_value, $new_value, $option_name): void
    {
        $opts = [
            SchedulableSynchronizer::SCHEDULE_ENABLED,
            SchedulableSynchronizer::SCHEDULE_INTERVAL,
        ];
        if($scope == $this->synchronizer->settings()->get_settings_scope() && in_array($option_name, $opts)) {
            $this->logger->info("Schedule option changed: {$option_name}, updating schedule");
            $this->update_sync_schedule();
        }
    }

    /**
     * Check if this sync type has already been initialized
     * Prevents duplicate scheduling when multiple instances exist
     */
    protected function is_sync_type_initialized(): bool
    {
        return isset(self::$initialized_sync_types[$this->get_synchronizer_key()]);
    }

    /**
     * Mark this sync type as initialized/uninitialized
     */
    protected function set_sync_type_initialized(bool $is_init): void
    {
        $key = $this->get_synchronizer_key();
        if($is_init) {
            self::$initialized_sync_types[$key] = $key;
        } else {
            unset(self::$initialized_sync_types[$key]);
        }
    }

    /**
     * WordPress init hook handler
     * Sets up the initial schedule if not already initialized
     */
    public function on_init(): void
    {
        if ($this->is_sync_type_initialized()) {
            return;
        }

        $this->update_sync_schedule();
        $this->set_sync_type_initialized(true);
    }

    /**
     * Check if there's currently a running sync for this type
     * Can be used to prevent overlapping syncs if needed
     */
    protected function is_sync_type_running(): bool
    {
        $sync = $this->sync_service->get_running_sync_for_type($this->synchronizer->get_sync_type_key());
        return !is_null($sync);
    }

    /**
     * Update the WordPress cron schedule based on current settings
     * 
     * This method:
     * 1. Checks if scheduling is enabled
     * 2. Compares current schedule with desired schedule
     * 3. Reschedules if interval changed or no schedule exists
     * 4. Clears schedule if disabled
     */
    public function update_sync_schedule(): void
    {
        $interval = $this->synchronizer->get_schedule_interval();
        $existing = wp_next_scheduled($this->get_hook_name());

        if (!$this->synchronizer->is_schedule_enabled()) {
            if ($existing) {
                $this->log()->info('Clearing scheduled sync (disabled)');
                $this->unschedule();
            }
            return;
        }

        $this->log()->debug("Updating sync schedule - Hook: {$this->get_hook_name()}, Interval: {$interval}");

        if ($existing) {
            // Check if interval changed
            $current_interval = $this->get_current_schedule_interval();

            if ($current_interval === $interval) {
                $this->log()->debug('Schedule interval unchanged, keeping existing schedule');
                return;
            }

            $this->log()->info("Interval changed from {$current_interval} to {$interval}, rescheduling");
        }

        // Clear existing and reschedule
        $this->unschedule();

        // Schedule for next interval period
        $intervals = wp_get_schedules();
        $interval_seconds = $intervals[$interval]['interval'] ?? 3600;
        $next_run = time() + $interval_seconds;

        $this->schedule(new \DateTime('@' . $next_run), $interval);
        $this->log()->info("Scheduled sync for: " . date('Y-m-d H:i:s', $next_run) . " with interval: {$interval}");
    }

    /**
     * Get the current schedule interval from WordPress cron system
     * Used to detect when settings have changed
     */
    private function get_current_schedule_interval(): ?string
    {
        $crons = get_option('cron', []);

        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[$this->get_hook_name()])) {
                foreach ($cron[$this->get_hook_name()] as $job) {
                    return $job['schedule'] ?? null;
                }
            }
        }

        return null;
    }



    /**
     * Get next scheduled run time
     */
    public function get_next_scheduled_run(): ?int
    {
        $timestamp = wp_next_scheduled($this->get_hook_name());
        return $timestamp ? $timestamp : null;
    }

    /**
     * Get schedule status info
     */
    public function get_schedule_status(): array
    {
        $next_run = $this->get_next_scheduled_run();
        return [
            'enabled' => $this->synchronizer->is_schedule_enabled(),
            'interval' => $this->synchronizer->get_schedule_interval(),
            'next_run' => $next_run,
            'next_run_formatted' => $next_run ? date_i18n( get_option( 'date_format' ) . ' ScheduledSyncEvent.php' . get_option('time_format'), $next_run) : null,
            'is_scheduled' => !empty($next_run)
        ];
    }

    private function get_synchronizer_key(): string
    {
        return $this->synchronizer->get_sync_type_key();
    }

    public function get_sync_hook(): string
    {
        return $this->get_hook_name();
    }
}