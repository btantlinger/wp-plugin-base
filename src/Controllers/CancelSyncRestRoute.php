<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Enums\SyncStatus;
use WebMoves\PluginBase\Concerns\HasLogger;
use WebMoves\PluginBase\Controllers\AbstractRestRoute;

class CancelSyncRestRoute extends AbstractRestRoute
{
    use HasLogger;

    private SyncService $sync_service;

    public function __construct(SyncService $sync_service)
    {
        $this->sync_service = $sync_service;
        parent::__construct(
            SyncStatusRestRoute::ROUTE . "/cancel",
            SyncStatusRestRoute::NAMESPACE,
            [
                'sync_type' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    }
                ]
            ],
            ['POST']
        );
    }

    public function handle_rest_request(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        try {
            $sync_type = sanitize_text_field($request->get_param('sync_type'));
            $this->log()->debug('Canceling sync for type: ' . $sync_type);

            // Find running sync for this type
            $running_sync = $this->sync_service->get_running_sync_for_type($sync_type);

            if (!$running_sync) {
                $this->log()->error('No running sync found for this type');
                return new \WP_REST_Response([
                    'status' => 'error',
                    'message' => 'No running sync found for this type'
                ], 404);
            }

            $this->log()->debug('Found running sync: ' . $running_sync->get_id());

            // Cancel the sync
            $this->sync_service->set_sync_status($running_sync->get_id(), SyncStatus::CANCELLED);

            $this->log()->debug('Sync cancelled successfully');

            return new \WP_REST_Response([
                'status' => 'success',
                'message' => 'Sync cancelled successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->log()->error('Failed to cancel sync', [$e->getMessage(), $e->getTraceAsString()]);
            return new \WP_REST_Response([
                'status' => 'error',
                'message' => 'Failed to cancel sync: ' . $e->getMessage()
            ], 500);
        }
    }
}