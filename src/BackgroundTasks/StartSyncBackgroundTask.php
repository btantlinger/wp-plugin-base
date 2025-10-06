<?php

namespace WebMoves\PluginBase\BackgroundTasks;

use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

/**
 * Background Sync Task Executor
 * 
 * This class serves as the bridge between scheduled sync events and actual synchronizer execution.
 * It provides proper background processing for synchronization operations, ensuring they run
 * without blocking the user interface or impacting frontend performance.
 * 
 * ## Key Features:
 * 
 * ### 1. Multi-Synchronizer Support
 * - Manages multiple synchronizer types (stock, product, price syncs, etc.)
 * - Routes sync requests to the appropriate synchronizer based on sync type key
 * - Maintains a registry of available synchronizers for runtime lookup
 * 
 * ### 2. Background Processing Architecture
 * - Executes syncs via WordPress cron system (non-blocking)
 * - Prevents duplicate executions with built-in scheduling checks
 * - Handles long-running operations with proper memory and time limits
 * - Spawns background processes for immediate execution
 * 
 * ### 3. Event-Driven Notifications
 * - Fires completion events when sync operations finish successfully
 * - Fires failure events when sync operations encounter errors
 * - Provides structured event data for other components to react to
 * - Enables the scheduling system to reschedule based on completion
 * 
 * ### 4. Resource Management
 * - Allocates increased memory limit (1024M) for data-intensive operations
 * - Sets unlimited execution time for long-running syncs
 * - Prepares environment for handling large datasets
 * 
 * ### 5. Error Handling & Logging
 * - Comprehensive logging throughout the sync lifecycle
 * - Graceful error handling with detailed error reporting
 * - Tracks sync duration and performance metrics
 * - Provides visibility into background operation status
 * 
 * ## Architecture Flow:
 * 
 * ### 1. Sync Initiation:
 * ```
 * ScheduledSyncEvent → StartSyncBackgroundTask::run_sync() → WordPress Cron Schedule
 * ```
 * 
 * ### 2. Background Execution:
 * ```
 * WordPress Cron → handle_scheduled_work() → execute_background_work() → synchronizer.sync()
 * ```
 * 
 * ### 3. Event Broadcasting:
 * ```
 * Sync Completion → Fire completion/failure hooks → ScheduledSyncEvent reschedules
 * ```
 * 
 * ## Integration Points:
 * 
 * ### With ScheduledSyncEvent:
 * - **Triggered by**: `ScheduledSyncEvent::handle_event()` calls `run_sync()`
 * - **Notifies back**: Via completion/failure hooks for rescheduling
 * - **Maintains**: Proper interval timing through event-driven communication
 * 
 * ### With Synchronizers:
 * - **Delegates to**: Individual synchronizer `sync()` methods
 * - **Provides context**: Passes `triggered_by` parameter for tracking
 * - **Isolates**: Each sync type in its own background execution context
 * 
 * ### With WordPress Cron:
 * - **Schedules**: Single-use cron events for immediate execution
 * - **Executes**: Via standard WordPress cron hook system
 * - **Spawns**: Background cron processes when not in cron context
 * 
 * ## Typical Execution Timeline:
 * 
 * ```
 * 10:00:00 AM - ScheduledSyncEvent triggers run_sync('stock_sync', 'schedule')
 * 10:00:00 AM - WordPress cron event scheduled for immediate execution
 * 10:00:01 AM - spawn_cron() triggered to run background process
 * 10:00:02 AM - handle_scheduled_work() begins execution
 * 10:00:02 AM - Memory limit set to 1024M, time limit removed
 * 10:00:03 AM - DuffellsStockSynchronizer::sync() begins processing
 * 10:05:30 AM - Stock sync completes (327 items processed)
 * 10:05:30 AM - Completion event fired: 'duffells_sync_start_sync_completed'
 * 10:05:31 AM - ScheduledSyncEvent receives completion, reschedules for 10:35:30
 * ```
 * 
 * ## Error Scenarios:
 * 
 * ### Sync Validation Errors:
 * - Invalid sync type → logs error, no background execution
 * - Already scheduled → throws exception to prevent duplicates
 * - Missing synchronizer → logs error, background task completes with error
 * 
 * ### Runtime Errors:
 * - API failures → synchronizer handles, task completes normally
 * - Memory exhaustion → background task fails, failure event fired
 * - Timeout → WordPress kills process, failure event may not fire
 * 
 * ## Event Hooks:
 * 
 * ### Completion Hook: `{prefix}_start_sync_completed`
 * **Parameters**: `(string $sync_type, string $triggered_by, mixed $result)`
 * 
 * ### Failure Hook: `{prefix}_start_sync_failed`  
 * **Parameters**: `(string $sync_type, string $triggered_by, Exception $e)`
 * 
 * ## Performance Considerations:
 * 
 * - **Memory**: 1024M allocation handles large product catalogs
 * - **Time**: Unlimited execution prevents timeout on large datasets
 * - **Concurrency**: Single synchronizer instance prevents resource conflicts
 * - **Scheduling**: Prevents overlapping executions of same sync type
 * 
 * @see ScheduledSyncEvent For the scheduling logic that triggers this task
 * @see AbstractBackgroundTask For the underlying background processing framework
 * @see SchedulableSynchronizer For the sync implementations that get executed
 */
class StartSyncBackgroundTask extends \WebMoves\PluginBase\BackgroundTasks\AbstractBackgroundTask
{
    const START_SYNC_HOOK = 'start_sync';

    /**
     * Registry of available synchronizers indexed by sync type key
     * Enables runtime lookup of the appropriate synchronizer for execution
     */
    private array $synchronizers;

    public function __construct(PluginMetadata $metadata, array $synchronizers)
    {
        parent::__construct($metadata, self::START_SYNC_HOOK);
        $this->synchronizers = [];
        foreach ($synchronizers as $synchronizer) {
            $this->synchronizers[$synchronizer->get_sync_type_key()] = $synchronizer;
        }
    }

    public function get_synchronizers(): array
    {
        return $this->synchronizers;
    }

    /**
     * Initiate a background sync operation
     * 
     * This is the main entry point for starting sync operations. It validates
     * that no sync is currently scheduled, then delegates to the parent's run()
     * method to handle WordPress cron scheduling and background execution.
     * 
     * @param string $sync_type_key The type of sync to run (e.g., 'stock_sync')
     * @param string $triggered_by How the sync was initiated ('manual', 'schedule', 'api')
     * @return bool True if successfully scheduled, false otherwise
     * @throws \Exception If a sync is already scheduled for this task
     */
    public function run_sync(string $sync_type_key, string $triggered_by = 'manual'): bool
    {
        if($this->is_scheduled()) {
            throw new \Exception('Sync is already scheduled');
        }

        return $this->run($sync_type_key, $triggered_by);
    }


    /**
     * Execute the actual sync work in the background
     * 
     * This method runs in the WordPress cron context and performs the actual
     * synchronization by delegating to the appropriate synchronizer. It handles
     * validation, error recovery, and ensures the sync completes properly.
     * 
     * The execution flow:
     * 1. Validate sync type and retrieve synchronizer
     * 2. Extract trigger context from arguments
     * 3. Execute synchronizer's sync() method
     * 4. Handle any exceptions that occur during sync
     * 
     * @param mixed ...$args Arguments passed from the scheduled event
     *                       [0] = sync_type_key (string)
     *                       [1] = triggered_by (string, optional)
     */
    protected function execute_background_work(...$args): void
    {
        if(isset($args[0]) && !empty($this->synchronizers[$args[0]])) {
            $synchronizer = $this->synchronizers[$args[0]];
            $this->log()->debug('Sync: Starting background sync for ' . $synchronizer->get_name());

            $triggered_by = isset($args[1]) ? $args[1] : 'manual';

            try {
                // Run the sync command - delegate to the synchronizer
                $synchronizer->sync($triggered_by);
            } catch (\Exception $e) {
                $this->log()->error($e->getMessage());
            }
        } else {
            $this->log()->error('Sync: Invalid sync type provided');
        }
    }

    /**
     * Configure memory limit for sync operations
     * 
     * Sets a high memory limit to handle large datasets typical in
     * product/inventory synchronization operations. This prevents
     * memory exhaustion when processing thousands of products.
     */
    protected function get_memory_limit(): string
    {
        return '1024M'; 
    }

    /**
     * Get descriptive task name for logging and identification
     */
    protected function get_task_name(): string
    {
        return 'SyncStart';
    }

    /**
     * Handle successful sync completion
     * 
     * Called when the background sync operation completes successfully.
     * Logs the completion and can be extended to perform cleanup or
     * additional processing based on sync results.
     * 
     * The parent class automatically fires the completion hook after this method,
     * which triggers ScheduledSyncEvent to reschedule the next sync.
     * 
     * @param mixed $result The result returned by the synchronizer
     * @param mixed ...$args Original arguments passed to the background task
     */
    protected function on_background_work_completed($result, ...$args): void
    {
        $sync_type = $args[0];
        $synchronizer = $this->synchronizers[$sync_type];
        $this->log()->debug("Sync completed for: {$synchronizer->get_name()}");
    }

    /**
     * Handle sync failure
     * 
     * Called when the background sync operation fails with an exception.
     * Logs the failure details and can be extended to perform error-specific
     * recovery or notification actions.
     * 
     * The parent class automatically fires the failure hook after this method,
     * which still triggers ScheduledSyncEvent to reschedule (maintaining intervals
     * even when syncs fail).
     * 
     * @param \Exception $e The exception that caused the failure
     * @param mixed ...$args Original arguments passed to the background task
     */
    protected function on_background_work_failed(\Exception $e, ...$args): void
    {
        $sync_type = $args[0];
        $synchronizer = $this->synchronizers[$sync_type];
        $this->log()->error("Sync failed for {$synchronizer->get_name()}: " . $e->getMessage());
    }
}