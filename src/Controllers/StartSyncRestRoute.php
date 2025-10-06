<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\BackgroundTasks\StartSyncBackgroundTask;
use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;;
use WebMoves\PluginBase\Concerns\HasLogger;
use WebMoves\PluginBase\Controllers\AbstractRestRoute;

class StartSyncRestRoute extends AbstractRestRoute
{
    private SyncService $sync_service;

    private array $synchronizers;

    private StartSyncBackgroundTask $task;

    use HasLogger;

    public function __construct(SyncService $sync_service, StartSyncBackgroundTask $task)
    {
        //$this->logging_channel = 'api';
        $this->sync_service = $sync_service;
        $this->synchronizers = $task->get_synchronizers();
        $this->task = $task;
        parent::__construct(
            SyncStatusRestRoute::ROUTE . "/start",
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
        $this->log()->info('Starting sync:' . $request->get_param('sync_type'));
        try {
            $sync_type = sanitize_text_field($request->get_param('sync_type'));

            // Check if there's already a running sync for this type
            $running_sync = $this->sync_service->get_running_sync_for_type($sync_type);

            if ($running_sync) {
                return new \WP_REST_Response([
                    'status' => 'error',
                    'message' => 'A sync is already running for this type'
                ], 400);
            }

            if (empty($this->synchronizers[$sync_type])) {
                return new \WP_REST_Response([
                    'status' => 'error',
                    'message' => 'Invalid sync type'
                ], 400);
            }

            $result = $this->task->run_sync($sync_type, 'manual');
            if(!$result) {
                throw new \Exception('Task failed to run');
            }

            return new \WP_REST_Response([
                'status' => 'success',
                'message' => 'Sync started successfully'
            ], 200);

        } catch (\Exception $e) {

            $this->log()->error($e->getTraceAsString());
            return new \WP_REST_Response([
                'status' => 'error',
                'message' => 'Failed to start sync: ' . $e->getMessage()
            ], 500);
        }
    }
}