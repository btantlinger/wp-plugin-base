<?php

namespace WebMoves\PluginBase\Synchronizers;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Contracts\Synchronizers\Sync;
use WebMoves\PluginBase\Enums\SyncStatus;
use WebMoves\PluginBase\Concerns\HasLogger;
use WebMoves\PluginBase\Synchronizers\BasicSync;


/**
 *
 *
 *
 */

/*

'database' => [
	'version' => '1.0.6',
	'delete_tables_on_uninstall' => true,
	'delete_options_on_uninstall' => true,
	'tables' => [
		DatabaseSyncService::TABLE_NAME => "CREATE TABLE {table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        sync_type varchar(50) NOT NULL,
        status varchar(20) NOT NULL,
        started_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        completed_at datetime DEFAULT NULL,
        duration_seconds int DEFAULT NULL,
        total_items int DEFAULT 0,
        synced_items int DEFAULT 0,
        failed_items int DEFAULT 0,
        error_message text DEFAULT NULL,
        sync_details json DEFAULT NULL,
        triggered_by varchar(50) DEFAULT 'manual',
        PRIMARY KEY (id),
        KEY status (status),
        KEY started_at (started_at),
        KEY sync_type (sync_type),
        KEY purge_lookup (started_at, status),
        KEY status_date (status, started_at)
    ) {charset_collate};"
	]
],


 */

class DatabaseSyncService implements SyncService
{
    const TABLE_NAME = 'sync_history';

    private string $table_name;

    private \wpdb $wpdb;

    use HasLogger;

	public static function get_table_definition(?string $table_name = null): string
	{
		$table_name = $table_name ?? static::get_table_name();
		return  "CREATE TABLE {table_name} (
		        id bigint(20) NOT NULL AUTO_INCREMENT,
		        sync_type varchar(50) NOT NULL,
		        status varchar(20) NOT NULL,
		        started_at datetime NOT NULL,
		        updated_at datetime NOT NULL,
		        completed_at datetime DEFAULT NULL,
		        duration_seconds int DEFAULT NULL,
		        total_items int DEFAULT 0,
		        synced_items int DEFAULT 0,
		        failed_items int DEFAULT 0,
		        error_message text DEFAULT NULL,
		        sync_details json DEFAULT NULL,
		        triggered_by varchar(50) DEFAULT 'manual',
		        PRIMARY KEY (id),
		        KEY status (status),
		        KEY started_at (started_at),
		        KEY sync_type (sync_type),
		        KEY purge_lookup (started_at, status),
		        KEY status_date (status, started_at)
		    ) {charset_collate};";

	}

    public function __construct()
    {
		global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = static::get_table_name();
    }

    public static function get_table_name(bool $include_prefix = true): string
    {
		if (!$include_prefix) {
			return self::TABLE_NAME;
		}
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    public function get_last_finished_sync_for_type(string $syncType): ?Sync
    {

        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
             WHERE sync_type = %s 
             AND status IN (%s, %s, %s) 
             ORDER BY updated_at DESC 
             LIMIT 1",
                $syncType,
                SyncStatus::COMPLETED->value,
                SyncStatus::FAILED->value,
                SyncStatus::CANCELLED->value
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return $this->create_sync_from_row($row);
    }

    public function get_last_completed_sync_for_type(string $syncType): ?Sync
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
             WHERE sync_type = %s 
             AND status = %s
             ORDER BY completed_at DESC, updated_at DESC 
             LIMIT 1",
                $syncType,
                SyncStatus::COMPLETED->value
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return $this->create_sync_from_row($row);
    }


    public function sync_started(string $syncType, string $triggeredBy = 'manual', array $details = []): int
    {
        $this->wpdb->insert(
            $this->table_name,
            [
                'sync_type' => $syncType,
                'status' => SyncStatus::RUNNING->value,
                'synced_items' => 0,
                'failed_items' => 0,
                'duration_seconds' => 0,
                'started_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'sync_details' => json_encode(array_merge($details, ['triggered_by' => $triggeredBy])),
                'triggered_by' => $triggeredBy
            ],
            ['%s', '%s',  '%d', '%d', '%s', '%s', '%s', '%s']
        );
        return $this->wpdb->insert_id;
    }

    public function set_total_items_to_sync(int $id, int $totalItemsToSync) : void
    {
        $this->ensure_sync_exists($id);

        $this->wpdb->update(
            $this->table_name,
            [
                'total_items' => $totalItemsToSync,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id],
            ['%d', '%s'],
            ['%d']
        );
    }

    public function get_sync(int $id): ?Sync
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return $this->create_sync_from_row($row);
    }

    public function get_running_sync_for_type(string $syncType): ?Sync
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE sync_type = %s AND status = %s ORDER BY started_at DESC LIMIT 1",
                $syncType,
                SyncStatus::RUNNING->value
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return $this->create_sync_from_row($row);
    }

    public function set_sync_status(int $id, SyncStatus $status): void
    {
        $this->ensure_sync_exists($id);

        $current_time = current_time('mysql');

        if ($status === SyncStatus::COMPLETED) {
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "UPDATE {$this->table_name} 
                 SET status = %s, 
                     updated_at = %s,
                     completed_at = %s,
                     duration_seconds = UNIX_TIMESTAMP(%s) - UNIX_TIMESTAMP(started_at)
                 WHERE id = %d",
                    $status->value,
                    $current_time,
                    $current_time,
                    $current_time,
                    $id
                )
            );
        } else {
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "UPDATE {$this->table_name} 
                 SET status = %s, 
                     updated_at = %s,
                     duration_seconds = UNIX_TIMESTAMP(%s) - UNIX_TIMESTAMP(started_at)
                 WHERE id = %d",
                    $status->value,
                    $current_time,
                    $current_time,
                    $id
                )
            );
        }
    }

    public function set_sync_complete(int $id): void
    {
        $this->set_sync_status($id, SyncStatus::COMPLETED);
    }

    public function set_sync_failed(int $id, string $errorMessage): void
    {
        $this->ensure_sync_exists($id);

        // Get current details and add error message
        $sync = $this->get_sync($id);
        $details = $sync ? $sync->get_details() : [];
        $details['error_message'] = $errorMessage;

        $this->wpdb->update(
            $this->table_name,
            [
                'status' => SyncStatus::FAILED->value,
                'completed_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'error_message' => $errorMessage,
                'sync_details' => json_encode($details)
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
    }

    public function increment_synced(int $id, int $increment): void
    {
        $this->ensure_sync_exists($id);

        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_name} 
             SET synced_items = synced_items + %d, 
                 updated_at = %s,
                 duration_seconds = UNIX_TIMESTAMP(%s) - UNIX_TIMESTAMP(started_at)
             WHERE id = %d",
                $increment,
                current_time('mysql'),
                current_time('mysql'),
                $id
            )
        );
    }

    public function increment_failed(int $id, int $increment): void
    {
        $this->ensure_sync_exists($id);
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_name} 
             SET failed_items = failed_items + %d, 
                 updated_at = %s,
                 duration_seconds = UNIX_TIMESTAMP(%s) - UNIX_TIMESTAMP(started_at)
             WHERE id = %d",
                $increment,
                current_time('mysql'),
                current_time('mysql'),
                $id
            )
        );
    }


    public function set_synced(int $id, int $total): void
    {
        $this->ensure_sync_exists($id);

        $current_time = current_time('mysql');

        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_name} 
             SET synced_items = %d, 
                 updated_at = %s,
                 duration_seconds = UNIX_TIMESTAMP(%s) - UNIX_TIMESTAMP(started_at)
             WHERE id = %d",
                $total,
                $current_time,
                $current_time,
                $id
            )
        );
    }

    public function set_failed(int $id, int $total): void
    {
        $this->ensure_sync_exists($id);
        $current_time = current_time('mysql');

        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_name} 
             SET failed_items = %d, 
                 updated_at = %s,
                 duration_seconds = UNIX_TIMESTAMP(%s) - UNIX_TIMESTAMP(started_at)
             WHERE id = %d",
                $total,
                $current_time,
                $current_time,
                $id
            )
        );
    }


    public function get_recent_syncs(int $limit = 10): array
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY started_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return array_map([$this, 'create_sync_from_row'], $rows);
    }

    public function get_timed_out_syncs_for_type(int $timeoutMinutes, string $syncType): array
    {
        $timeout_seconds = $timeoutMinutes * 60;
        $cutoff_time = date('Y-m-d H:i:s', time() - $timeout_seconds);

        // Find running syncs of this type that haven't been updated within the timeout period
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
             WHERE status = %s 
             AND sync_type = %s
             AND updated_at < %s
             ORDER BY updated_at ASC",
                SyncStatus::RUNNING->value,
                $syncType,
                $cutoff_time
            ),
            ARRAY_A
        );

        return array_map([$this, 'create_sync_from_row'], $rows);
    }

    public function get_timed_out_syncs(int $timeoutMinutes): array
    {
        $timeout_seconds = $timeoutMinutes * 60;
        $cutoff_time = date('Y-m-d H:i:s', time() - $timeout_seconds);

        // Find running syncs of this type that haven't been updated within the timeout period
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
             WHERE status = %s 
             AND updated_at < %s
             ORDER BY updated_at ASC",
                SyncStatus::RUNNING->value,
                $cutoff_time
            ),
            ARRAY_A
        );

        return array_map([$this, 'create_sync_from_row'], $rows);
    }


    /**
     * Ensure sync exists, throw exception if not
     */
    private function ensure_sync_exists(int $id): void
    {
        $exists = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d", $id)
        );

        if (!$exists) {
            throw new \Exception("Sync $id does not exist in the database.");
        }
    }

    /**
     * Create Sync object from database row
     */
    private function create_sync_from_row(array $row): Sync
    {
        return new BasicSync($row);
    }
}