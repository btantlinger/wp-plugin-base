<?php

namespace WebMoves\PluginBase\Examples\Synchronizers;

/**
 * A second dummy synchronizer for testing multiple synchronizer functionality.
 * 
 * This synchronizer simulates a product sync by "processing" a configurable number
 * of product SKUs with different timing and failure rates than the main DummySynchronizer.
 * It's useful for testing the framework's ability to handle multiple sync types.
 */
class ProductSyncDummy extends DummySynchronizer
{
    /**
     * Get the human-readable label for this synchronizer type
     */
    public function get_sync_type_label(): string
    {
        return 'Product Sync (Dummy)';
    }

    /**
     * Get the items to sync - simulate product SKUs
     */
    protected function get_items_to_sync(): array
    {
        // Get the number of products from settings, default to 15
        $product_count = $this->settings->get_scoped_option('product_count', 15);

        // Generate fake product SKUs
        $products = [];
        for ($i = 1; $i <= $product_count; $i++) {
            $products[] = 'PROD-' . str_pad($i, 4, '0', STR_PAD_LEFT);
        }

        return $products;
    }

    /**
     * Perform the sync operation - simulate product processing
     */
    protected function perform_sync(array $items, int $sync_id): void
    {
        $this->log()->info("Starting product sync with " . count($items) . " products");

        // Get settings
        $enable_failures = $this->settings->get_scoped_option('enable_random_failures', true);
        $delay = $this->settings->get_scoped_option('processing_delay', 2);
        $failure_rate = $this->settings->get_scoped_option('failure_rate', 5); // 5% default

        foreach ($items as $product_sku) {
            // Check if we should stop (in case sync was cancelled)
            $current_sync = $this->get_sync($sync_id);
            if ($current_sync->get_status() === \WebMoves\PluginBase\Enums\SyncStatus::CANCELLED) {
                $this->log()->info("Product sync was cancelled at SKU: " . $product_sku);
                break;
            }

            try {
                // Simulate processing a product
                $this->log()->debug("Processing product: " . $product_sku);

                // Variable sleep time based on settings
                if ($delay > 0) {
                    sleep($delay);
                }

                // Configurable failure rate
                $should_fail = $enable_failures && (rand(1, 100) <= $failure_rate);

                if ($should_fail) {
                    $this->log()->warning("Simulated failure for product: " . $product_sku);
                    $this->increment_failed($sync_id, 1);
                } else {
                    // Successfully "processed" this product
                    $this->log()->debug("Successfully synced product: " . $product_sku);
                    $this->increment_synced($sync_id, 1);
                }

            } catch (\Exception $e) {
                $this->log()->error("Error processing product {$product_sku}: " . $e->getMessage());
                $this->increment_failed($sync_id, 1);
            }
        }

        $final_sync = $this->get_sync($sync_id);
        $this->log()->info(sprintf(
            "Product sync completed. Synced: %d, Failed: %d, Total: %d",
            $final_sync->get_synced(),
            $final_sync->get_failed(),
            $final_sync->get_total()
        ));
    }

    /**
     * Validate that the sync can run
     */
    protected function validate_can_sync(): void
    {
        // Add some product-specific validation
        $product_count = $this->settings->get_scoped_option('product_count', 15);

        if ($product_count < 1) {
            throw new \Exception('Product count must be at least 1');
        }

        if ($product_count > 100) {
            throw new \Exception('Product count cannot exceed 100 for this demo');
        }

        $this->log()->info("Product sync validation passed - ready to sync {$product_count} products!");
    }

    /**
     * Override schedule intervals to provide different options
     */
    public function get_available_schedule_intervals(): array
    {
        return [
            'every_15_minutes' => [
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display' => 'Every 15 minutes',
            ],
            'every_30_minutes' => [
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display' => 'Every 30 minutes',
            ],
            'every_2_hours' => [
                'interval' => 2 * HOUR_IN_SECONDS,
                'display' => 'Every 2 hours',
            ],
            'every_6_hours' => [
                'interval' => 6 * HOUR_IN_SECONDS,
                'display' => 'Every 6 hours',
            ],
            'every_12_hours' => [
                'interval' => 12 * HOUR_IN_SECONDS,
                'display' => 'Every 12 hours',
            ],
            'daily' => [
                'interval' => 24 * HOUR_IN_SECONDS,
                'display' => 'Daily',
            ]
        ];
    }

    /**
     * Add product-specific settings fields
     */
    protected function get_settings_fields(): array
    {
        $base_fields = parent::get_settings_fields();
        $td = $this->metadata->get_text_domain();
        $type = $this->get_sync_type_key();

        // Add product-specific settings
        $product_fields = [
            'product_count' => [
                'id' => 'product_count',
                'label' => __('Number of Products', $td),
                'description' => __('How many dummy products to process during sync', $td),
                'type' => 'number',
                'default' => 15,
                'attributes' => [
                    'min' => 1,
                    'max' => 100,
                    'step' => 1,
                    'class' => "wm-{$type}-schedule-dependent",
                ]
            ],
            'failure_rate' => [
                'id' => 'failure_rate',
                'label' => __('Failure Rate (%)', $td),
                'description' => __('Percentage of products that will randomly fail (0-50%)', $td),
                'type' => 'number',
                'default' => 5,
                'attributes' => [
                    'min' => 0,
                    'max' => 50,
                    'step' => 1,
                    'class' => "wm-{$type}-schedule-dependent",
                ]
            ],
            'enable_detailed_logging' => [
                'id' => 'enable_detailed_logging',
                'label' => __('Enable Detailed Logging', $td),
                'description' => __('Log detailed information about each product processed', $td),
                'type' => 'checkbox',
                'default' => false,
                'attributes' => [
                    'class' => "wm-{$type}-schedule-dependent",
                ]
            ]
        ];

        return array_merge($base_fields, $product_fields);
    }
}
