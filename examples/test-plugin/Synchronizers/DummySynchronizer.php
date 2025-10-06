<?php

namespace WebMoves\PluginBase\Examples\Synchronizers;

use WebMoves\PluginBase\Synchronizers\AbstractSynchronizer;

/**
 * A dummy synchronizer for testing sync functionality.
 * 
 * This synchronizer simply counts from 1 to 20 with a 1-second sleep between each count.
 * It's useful for testing the sync UI, progress tracking, and general sync functionality
 * without requiring external dependencies or complex logic.
 */
class DummySynchronizer extends AbstractSynchronizer
{
    /**
     * Get the human-readable label for this synchronizer type
     */
    public function get_sync_type_label(): string
    {
        return 'Dummy Counter Sync'; //__('Dummy Counter Sync', $this->metadata->get_text_domain());
    }

    /**
     * Get the items to sync - in this case, just numbers 1-20
     */
    protected function get_items_to_sync(): array
    {
        return range(1, 20);
    }

    /**
     * Perform the sync operation - count each number with a 1-second delay
     */
    protected function perform_sync(array $items, int $sync_id): void
    {
        $this->log()->info("Starting dummy sync with " . count($items) . " items");

        foreach ($items as $item) {
            // Check if we should stop (in case sync was cancelled)
            $current_sync = $this->get_sync($sync_id);
            if ($current_sync->get_status() === \WebMoves\PluginBase\Enums\SyncStatus::CANCELLED) {
                $this->log()->info("Dummy sync was cancelled at item: " . $item);
                break;
            }

            try {
                // Simulate some work with the "item"
                $this->log()->debug("Processing item: " . $item);

                // Sleep for 1 second to simulate processing time
                sleep(1);

                // Randomly fail some items (10% chance) to test error handling
                if (rand(1, 10) === 1) {
                    $this->log()->warning("Simulated failure for item: " . $item);
                    $this->increment_failed($sync_id, 1);
                } else {
                    // Successfully "processed" this item
                    $this->log()->debug("Successfully processed item: " . $item);
                    $this->increment_synced($sync_id, 1);
                }

            } catch (\Exception $e) {
                $this->log()->error("Error processing item {$item}: " . $e->getMessage());
                $this->increment_failed($sync_id, 1);
            }
        }

        $final_sync = $this->get_sync($sync_id);
        $this->log()->info(sprintf(
            "Dummy sync completed. Processed: %d, Failed: %d, Total: %d",
            $final_sync->get_synced(),
            $final_sync->get_failed(),
            $final_sync->get_total()
        ));
    }

    /**
     * Validate that the sync can run
     * For a dummy sync, we don't need any special validation
     */
    protected function validate_can_sync(): void
    {
        // No special validation needed for dummy sync
        $this->log()->info("Dummy sync validation passed - ready to count!");
    }

	public function get_available_schedule_intervals(): array
	{
		$intervals = parent::get_available_schedule_intervals();

		$my_intervals = [
			'every_2_minutes' => [
				'interval' => 2 * MINUTE_IN_SECONDS,
				'display' => 'Every 2 minutes',
			],

		];
		return array_merge($my_intervals, $intervals);
	}

    /**
     * Add some custom settings fields for demonstration
     */
    protected function get_settings_fields(): array
    {
        $base_fields = parent::get_settings_fields();
        $td = $this->metadata->get_text_domain();

        // Add some dummy-specific settings
        $dummy_fields = [
            'enable_random_failures' => [
                'id' => 'enable_random_failures',
                'label' => __('Enable Random Failures', $td),
                'description' => __('Randomly fail 10% of items to test error handling', $td),
                'type' => 'checkbox',
                'default' => true,
            ],
            'processing_delay' => [
                'id' => 'processing_delay',
                'label' => __('Processing Delay (seconds)', $td),
                'description' => __('How long to wait between processing each item', $td),
                'type' => 'number',
                'default' => 1,
                'attributes' => [
                    'min' => 0,
                    'max' => 10,
                    'step' => 1,
                ]
            ]
        ];

        return array_merge($base_fields, $dummy_fields);
    }
}
