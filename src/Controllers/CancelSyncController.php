<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Enums\SyncStatus;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Controllers\AbstractFormController;

class CancelSyncController extends AbstractFormController
{
    private SyncService $sync_service;

    public function __construct(PluginMetadata $metadata, FlashData $flash_data, SyncService $sync_service)
    {
        $this->sync_service = $sync_service;
        parent::__construct($metadata, $flash_data, 'cancel_sync', 'GET');
    }


    protected function get_required_capability(): string
    {
        return 'manage_options';
    }

    protected function validate_request_data(array $data): array
    {
        if (!isset($data['sync_id']) || !is_numeric($data['sync_id'])) {
            return [
                'valid' => false,
                'message' => __('Invalid sync ID provided.', $this->text_domain)
            ];
        }

        $sync_id = intval($data['sync_id']);
        $sync = $this->sync_service->get_sync($sync_id);

        if (!$sync) {
            return [
                'valid' => false,
                'message' => __('Sync record not found.', $this->text_domain)
            ];
        }

        if ($sync->get_status() !== SyncStatus::RUNNING) {
            return [
                'valid' => false,
                'message' => __('Only running syncs can be cancelled.', $this->text_domain)
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    protected function get_nonce_action(array $data): string
    {
        return 'cancel_sync_' . intval($data['sync_id']);
    }

    protected function get_redirect_params_to_remove(array $data): array
    {
        return ['action', 'sync_id', $this->get_nonce_key()];
    }

    protected function handle_action(array $data): array
    {
        $sync_id = intval($data['sync_id']);
        $this->sync_service->set_sync_status($sync_id, SyncStatus::CANCELLED);

        return ['sync_id' => $sync_id];
    }

    protected function get_success_message($result): string
    {
        return __('Sync cancelled successfully.', $this->text_domain);
    }
}