<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Enums\SyncStatus;
use WebMoves\PluginBase\Events\ScheduledSyncEvent;
use WebMoves\PluginBase\Controllers\AbstractRestRoute;

class SyncStatusRestRoute extends AbstractRestRoute
{
    const NAMESPACE = 'plugin-base-sync/v1';

    const ROUTE = 'sync-status';

    private SyncService $sync_service;

    private array $schedulers;

    public function __construct(SyncService $sync_service, array $sync_schedulers)
    {
        $this->sync_service = $sync_service;
        $this->schedulers = $sync_schedulers;
        parent::__construct(self::ROUTE, self::NAMESPACE);
    }

    public function handle_rest_request(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $data = [];
        foreach($this->schedulers as $scheduler) {
            /**
             * @var $scheduler ScheduledSyncEvent
             */
            $syncer = $scheduler->get_synchronizer();
            $next_run_timestamp = $scheduler->get_next_scheduled_run();
            $next_run_formatted_date = null;
            if($next_run_timestamp) {
                $time_diff = $next_run_timestamp - time();
                if ($time_diff < 86400) { // Less than 24 hours
                    $next_run_formatted_date = sprintf('Today at %s', wp_date('g:i A', $next_run_timestamp));
                } else if ($time_diff < 172800) { // Less than 48 hours
                    $next_run_formatted_date = sprintf('Tomorrow at %s', wp_date('g:i A', $next_run_timestamp));
                } else {
                    $next_run_formatted_date = wp_date('F j, Y \a\t g:i A', $next_run_timestamp);
                }
            }

            $sync = $this->sync_service->get_running_sync_for_type($syncer->get_sync_type_key());
            if(!$sync) {
                $sync = $this->sync_service->get_last_finished_sync_for_type($syncer->get_sync_type_key());
            }

            $sync_data = [
                'sync_status' => SyncStatus::NOT_STARTED,
                'key' => $syncer->get_sync_type_key(),
                'label' => $syncer->get_sync_type_label(),
                'percent_complete' => 0,
                'total' => 0,
                'synced' => 0,
                'failed' => 0,
                'duration' => 0,
                'is_running' => false,
                'next_at_formatted' => $next_run_formatted_date,
                'next_at' => $next_run_timestamp,
                'started_at' => null,
                'started_at_formatted' => null,
                'updated_at' => null,
                'completed_at' => null,
            ];

            if($sync) {
                $sync_data = array_merge($sync_data, [
                    'key' => $syncer->get_sync_type_key(),
                    'label' => $syncer->get_sync_type_label(),
                    'sync_status' => $sync->get_status(),
                    'percent_complete' => $sync->get_percent_complete(),
                    'total' => $sync->get_total(),
                    'synced' => $sync->get_synced(),
                    'failed' => $sync->get_failed(),
                    'duration' => $sync->get_duration(),
                    'is_running' => ($sync->get_status() == SyncStatus::RUNNING),
                    'next_at_formatted' => $next_run_formatted_date,
                    'next_at' => $next_run_timestamp,
                    'started_at' => $sync->get_started_at()?->getTimestamp(),
                    'started_at_formatted' => $sync->get_started_at()?->format('F j, Y \a\t g:i A'),
                    'updated_at' => $sync->get_updated_at()?->getTimestamp(),
                    'completed_at' => $sync->get_completed_at()?->getTimestamp(),
                ]);
            }
            $data[$syncer->get_sync_type_key()] = $sync_data;

        }
        return new \WP_REST_Response([
            'status' => 'success',
            'data' => $data
        ], 200);
    }
}