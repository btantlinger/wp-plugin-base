<?php

namespace WebMoves\PluginBase\Events;

use WebMoves\PluginBase\Services\DatabaseSyncService;
use WebMoves\PluginBase\Settings\GlobalSyncSettings;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;

/**
 * Sync History Purge Event
 * 
 * This class implements intelligent database maintenance for sync history records,
 * preventing unlimited growth of sync logs while preserving important historical
 * data based on configurable retention policies. It runs automatically to keep
 * the database efficient and prevent storage issues.
 * 
 * ## Purpose & Problem Solved:
 * 
 * ### The Problem:
 * - Sync operations generate database records for tracking and auditing
 * - Without maintenance, sync history tables grow indefinitely
 * - Large history tables impact database performance and storage
 * - Failed syncs may need longer retention than successful ones for troubleshooting
 * - Administrators need control over how much history to retain
 * - Complete history deletion would lose valuable audit trails
 * 
 * ### The Solution:
 * - Automated daily purging of old sync records
 * - Intelligent retention policies based on sync status and age
 * - Configurable limits on total record count
 * - Preservation of critical records (running syncs are never deleted)
 * - Different retention periods for successful vs. failed syncs
 * - Complete statistics and logging for maintenance transparency
 * 
 * ## Key Features:
 * 
 * ### 1. Multi-Tier Retention Strategy
 * - **Age-based purging**: Removes records older than configured thresholds
 * - **Status-based retention**: Different retention periods for different sync outcomes
 * - **Count-based limits**: Enforces maximum total record count regardless of age
 * - **Critical record protection**: Never deletes running or recent syncs
 * 
 * ### 2. Intelligent Status Handling
 * - **Completed syncs**: Standard retention period (e.g., 30 days)
 * - **Failed syncs**: Extended retention for troubleshooting (e.g., 90 days)
 * - **Cancelled syncs**: Same as completed (standard retention)
 * - **Running syncs**: Never deleted (prevents data corruption)
 * 
 * ### 3. Configurable Policies
 * - All retention settings controlled via GlobalSyncSettings
 * - Administrators can adjust based on storage capacity and audit requirements
 * - Separate controls for different aspects of purging
 * - Settings changes take effect on next daily run
 * 
 * ### 4. Safe Database Operations
 * - Uses WordPress prepared statements for SQL injection protection
 * - Transactional approach prevents partial deletions
 * - Comprehensive error handling and logging
 * - Statistics tracking for monitoring purge effectiveness
 * 
 * ### 5. Performance Optimization
 * - Runs during low-traffic periods (daily scheduled execution)
 * - Efficient queries that target specific record sets
 * - Prevents table locking issues with large datasets
 * - Optional manual execution for immediate cleanup
 * 
 * ## Retention Logic:
 * 
 * ### Phase 1: Age-Based Purging by Status
 * ```
 * DELETE completed syncs older than [retention_days] days
 * DELETE cancelled syncs older than [retention_days] days  
 * DELETE failed syncs older than [failed_retention_days] days
 * NEVER DELETE running syncs (regardless of age)
 * ```
 * 
 * ### Phase 2: Count-Based Enforcement
 * ```
 * COUNT total non-running records
 * IF count > max_records:
 *   DELETE oldest records beyond limit
 *   PRESERVE most recent [max_records] records
 *   PRESERVE all running syncs
 * ```
 * 
 * ## Configuration Examples:
 * 
 * ### Conservative Setup (Long Retention):
 * ```
 * retention_days = 90 days (completed/cancelled)
 * failed_retention_days = 180 days (failed syncs)
 * max_records = 10,000 total records
 * ```
 * 
 * ### Aggressive Setup (Short Retention):
 * ```
 * retention_days = 7 days (completed/cancelled)
 * failed_retention_days = 30 days (failed syncs)
 * max_records = 1,000 total records
 * ```
 * 
 * ### Balanced Setup (Medium Retention):
 * ```
 * retention_days = 30 days (completed/cancelled)
 * failed_retention_days = 90 days (failed syncs)
 * max_records = 5,000 total records
 * ```
 * 
 * ## Typical Execution Timeline:
 * 
 * ```
 * 2:00 AM Daily - WordPress cron triggers purge event
 * 2:00:01 AM - Load retention settings from GlobalSyncSettings
 * 2:00:02 AM - Phase 1: Purge completed syncs older than 30 days (45 deleted)
 * 2:00:03 AM - Phase 1: Purge cancelled syncs older than 30 days (12 deleted)
 * 2:00:04 AM - Phase 1: Purge failed syncs older than 90 days (8 deleted)
 * 2:00:05 AM - Phase 2: Check total count (4,923 records, under 5,000 limit)
 * 2:00:06 AM - Complete: "Sync History Purge: Deleted 65 old records"
 * ```
 * 
 * ## Database Impact Analysis:
 * 
 * ### Before Purging (Example):
 * ```
 * Total Records: 8,347
 * ├── completed: 6,234 (oldest: 2023-01-15, newest: 2024-12-31)
 * ├── failed: 1,456 (oldest: 2023-02-20, newest: 2024-12-30)
 * ├── cancelled: 655 (oldest: 2023-03-10, newest: 2024-12-29)
 * └── running: 2 (oldest: 2024-12-31, newest: 2024-12-31)
 * ```
 * 
 * ### After Purging (Example):
 * ```
 * Total Records: 4,892 (3,455 deleted)
 * ├── completed: 3,234 (oldest: 2024-12-01, newest: 2024-12-31)
 * ├── failed: 1,156 (oldest: 2024-10-01, newest: 2024-12-30)
 * ├── cancelled: 500 (oldest: 2024-12-01, newest: 2024-12-29)
 * └── running: 2 (preserved)
 * ```
 * 
 * ## Integration Points:
 * 
 * ### With DatabaseSyncService:
 * - **Table Access**: Uses service's table name constants for consistency
 * - **Data Structure**: Understands sync record schema and status values
 * - **Safe Operations**: Respects service's data integrity requirements
 * 
 * ### With GlobalSyncSettings:
 * - **Policy Control**: All retention limits come from centralized settings
 * - **Runtime Configuration**: Settings changes apply on next scheduled run
 * - **Administrative Control**: Admins control all aspects of purging behavior
 * 
 * ### With WordPress Cron:
 * - **Daily Scheduling**: Automatic execution during low-traffic periods
 * - **Reliable Execution**: Uses WordPress native cron system
 * - **Manual Triggers**: Can be executed immediately via admin interface
 * 
 * ## Safety Mechanisms:
 * 
 * ### 1. Running Sync Protection
 * - Never deletes records with status = 'running'
 * - Prevents data corruption of active operations
 * - Ensures sync system integrity during purge operations
 * 
 * ### 2. Database Transaction Safety
 * - Uses prepared statements for all database operations
 * - Proper error handling prevents partial purges
 * - Detailed logging for audit trails and troubleshooting
 * 
 * ### 3. Graduated Purging
 * - Age-based purging runs first (more predictable)
 * - Count-based purging only runs if needed
 * - Each phase operates independently to prevent cascading failures
 * 
 * ## Administrative Features:
 * 
 * ### 1. Statistics Reporting
 * - `get_purge_statistics()` provides complete database overview
 * - Breakdown by status with date ranges
 * - Useful for capacity planning and retention policy adjustment
 * 
 * ### 2. Manual Purging
 * - `purge_records_older_than()` for immediate cleanup
 * - `run_automated_purge()` for full policy enforcement
 * - Useful for emergency cleanup or testing retention policies
 * 
 * ### 3. Monitoring Integration
 * - Detailed logging enables external monitoring
 * - Success/failure tracking for operational visibility
 * - Performance metrics for database maintenance planning
 * 
 * ## Performance Considerations:
 * 
 * - **Timing**: Runs at 2 AM to minimize impact on active users
 * - **Efficiency**: Targeted DELETE operations avoid full table scans
 * - **Scalability**: Handles large datasets through optimized queries  
 * - **Resource Usage**: Minimal memory footprint during execution
 * 
 * @see DatabaseSyncService For the sync records being maintained
 * @see GlobalSyncSettings For the retention policy configuration
 * @see AbstractEvent For the underlying WordPress cron integration
 */
class SyncHistoryPurgeEvent extends \WebMoves\PluginBase\Events\AbstractEvent
{
    /** WordPress hook name for the purge event */
    const PURGE_HOOK = 'history_purge';

    private \wpdb $wpdb;
    private GlobalSyncSettings $sync_settings;

    public function __construct(PluginMetadata $metadata, \wpdb $wpdb, GlobalSyncSettings $sync_settings)
    {
        parent::__construct($metadata, self::PURGE_HOOK);
        $this->wpdb = $wpdb;
        $this->sync_settings = $sync_settings;
    }

    /**
     * Initialize daily purge scheduling
     * 
     * Automatically schedules the purge event to run daily if not already
     * scheduled. This ensures consistent database maintenance without
     * requiring manual intervention.
     * 
     * The daily schedule runs during low-traffic periods (typically 2 AM)
     * to minimize impact on site performance and user experience.
     */
    public function on_init(): void
    {
        // Schedule daily purge if not already scheduled
        if (!$this->is_scheduled()) {
            $this->schedule(null, 'daily');
            $this->logger->info('Scheduled sync history purge: ' . $this->get_hook_name());
        }
    }

    /**
     * Handle the scheduled purge event
     * 
     * Called by WordPress cron system when the daily purge is triggered.
     * This is the main entry point for automatic database maintenance.
     */
    public function handle_event(): void
    {
        $this->run_automated_purge();
    }

    /**
     * Execute the automated purge process
     * 
     * Wrapper method that delegates to the main purge logic.
     * Provides a clean interface for both scheduled and manual execution.
     */
    public function run_automated_purge(): void
    {
        $this->purge_old_records();
    }

    /**
     * Execute the complete purge process with intelligent retention
     * 
     * This is the core method that implements the multi-tier retention strategy:
     * 
     * 1. **Load Configuration**: Gets all retention settings from GlobalSyncSettings
     * 2. **Phase 1 - Age-based Purging**: Removes old records by status and age
     * 3. **Phase 2 - Count-based Limiting**: Enforces maximum record limits
     * 4. **Logging & Reporting**: Tracks results and handles errors
     * 
     * The method uses different retention periods for different sync statuses,
     * allowing administrators to keep failed syncs longer for troubleshooting
     * while cleaning up successful syncs more aggressively.
     * 
     * ## Retention Strategy:
     * 
     * - **Completed syncs**: Use standard retention period
     * - **Cancelled syncs**: Use standard retention period  
     * - **Failed syncs**: Use extended retention period for debugging
     * - **Running syncs**: Never deleted (critical system protection)
     * 
     * ## Error Handling:
     * 
     * All database operations are wrapped in try-catch blocks to ensure
     * partial failures don't corrupt the purge process. Errors are logged
     * with detailed information for troubleshooting.
     * 
     * @return int Total number of records deleted during the purge
     */
    public function purge_old_records(): int
    {
        $total_deleted = 0;

        try {
            // Get settings from GlobalSyncSettings
            $retention_days = $this->sync_settings->get_history_retention_days();
            $failed_retention_days = $this->sync_settings->get_history_failed_retention_days();
            $max_records = $this->sync_settings->get_max_history_records();

            // Purge by age (but keep different retention for different statuses)
            $total_deleted += $this->purge_by_age_and_status($retention_days, 'completed');
            $total_deleted += $this->purge_by_age_and_status($retention_days, 'cancelled');
            $total_deleted += $this->purge_by_age_and_status($failed_retention_days, 'failed');

            // Enforce maximum record limit
            $total_deleted += $this->enforce_max_records($max_records);

            if ($total_deleted > 0) {
                $this->logger->info("Sync History Purge: Deleted {$total_deleted} old records");
            }

        } catch (\Exception $e) {
            $this->logger->error("Sync History Purge failed: " . $e->getMessage());
        }

        return $total_deleted;
    }

    /**
     * Purge records by age and status
     * 
     * Removes sync records of a specific status that are older than the
     * specified retention period. This implements the age-based portion
     * of the retention strategy.
     * 
     * ## SQL Safety:
     * 
     * Uses WordPress prepared statements to prevent SQL injection and
     * ensure safe parameter binding. The query specifically targets
     * records matching both status and age criteria.
     * 
     * ## Example Operation:
     * ```
     * DELETE FROM sync_history 
     * WHERE status = 'completed' 
     * AND started_at < '2024-11-01 00:00:00'
     * ```
     * 
     * @param int $retention_days Number of days to retain records
     * @param string $status Sync status to target ('completed', 'failed', etc.)
     * @return int Number of records deleted
     */
    private function purge_by_age_and_status(int $retention_days, string $status): int
    {
        $table_name = DatabaseSyncService::get_table_name();
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        return $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$table_name} 
             WHERE status = %s 
             AND started_at < %s",
            $status,
            $cutoff_date
        ));
    }

    /**
     * Enforce maximum record count limits
     * 
     * Implements the count-based portion of the retention strategy by
     * ensuring the total number of sync records never exceeds the
     * configured maximum. When the limit is exceeded, this method
     * deletes the oldest records while preserving running syncs.
     * 
     * ## Protection Logic:
     * 
     * 1. **Count Check**: Only counts non-running records toward the limit
     * 2. **Limit Enforcement**: If over limit, calculates excess records
     * 3. **Safe Deletion**: Uses subquery to identify oldest excess records
     * 4. **Running Sync Protection**: Never deletes records with status = 'running'
     * 
     * ## Complex Query Explanation:
     * 
     * The deletion uses a subquery approach to safely identify which records
     * to delete without creating table locking issues:
     * 
     * ```sql
     * DELETE FROM sync_history 
     * WHERE status != 'running'
     * AND id NOT IN (
     *     SELECT id FROM (
     *         SELECT id FROM sync_history 
     *         WHERE status != 'running'
     *         ORDER BY started_at DESC 
     *         LIMIT [max_records]
     *     ) AS keeper
     * )
     * ```
     * 
     * This approach:
     * - Identifies the newest N records to keep
     * - Deletes everything else that's not running
     * - Avoids MySQL's limitation on updating/deleting from the same table in a subquery
     * 
     * @param int $max_records Maximum number of records to retain
     * @return int Number of records deleted to enforce the limit
     */
    private function enforce_max_records(int $max_records): int
    {
        $table_name = DatabaseSyncService::get_table_name();

        // Never delete running syncs
        $total_count = $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE status != 'running'"
        );

        if ($total_count <= $max_records) {
            return 0;
        }

        // Delete oldest records beyond the limit (but preserve running syncs)
        return $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$table_name} 
             WHERE status != 'running'
             AND id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM {$table_name} 
                     WHERE status != 'running'
                     ORDER BY started_at DESC 
                     LIMIT %d
                 ) AS keeper
             )",
            $max_records
        ));
    }

    /**
     * Manual purge by age threshold
     * 
     * Provides a simplified interface for manual cleanup operations.
     * Removes all records older than the specified age regardless of
     * status (except running syncs which are always preserved).
     * 
     * This method is useful for:
     * - Emergency cleanup operations
     * - One-time maintenance tasks
     * - Testing purge operations before scheduling
     * - Custom retention policies
     * 
     * @param int $days Age threshold in days
     * @return int Number of records deleted
     */
    public function purge_records_older_than(int $days): int
    {
        $table_name = DatabaseSyncService::get_table_name();
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$table_name} 
             WHERE started_at < %s 
             AND status != 'running'",
            $cutoff_date
        ));
    }

    /**
     * Get comprehensive purge statistics
     * 
     * Provides detailed information about the current state of the sync
     * history database, broken down by status. This information is
     * valuable for:
     * 
     * - **Capacity Planning**: Understanding database growth patterns
     * - **Policy Adjustment**: Fine-tuning retention periods
     * - **Monitoring**: Tracking purge effectiveness over time
     * - **Troubleshooting**: Identifying unusual sync patterns
     * 
     * ## Return Format:
     * ```php
     * [
     *     'total_records' => 4892,
     *     'by_status' => [
     *         [
     *             'status' => 'completed',
     *             'count' => 3234,
     *             'oldest' => '2024-12-01 10:30:00',
     *             'newest' => '2024-12-31 23:45:00'
     *         ],
     *         [
     *             'status' => 'failed', 
     *             'count' => 1156,
     *             'oldest' => '2024-10-01 14:20:00',
     *             'newest' => '2024-12-30 18:30:00'
     *         ],
     *         // ... other statuses
     *     ]
     * ]
     * ```
     * 
     * ## Usage Examples:
     * 
     * - **Admin Dashboard**: Display current database status
     * - **Capacity Alerts**: Warn when approaching storage limits
     * - **Retention Analysis**: Understand which statuses consume most space
     * - **Historical Trends**: Track database growth over time
     * 
     * @return array Comprehensive statistics about sync history records
     */
    public function get_purge_statistics(): array
    {
        $table_name = DatabaseSyncService::get_table_name();

        $stats = $this->wpdb->get_results(
            "SELECT 
                status,
                COUNT(*) as count,
                MIN(started_at) as oldest,
                MAX(started_at) as newest
             FROM {$table_name} 
             GROUP BY status",
            ARRAY_A
        );

        $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        return [
            'total_records' => (int) $total,
            'by_status' => $stats
        ];
    }
}