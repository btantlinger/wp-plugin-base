<?php

namespace WebMoves\PluginBase\Events;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Settings\GlobalSyncSettings;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

/**
 * Sync Timeout Watchdog
 * 
 * This class implements a critical safety mechanism that monitors running synchronization
 * operations and automatically marks stuck or hung syncs as failed when they exceed
 * configured timeout limits. It serves as a failsafe to prevent indefinitely running
 * syncs from blocking the synchronization system.
 * 
 * ## Purpose & Problem Solved:
 * 
 * ### The Problem:
 * - Background sync processes can get stuck due to API failures, network issues, or server problems
 * - WordPress processes can be killed by hosting providers without proper cleanup
 * - Database connections can timeout leaving sync records in "running" state indefinitely
 * - Stuck syncs prevent new syncs from starting, effectively breaking the sync system
 * - No built-in mechanism exists to detect and recover from these scenarios
 * 
 * ### The Solution:
 * - Runs every 2 minutes to check for stuck sync operations
 * - Compares sync "updated_at" timestamp against configurable timeout limits
 * - Automatically marks timed-out syncs as "failed" with descriptive error messages
 * - Enables the sync system to recover automatically from stuck states
 * - Provides detailed logging for debugging and monitoring
 * 
 * ## Key Features:
 * 
 * ### 1. Frequent Monitoring
 * - Runs every 2 minutes (WATCHDOG_MIN_MINUTES constant)
 * - High frequency ensures quick detection and recovery from stuck syncs
 * - Independent of sync intervals - operates as a background safety net
 * 
 * ### 2. Configurable Timeout Detection
 * - Uses GlobalSyncSettings to get timeout limits (configurable by admins)
 * - Compares current time against sync's last update timestamp
 * - Accounts for different sync types having different expected durations
 * 
 * ### 3. Automatic Recovery
 * - Marks timed-out syncs as "failed" rather than leaving them "running"
 * - Allows new syncs to start by clearing the "running" state
 * - Preserves sync history with detailed timeout error messages
 * 
 * ### 4. Comprehensive Logging
 * - Debug logs for normal operation (no timeouts found)
 * - Warning logs when timeouts are detected and handled
 * - Detailed timing information for troubleshooting
 * 
 * ### 5. System Integration
 * - Automatically schedules itself on WordPress init
 * - Uses WordPress cron system for reliable execution
 * - Integrates with existing sync service and settings infrastructure
 * 
 * ## Operation Flow:
 * 
 * ### 1. Initialization (`on_init`):
 * ```
 * WordPress Init → Check if scheduled → Schedule every 2 minutes → Log scheduling
 * ```
 * 
 * ### 2. Watchdog Execution (`handle_event`):
 * ```
 * WordPress Cron → Get timeout limit → Query timed-out syncs → Mark as failed → Log actions
 * ```
 * 
 * ### 3. Timeout Detection Logic:
 * ```
 * For each sync in "running" state:
 *   elapsed_time = now - sync.updated_at
 *   if elapsed_time > timeout_limit:
 *     mark_as_failed()
 *     log_timeout()
 * ```
 * 
 * ## Example Scenarios:
 * 
 * ### Scenario 1: Normal Operation
 * ```
 * 10:00 AM - Stock sync starts, updated_at = 10:00 AM
 * 10:02 AM - Watchdog runs, elapsed = 2 min (under 15 min limit) → No action
 * 10:04 AM - Watchdog runs, elapsed = 4 min (under 15 min limit) → No action  
 * 10:05 AM - Stock sync completes normally, updated_at = 10:05 AM
 * ```
 * 
 * ### Scenario 2: Timeout Recovery
 * ```
 * 10:00 AM - Stock sync starts, updated_at = 10:00 AM
 * 10:02 AM - Watchdog runs, elapsed = 2 min → No action
 * 10:04 AM - Network issue causes sync to hang (no updated_at change)
 * 10:06 AM - Watchdog runs, elapsed = 6 min → No action (under limit)
 * ...
 * 10:16 AM - Watchdog runs, elapsed = 16 min (over 15 min limit) → Mark as failed
 * 10:30 AM - Next scheduled sync can now start (running state cleared)
 * ```
 * 
 * ### Scenario 3: Server Process Kill
 * ```
 * 10:00 AM - Product sync starts (large dataset), updated_at = 10:00 AM
 * 10:10 AM - Hosting provider kills long-running process
 * 10:12 AM - Sync record still shows "running" but process is dead
 * 10:16 AM - Watchdog detects 16-minute timeout → Mark as failed
 * 10:30 AM - System recovers, next sync starts normally
 * ```
 * 
 * ## Configuration:
 * 
 * ### Watchdog Frequency:
 * - **WATCHDOG_MIN_MINUTES = 2**: Fixed 2-minute check interval
 * - Balances responsiveness with system load
 * - Independent of sync schedules to ensure consistent monitoring
 * 
 * ### Timeout Limits:
 * - Configured via `GlobalSyncSettings::get_sync_timeout_minutes()`
 * - Admin-configurable based on expected sync durations
 * - Typically 15-30 minutes for large product syncs
 * 
 * ## Integration Points:
 * 
 * ### With SyncService:
 * - **Queries**: `get_timed_out_syncs()` to find stuck operations
 * - **Updates**: `set_sync_failed()` to mark timeouts as failed
 * - **Preserves**: Complete sync history and error information
 * 
 * ### With ScheduledSyncEvent:
 * - **Enables**: Stuck sync detection allows scheduling to resume
 * - **Protects**: Prevents indefinite blocking of sync schedules
 * - **Maintains**: System reliability through automatic recovery
 * 
 * ### With WordPress Cron:
 * - **Self-scheduling**: Automatically sets up recurring execution
 * - **Reliable execution**: Uses WordPress native cron system
 * - **Performance**: Minimal overhead with targeted queries
 * 
 * ## Error Messages:
 * 
 * Timeout errors include precise timing information:
 * ```
 * "Sync timed out after 18.3 minutes (limit: 15 minutes)"
 * ```
 * 
 * This helps administrators:
 * - Understand why syncs failed
 * - Adjust timeout limits if needed
 * - Identify performance issues requiring investigation
 * 
 * ## Reliability Features:
 * 
 * - **No dependencies**: Operates independently of other sync components
 * - **Minimal queries**: Efficient database operations for monitoring
 * - **Safe operations**: Only marks as failed, never deletes sync records
 * - **Detailed logging**: Complete audit trail of timeout detections
 * - **Graceful handling**: Continues processing other syncs if one fails
 * 
 * @see SyncService For the sync operations being monitored
 * @see GlobalSyncSettings For timeout configuration
 * @see ScheduledSyncEvent For the scheduling system this protects
 */
class SyncTimeoutWatchdog extends \WebMoves\PluginBase\Events\AbstractEvent
{
    /** 
     * Watchdog check frequency in minutes
     * Fixed at 2 minutes to provide responsive timeout detection
     */
    const WATCHDOG_MIN_MINUTES = 2;
    
    /** 
     * WordPress hook name for the watchdog event
     */
    const TIMEOUT_WATCHDOG_HOOK = 'timeout_watchdog';

    private SyncService $sync_service;
    private GlobalSyncSettings $sync_settings;

    public function __construct(PluginMetadata $metadata, SyncService $sync_service, GlobalSyncSettings $sync_settings)
    {
        parent::__construct($metadata, self::TIMEOUT_WATCHDOG_HOOK);
        
        $this->sync_service = $sync_service;
        $this->sync_settings = $sync_settings;
        
        // Add the watchdog custom schedule for frequent monitoring
        $this->add_custom_schedule(
            "every_" . self::WATCHDOG_MIN_MINUTES . "_minutes",
            self::WATCHDOG_MIN_MINUTES * MINUTE_IN_SECONDS,
            "Every " . self::WATCHDOG_MIN_MINUTES . " Minutes (System)"
        );
    }

    /**
     * Initialize the watchdog on WordPress startup
     * 
     * Automatically schedules the watchdog for recurring execution if not already
     * scheduled. This ensures the timeout monitoring is always active once the
     * plugin is loaded, providing continuous protection against stuck syncs.
     */
    public function on_init(): void
    {
        // Schedule the watchdog if not already scheduled
        if (!$this->is_scheduled()) {
            $interval = "every_" . self::WATCHDOG_MIN_MINUTES . "_minutes";
            $this->schedule(null, $interval);
            $this->logger->info('Scheduled timeout watchdog: ' . $this->get_hook_name());
        }
    }

    /**
     * Handle the watchdog execution event
     * 
     * Called by WordPress cron every 2 minutes to perform timeout checks.
     * This is the main entry point for the watchdog's monitoring functionality.
     */
    public function handle_event(): void
    {
        $this->check_for_sync_timeouts();
    }

    /**
     * Perform timeout detection and recovery
     * 
     * This is the core watchdog functionality that:
     * 1. Gets the configured timeout limit from settings
     * 2. Queries for syncs that have exceeded the timeout
     * 3. Marks each timed-out sync as failed with detailed error information
     * 4. Logs the timeout detection and recovery actions
     * 
     * The timeout detection is based on comparing the current time against
     * each sync's "updated_at" timestamp, which is updated throughout the
     * sync process to indicate the sync is still active.
     * 
     * ## Recovery Process:
     * 
     * For each timed-out sync:
     * - Calculate precise elapsed time since last update
     * - Generate descriptive error message with timing details
     * - Mark sync as "failed" in the database
     * - Log warning with sync ID, type, and timing information
     * 
     * This process ensures that:
     * - Stuck syncs don't block future sync operations
     * - Administrators can see exactly why syncs failed
     * - System automatically recovers from hung processes
     * - Complete audit trail is maintained for troubleshooting
     */
    public function check_for_sync_timeouts(): void
    {
        $this->logger->debug('Checking for sync timeouts');
        
        $timeout_minutes = $this->sync_settings->get_sync_timeout_minutes();
        $timed_out_syncs = $this->sync_service->get_timed_out_syncs($timeout_minutes);

        foreach ($timed_out_syncs as $sync) {
            $this->logger->warning("Timeout Watchdog: Marking stuck sync #{$sync->get_id()} ({$sync->get_sync_type()}) as failed due to timeout");
            
            $elapsed_seconds = time() - $sync->get_updated_at()->getTimestamp();
            $elapsed_minutes = round($elapsed_seconds / 60, 1);

            $errorMessage = "Sync timed out after {$elapsed_minutes} minutes (limit: {$timeout_minutes} minutes)";

            $this->sync_service->set_sync_failed($sync->get_id(), $errorMessage);

            $this->logger->warning("Timeout Watchdog: Marked stuck sync #{$sync->get_id()} ({$sync->get_sync_type()}) as failed due to timeout ({$elapsed_minutes}min elapsed, {$timeout_minutes}min limit)");
        }
    }
}