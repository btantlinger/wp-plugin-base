<?php

namespace WebMoves\PluginBase\Contracts\Synchronizers;

use WebMoves\PluginBase\Enums\SyncStatus;

interface SyncService
{
    /**
     * Get sync by ID - returns null if not found
     */
    public function get_sync(int $id): ?Sync;

    /**
     * Get active sync for a specific type
     */
    public function get_running_sync_for_type(string $syncType): ?Sync;

    /**
     * Retrieves the most recently completed sync operation that has reached a terminal state.
     *
     * This method searches for the latest sync operation that has finished processing,
     * regardless of whether it completed successfully, was cancelled, or failed.
     * Syncs are ordered by their completion time (most recent first).
     *
     * A sync is considered "finished" when its status returns true for the `is_finished()` method.
     * This includes syncs with these statuses:
     * - COMPLETED: Successfully processed all items
     * - CANCELLED: Manually stopped before completion
     * - FAILED: Encountered errors that prevented completion
     *
     * Running or not-started syncs are excluded as `SyncStatus::RUNNING->is_finished()`
     * and `SyncStatus::NOT_STARTED->is_finished()` both return false.
     *
     * @param $syncType string -  The type key this sync
     *
     * @return Sync|null The most recent finished sync, or null if no finished syncs exist
     *
     * @see SyncStatus::is_finished() For the logic that determines if a sync is finished
     *
     * @example
     * // Check the results of the last sync operation
     * $lastSync = SyncService::get_most_recent_finished_sync();
     * if ($lastSync && $lastSync->get_status() === SyncStatus::FAILED) {
     *     $this->logger->error('Last sync failed: ' . $lastSync->get_details()['error_message']);
     * }
     *
     * // Alternative way to check if any sync is finished
     * if ($lastSync && $lastSync->get_status()->is_finished()) {
     *     echo "Last sync completed with status: " . $lastSync->get_status()->label();
     * }
     *
     * // Display completion stats to user
     * $lastSync = SyncService::get_most_recent_finished_sync();
     * if ($lastSync) {
     *     echo "Last sync: {$lastSync->get_synced()}/{$lastSync->get_total()} items processed";
     * }
     */
    public function get_last_finished_sync_for_type(string $syncType): ?Sync;



    public function get_last_completed_sync_for_type(string $syncType): ?Sync;

    /**
     * Get recent syncs
     */
    public function get_recent_syncs(int $limit = 10): array;


    /**
     * Start a new sync operation
     */
    public function sync_started(string $syncType, string $triggeredBy = 'manual', array $details = []): int;


    public function set_total_items_to_sync(int $id, int $totalItemsToSync): void;


    /**
     * Update sync status - throws exception if sync doesn't exist
     */
    public function set_sync_status(int $id, \WebMoves\PluginBase\Enums\SyncStatus $status): void;

    /**
     * Mark sync as completed
     */
    public function set_sync_complete(int $id): void;

    /**
     * Mark sync as failed
     */
    public function set_sync_failed(int $id, string $errorMessage): void;


    /**
     * Increment synced count
     */
    public function increment_synced(int $id, int $increment): void;

    /**
     * Increment failed count
     */
    public function increment_failed(int $id, int $increment): void;


    /**
     * Increment synced count
     */
    public function set_synced(int $id, int $total): void;

    /**
     * Increment failed count
     */
    public function set_failed(int $id, int $total): void;

    /**
     * Get syncs of a specific type that have timed out based on their last update time
     *
     * Returns running syncs of the specified type that haven't been updated within
     * the specified timeout period. The caller is responsible for deciding what to
     * do with these timed out syncs.
     *
     * @param int $timeoutMinutes How long to wait before considering a sync timed out
     * @param string $syncType The sync type to check for timeouts
     * @return Sync[] Array of timed out sync objects for the specified type
     */
    public function get_timed_out_syncs_for_type(int $timeoutMinutes, string $syncType): array;

    public function get_timed_out_syncs(int $timeoutMinutes): array;

}