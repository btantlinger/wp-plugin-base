<?php

namespace WebMoves\PluginBase\Synchronizers;

use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Contracts\Synchronizers\SchedulableSynchronizer;
use WebMoves\PluginBase\Contracts\Synchronizers\Sync;
use WebMoves\PluginBase\Contracts\Synchronizers\SyncAlreadyRunningException;
use WebMoves\PluginBase\Enums\SyncStatus;
use WebMoves\PluginBase\Concerns\HasLogger;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\SettingsManager;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;


abstract class AbstractSynchronizer implements SchedulableSynchronizer
{
    protected  SyncService $syncService;

    private ?string $key = null;

    private ?int $current_sync_id = null;
    
    protected PluginMetadata $metadata;
    
    //protected string $text_domain;

    protected SettingsManager $settings;


    use HasLogger;

    public function __construct(SyncService $syncService, PluginMetadata $metadata, SettingsManagerFactory $settings_factory)
    {
        $this->logging_channel = 'app';
        $this->syncService = $syncService;
        $this->metadata = $metadata;
        $this->settings = $settings_factory->create($this->get_sync_type_key());
    }

    public function settings(): SettingsManager
    {
        return $this->settings;
    }


    public function sync(string $triggeredBy='manual'): void
    {
        // Check if this type already has one running.  Only one sync per type at a time may run!
        $sync = $this->syncService->get_running_sync_for_type($this->get_sync_type_key());
        if (!is_null($sync)) {
            throw new SyncAlreadyRunningException('A sync of type "' . $this->get_sync_type_key() . '" is already running. Skipping this sync.');
        }

        $this->current_sync_id = null;
        try {
            $this->validate_can_sync();
            $this->current_sync_id = $this->syncService->sync_started($this->get_sync_type_key(), $triggeredBy);
            $items = $this->get_items_to_sync();
            $this->syncService->set_total_items_to_sync($this->current_sync_id, count($items));

            $this->perform_sync($items, $this->current_sync_id);

            $status = $this->syncService->get_sync($this->current_sync_id)->get_status();
            if($status !== SyncStatus::CANCELLED && $status !== SyncStatus::FAILED) {
                $this->syncService->set_sync_complete($this->current_sync_id);
            }
        } catch (\Exception $e) {
            if($this->current_sync_id) {
                $this->syncService->set_sync_failed($this->current_sync_id, $e->getMessage());
            } else {
                // Just start a sync and then fail it to set the error message...
                $id = $this->syncService->sync_started($this->get_sync_type_key(), $triggeredBy);
                $this->syncService->set_sync_failed($id, $e->getMessage());
            }
            $this->log()->error($e->getMessage());
        }
    }


    /**
     * increments the current sync and returns the status of the after the increment
     *
     * @param int $sync_id
     * @param int $increment
     * @return SyncStatus
     */
    protected function increment_synced(int $sync_id, int $increment): Sync
    {
        $this->syncService->increment_synced($sync_id, $increment);
        return $this->update_status_for_total_complete($sync_id);
    }

    protected function increment_failed(int $sync_id, int $increment): Sync
    {
        $this->syncService->increment_failed($sync_id, $increment);
        return $this->update_status_for_total_complete($sync_id);
    }

    private function update_status_for_total_complete($sync_id): Sync
    {
        $sync = $this->get_sync($sync_id);
        if($sync->get_status() == SyncStatus::RUNNING) {
            $total_complete = $sync->get_synced() + $sync->get_failed();
            if ($total_complete >= $sync->get_total()) {
                $this->syncService->set_sync_complete($sync_id);
                return $this->get_sync($sync_id);
            }
        }
        return $sync;
    }

    protected function get_sync(int $sync_id): Sync
    {
        return $this->syncService->get_sync($sync_id);
    }


    public function get_name(): string
    {
        return $this->get_sync_type_key();
    }

    public function get_sync_type_key(): string
    {
        if(!$this->key) {
            $name = $this->get_sync_type_label();
            $this->key = str_replace('-', '_', sanitize_title_with_dashes(strtolower($name)));
        }
        return $this->key;
    }


    /**
     * Items may be ids, skus, full objects, arrays etc... it depends on the implementation
     *
     * Each item in the array should be a single item to sync into the local system
     *
     * @return array the things to sync
     */
    protected abstract function get_items_to_sync(): array;

    /**
     * Synchronize the items returned from get_items_to_sync() with the local system
     *
     * @param array $items
     * @param int $sync_id
     * @return void
     */
    protected abstract function perform_sync(array $items, int $sync_id): void;

    /**
     * Validates that the sync is possible
     *
     * Sub classes might want to add additional checks
     *
     * @return void
     * @throws \Exception
     */
    protected function validate_can_sync()
    {
/*        if (!class_exists('WooCommerce')) {
            throw new \Exception('WooCommerce must be installed and activated to run product sync.');
        }

        if (!function_exists('wc_get_product')) {
            throw new \Exception('WooCommerce is not fully initialized.');

        }
        $this->log()->info("WooCommerce validation passed");*/
    }

    public function is_schedule_enabled(): bool {
        return $this->settings->get_scoped_option(SchedulableSynchronizer::SCHEDULE_ENABLED, false);
    }

    public function get_schedule_interval(): string
    {
        return $this->settings->get_scoped_option(SchedulableSynchronizer::SCHEDULE_INTERVAL, 'every_6_hours');
    }

    public function get_available_schedule_intervals(): array
    {
        return [
            'every_30_minutes' => [
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display' => 'Every 30 minutes',
            ],
            'every_60_minutes' => [
                'interval' => 60 * MINUTE_IN_SECONDS,
                'display' => 'Every 60 minutes',
            ],
            'every_90_minutes' => [
                'interval' => 90 * MINUTE_IN_SECONDS,
                'display' => 'Every 90 minutes',
            ],
            'every_2_hours' => [
                'interval' => 2 * HOUR_IN_SECONDS,
                'display' => 'Every 2 hours',
            ],
            'every_4_hours' => [
                'interval' => 4 * HOUR_IN_SECONDS,
                'display' => 'Every 4 hours',
            ],
            'every_6_hours' => [
                'interval' => 6 * HOUR_IN_SECONDS,
                'display' => 'Every 6 hours',
            ],
            'every_8_hours' => [
                'interval' => 8 * HOUR_IN_SECONDS,
                'display' => 'Every 8 hours',
            ],
            'every_12_hours' => [
                'interval' => 12 * HOUR_IN_SECONDS,
                'display' => 'Every 12 hours',
            ],
            'every_24_hours' => [
                'interval' => 24 * HOUR_IN_SECONDS,
                'display' => 'Every 24 hours',
            ]
        ];
    }

    public function get_schedule_options(): array
    {
        $options = [];
        $intervals = $this->get_available_schedule_intervals();
        foreach($intervals as $key => $interval) {
            $options[$key] = $interval['display'];
        }
        return $options;
    }


    protected function build_section_configuration(): array
    {
        return [
            'id' => $this->get_sync_type_key() . '_settings',
            'title' => sprintf(__('%s Settings', $this->metadata->get_text_domain()), $this->get_sync_type_label()),
        ];
    }

    public function get_settings_configuration(): array
    {
        return [
            'section' => $this->build_section_configuration(),
            'fields' => $this->build_field_configurations()
        ];
    }

    protected function build_field_configurations(): array
    {
        return $this->get_settings_fields();
    }

    protected function get_settings_fields(): array
    {
        return $this->get_scheduling_fields();
    }

    protected function get_scheduling_fields(): array
    {
        $td = $this->metadata->get_text_domain();
        $type = $this->get_sync_type_key();

        // Enqueue admin scripts and styles for schedule toggling
        $this->enqueue_schedule_assets();

        return [
            self::SCHEDULE_ENABLED => [
                'id' => self::SCHEDULE_ENABLED,
                'label' => __('Enable Scheduling', $td),
                'description' => __('Scheduled Synchronizing', $td),
                'type' => 'checkbox',
                'default' => false,
                'attributes' => [
                    'class' => "wm-$type-schedule-enabled wm-schedule-enabled",
                ]
            ],
           self::SCHEDULE_INTERVAL => [
                'id' => self::SCHEDULE_INTERVAL,
                'label' => __('Schedule Interval', $td),
                'type' => 'select',
                'description' => __('The interval at which to run the sync.', $td),
                'options' => $this->get_schedule_options(),
                'default' => 'every_6_hours',
                'attributes' => [
                    'class' => "wm-$type-schedule-interval wm-schedule-interval",
                ]
            ]
        ];
    }

    /**
     * Enqueue admin assets for schedule field toggling
     */
    protected function enqueue_schedule_assets(): void
    {
        // Only enqueue on admin pages
        if (!is_admin()) {
            return;
        }

        // Get plugin URL from metadata
        $plugin_file = $this->metadata->get_plugin_file();
        $plugin_url = plugin_dir_url($plugin_file);

        // Get the correct asset paths
        $js_path = $this->get_framework_asset_path('js/schedule-toggle.js');
        $css_path = $this->get_framework_asset_path('css/schedule-toggle.css');

        // Enqueue JavaScript
        wp_enqueue_script(
            'wm-schedule-toggle',
            $plugin_url . $js_path,
            ['jquery'],
            $this->metadata->get_version(),
            true
        );

        // Enqueue CSS
        wp_enqueue_style(
            'wm-schedule-toggle',
            $plugin_url . $css_path,
            [],
            $this->metadata->get_version()
        );
    }

    /**
     * Get the correct asset path whether running as standalone plugin or Composer package
     * This also allows users to override by placing assets in their plugin directory
     */
    private function get_framework_asset_path(string $asset_path): string
    {
        // Get the plugin directory from metadata
        $plugin_file = $this->metadata->get_plugin_file();
        $plugin_dir = plugin_dir_path($plugin_file);

        // First check if user has overridden the asset in their plugin directory
        $plugin_asset_path = 'assets/admin/' . $asset_path;
        $plugin_asset_file = $plugin_dir . $plugin_asset_path;

        if (file_exists($plugin_asset_file)) {
            // User has overridden the asset
            return $plugin_asset_path;
        }

        // Try to detect if framework is running from vendor directory
        $reflection = new \ReflectionClass(\WebMoves\PluginBase\PluginBase::class);
        $framework_file = $reflection->getFileName();

        // Check if we're in a vendor directory
        if (strpos($framework_file, '/vendor/webmoves/plugin-base/') !== false) {
            // Running as Composer package - standard vendor path
            return 'vendor/webmoves/plugin-base/assets/admin/' . $asset_path;
        } elseif (strpos($framework_file, '/vendor/') !== false) {
            // Running as Composer package with different vendor structure
            // Extract the vendor path dynamically
            preg_match('/^(.+\/vendor\/)/', $framework_file, $matches);
            if (!empty($matches[1])) {
                $relative_vendor = str_replace($plugin_dir, '', $matches[1]);
                return $relative_vendor . 'webmoves/plugin-base/assets/admin/' . $asset_path;
            }
        }

        // Fallback: assume standalone plugin structure
        return 'assets/admin/' . $asset_path;
    }
}