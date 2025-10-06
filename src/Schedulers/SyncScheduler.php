<?php

namespace WebMoves\PluginBase\Schedulers;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Contracts\Synchronizers\SchedulableSynchronizer;
use WebMoves\PluginBase\Settings\GlobalSyncSettings;
use WebMoves\PluginBase\Components\AbstractComponent;
use WebMoves\PluginBase\Concerns\HasLogger;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\SettingsManager;
use WebMoves\PluginBase\Enums\Lifecycle;


class SyncScheduler extends AbstractComponent
{
    const WATCHDOG_MIN_MINUTES = 2;

    const TIMEOUT_WATCHDOG_HOOK = 'sync_timeout_check_watchdog';


    /**
     * Whether the scheduler has been initialized
     *
     * @var bool
     */
    private static array $initialized_sync_types = [];


    private static bool $is_watchdog_initialized = false;


    use HasLogger;
    
    private SchedulableSynchronizer $synchronizer;

    private SyncService $sync_service;

    private PluginMetadata $metadata;

    private GlobalSyncSettings $sync_settings;


    public static function clear_sync_timeout_watchdog(): void
    {
        self::force_clear_scheduled_hook(self::TIMEOUT_WATCHDOG_HOOK);
    }

    public static function force_clear_scheduled_hook(string $hook): void
    {
        $logger = \WebMoves\PluginBase\Logging\LoggerFactory::logger();
        $logger->info('=== CLEAR_SCHEDULED_SYNC ===');

        // Get all scheduled instances, not just the next one
        while ($timestamp = wp_next_scheduled($hook)) {
            $logger->info('Clearing scheduled event at: ' . date('Y-m-d H:i:s', $timestamp));
            $result = wp_unschedule_event($timestamp, $hook);
            $logger->info('Unschedule result: ' . ($result ? 'SUCCESS' : 'FAILED'));

            // Prevent infinite loop - if unschedule fails, break
            if (!$result) {
                $logger->info('Failed to unschedule, using wp_clear_scheduled_hook as fallback');
                wp_clear_scheduled_hook($hook);
                break;
            }
        }

        // Double-check with nuclear option
        $remaining = wp_next_scheduled($hook);
        if ($remaining) {
            $logger->info('WARNING: Events still scheduled after clear attempt, using wp_clear_scheduled_hook');
            wp_clear_scheduled_hook($hook);
        }

        $logger->info('=== END CLEAR_SCHEDULED_SYNC ===');
    }


    /**
     * Constructor
     */
    public function __construct(SchedulableSynchronizer $synchronizer, SyncService $sync_service, PluginMetadata $metadata, GlobalSyncSettings $sync_settings)
    {
        parent::__construct();
        $this->logger = $this->log();
        $this->synchronizer = $synchronizer;
        $this->metadata = $metadata;
        $this->sync_service = $sync_service;
        $this->sync_settings = $sync_settings;
    }

    public function register_on(): Lifecycle
    {
        return Lifecycle::BOOTSTRAP;
    }

    public function register(): void
    {
        // Hook into WordPress init
        add_action('init', [$this, 'init_scheduler']);

        // Register the scheduled tasks
        add_action($this->get_sync_hook(), [$this, 'run_scheduled_sync']);

        // Only register the global timeout watchdog once per type
        if (!$this->is_timeout_watchdog_initialized()) {
            add_action($this->get_timeout_watchdog_hook(), [$this, 'check_for_sync_timeouts']);
            $this->set_timeout_watchdog_initialized(true);
        }
        // Add custom intervals
        add_filter('cron_schedules', [$this, 'add_custom_cron_intervals']);

        $this->register_option_change_hooks();
    }


    private function register_option_change_hooks(): void
    {
        $scope = $this->synchronizer->settings()->get_settings_scope();
        $action = SettingsManager::UPDATE_HOOK . '_' . $scope;

        add_action($action, [$this, 'on_scoped_option_changed'], 10, 4);


/*        // Hook for schedule enabled/disabled
        add_action('update_option_' . $this->synchronizer->create_option_key(SchedulableSynchronizer::SCHEDULE_ENABLED),
            [$this, 'on_schedule_option_changed'], 10, 3);

        // Hook for schedule interval changes
        add_action('update_option_' . $this->synchronizer->create_option_key(SchedulableSynchronizer::SCHEDULE_INTERVAL),
            [$this, 'on_schedule_option_changed'], 10, 3);*/
    }

    public function get_synchronizer(): SchedulableSynchronizer
    {
        return $this->synchronizer;
    }

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

    protected function is_sync_type_initialized(): bool
    {
        return isset(self::$initialized_sync_types[$this->get_synchronizer_key()]);
    }

    protected function set_sync_type_initialized(bool $is_init): void
    {
        $key = $this->get_synchronizer_key();
        if($is_init) {
            self::$initialized_sync_types[$key] = $key;
        } else {
            unset(self::$initialized_sync_types[$key]);
        }
    }

    protected function is_timeout_watchdog_initialized(): bool
    {
        return self::$is_watchdog_initialized; //isset(self::$initialized_timeout_watchdogs[$this->get_timeout_watchdog_hook()]);
    }

    protected function set_timeout_watchdog_initialized(bool $is_init): void
    {
/*        $key = $this->get_timeout_watchdog_hook();
        if($is_init) {
            self::$initialized_timeout_watchdogs[$key] = $key;
        } else {
            unset(self::$initialized_timeout_watchdogs[$key]);
        }*/
        self::$is_watchdog_initialized = $is_init;
    }

    public function add_custom_cron_intervals($schedules): array
    {
        $n = self::WATCHDOG_MIN_MINUTES;
        $custom_schedules = [
            "every_{$n}_minutes" => [
                'interval' => $n * MINUTE_IN_SECONDS,
                'display' => __("Every {$n} Minutes (System)", $this->metadata->get_text_domain())
            ]
        ];
        return array_merge($schedules, $custom_schedules, $this->synchronizer->get_available_schedule_intervals());
    }


    public function init_scheduler(): void
    {
        if ($this->is_sync_type_initialized()) {
            return;
        }

        //is the watchdog scheduled? it should not be...
        $watchdog_hook = $this->get_timeout_watchdog_hook();
        if (!wp_next_scheduled($watchdog_hook)) {
            $interval = "every_" . self::WATCHDOG_MIN_MINUTES . "_minutes";
            wp_schedule_event(time(), $interval, $watchdog_hook);
            $this->logger->info('Scheduled timeout watchdog ' . $watchdog_hook);
        }

        $this->update_sync_schedule();
        $this->set_sync_type_initialized(true);
    }

    protected function is_sync_type_running()
    {
        $sync = $this->sync_service->get_running_sync_for_type($this->synchronizer->get_sync_type_key());
        return !is_null($sync);
    }

    public function run_scheduled_sync(): void
    {
        // Check if any sync is currently running
/*        if ($this->is_sync_type_running()) {
            $this->logger->warning('Sync: Skipping scheduled sync - another sync is already running');
            return;
        }*/

        if (!$this->synchronizer->is_schedule_enabled()) {
            $this->logger->warning('Sync: Scheduled sync is disabled');
            return;
        }

        ini_set('max_execution_time', -1);
        ini_set('max_input_time', -1);
        set_time_limit(0);
        $this->logger->info('Sync: Starting scheduled sync');

        try {
            // Run the sync command
            $this->synchronizer->sync('schedule');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Update the sync schedule based on current settings
     */
    public function update_sync_schedule()
    {        
        $interval = $this->synchronizer->get_schedule_interval();

        // Check if there's already a schedule with the same interval
        $existing = wp_next_scheduled($this->get_sync_hook());

        if (!$this->synchronizer->is_schedule_enabled()) {
            // Should not be scheduled - clear if exists
            if ($existing) {
                $this->logger->info('Clearing scheduled sync (disabled)');
                $this->clear_scheduled_sync();
            }
            return;
        }

        // Check if we already have the right schedule
        if ($existing) {
            // Get the current schedule interval
            $crons = get_option('cron', []);
            $current_interval = null;

            foreach ($crons as $timestamp => $cron) {
                if (isset($cron[$this->get_sync_hook()])) {
                    foreach ($cron[$this->get_sync_hook()] as $job) {
                        $current_interval = $job['schedule'] ?? null;
                        break 2;
                    }
                }
            }

            // If the interval matches, don't reschedule
            if ($current_interval === $interval) {
                //$this->logger->info("Sync already scheduled with correct interval ({$interval}), not rescheduling");
                return;
            }

            $this->logger->info("Interval changed from {$current_interval} to {$interval}, rescheduling");
        }

        // Clear existing and reschedule
        $this->clear_scheduled_sync();

        // Schedule for next interval period, not immediately
        $intervals = wp_get_schedules();
        $interval_seconds = $intervals[$interval]['interval'] ?? 3600;
        $next_run = time() + $interval_seconds;

        $scheduled = wp_schedule_event($next_run, $interval, $this->get_sync_hook());
        $this->logger->info("Scheduled sync for: " . date('Y-m-d H:i:s', $next_run) . " with interval: {$interval}");
    }


    /**
     * Clear scheduled sync
     */
    public function clear_scheduled_sync(): void
    {
        self::force_clear_scheduled_hook($this->get_sync_hook());
    }

    /**
     * Get next scheduled run time
     */
    public function get_next_scheduled_run(): ?int
    {
        $timestamp = wp_next_scheduled($this->get_sync_hook());
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
            'next_run_formatted' => $next_run ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run) : null,
            'is_scheduled' => !empty($next_run)
        ];
    }


    private function get_synchronizer_key(): string
    {
        return  $this->synchronizer->get_sync_type_key();
    }
    
    public function get_sync_hook(): string
    {
        return $this->get_synchronizer_key() . '_sync';
    }

    public function get_timeout_watchdog_hook(): string
    {
        return self::TIMEOUT_WATCHDOG_HOOK;
    }

    public function check_for_sync_timeouts(): void
    {
        $this->log()->debug('Checking for sync timeouts');
        $timeout_minutes = $this->sync_settings->settings()->get_scoped_option("sync_timeout_minutes", 10); //get_option(self::SYNC_TIMEOUT_MINUTES_KEY, 10);
        $syncType = $this->synchronizer->get_sync_type_key();
        $timed_out_syncs = $this->sync_service->get_timed_out_syncs_for_type($timeout_minutes, $syncType);

        foreach ($timed_out_syncs as $sync) {
            $this->log()->warning("Timeout Watchdog: Marking stuck sync #{$sync->get_id()} ({$sync->get_sync_type()}) as failed due to timeout");
            $elapsed_seconds = time() - $sync->get_updated_at()->getTimestamp();
            $elapsed_minutes = round((float)($elapsed_seconds / 60), 1);

            $errorMessage = "Sync timed out after {$elapsed_minutes} minutes (limit: {$timeout_minutes} minutes)";

            $this->sync_service->set_sync_failed($sync->get_id(), $errorMessage);

            $this->log()->warning("Timeout Watchdog: Marked stuck sync #{$sync->get_id()} ({$sync->get_sync_type()}) as failed due to timeout ({$elapsed_minutes}min elapsed, {$timeout_minutes}min limit)");
        }
    }
}