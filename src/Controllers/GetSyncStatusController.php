<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Controllers\AbstractAjaxController;

class GetSyncStatusController extends AbstractAjaxController
{
    private SyncService $sync_service;

    public function __construct(PluginMetadata $metadata, FlashData $flash_data, SyncService $sync_service)
    {
        $this->sync_service = $sync_service;
        parent::__construct($metadata, $flash_data, 'get_sync_status');
    }


    protected function get_required_capability(): string
    {
        return 'read';
    }

    protected function validate_request_data(array $data): array
    {
        if (!isset($data['sync_id']) || !is_numeric($data['sync_id'])) {
            return [
                'valid' => false,
                'message' => __('Invalid sync ID provided.', $this->text_domain)
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    protected function get_nonce_action(array $data): string
    {
        return 'get_sync_status_' . intval($data['sync_id']);
    }

    protected function handle_action(array $data): mixed  {
        $sync_id = intval($data['sync_id']);
        $sync = $this->sync_service->get_sync($sync_id);

        if (!$sync) {
            throw new \Exception(__('Sync not found.', $this->text_domain));
        }

        return [
            'id' => $sync->get_id(),
            'status' => $sync->get_status()->value,
            'progress' => $sync->get_progress_percentage(),
            'message' => $sync->get_status_message(),
            'total_items' => $sync->get_total_items(),
            'synced_items' => $sync->get_synced_items(),
            'failed_items' => $sync->get_failed_items()
        ];
    }
}