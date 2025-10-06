<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Controllers\AbstractFormController;
use WebMoves\PluginBase\Services\DatabaseSyncService;

class ExportSyncDataController extends AbstractFormController
{
    public function __construct(PluginMetadata $metadata, FlashData $flash_data)
    {
        parent::__construct($metadata, $flash_data, 'cancel_sync', 'GET');
    }

    protected function get_required_capability(): string
    {
        return 'export';
    }

    protected function get_nonce_action(array $data): string
    {
        return 'export_sync_data';
    }

    protected function should_redirect_after_action(): bool
    {
        return false; // Don't redirect for file downloads
    }

    protected function handle_action(array $data): array
    {
        global $wpdb;

        // Generate and output CSV file
        $filename = 'sync-data-' . date('Y-m-d-H-i-s') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Type', 'Status', 'Started', 'Completed', 'Duration (sec)', 'Total Items', 'Synced', 'Failed']);

        // Get sync data from database
        $table_name = DatabaseSyncService::get_table_name();
        $results = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY started_at DESC");

        foreach ($results as $sync) {
            fputcsv($output, [
                $sync->id,
                $sync->sync_type,
                $sync->status,
                $sync->started_at,
                $sync->completed_at ?: '',
                $sync->duration_seconds ?: '',
                $sync->total_items,
                $sync->synced_items,
                $sync->failed_items
            ]);
        }

        fclose($output);
        return [];
    }
}