<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Schedulers\SyncScheduler;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Settings\AbstractSettingsProvider;

class GlobalSyncSettings extends AbstractSettingsProvider
{
    // Default values for sync history purge settings
    const DEFAULT_HISTORY_RETENTION_DAYS = 30;
    const DEFAULT_HISTORY_FAILED_RETENTION_DAYS = 60; // Keep failures longer for debugging
    const DEFAULT_MAX_HISTORY_RECORDS = 1000;

    const DEFAULT_SYNC_TIMEOUT_MINUTES = 5;
    
    public function __construct(SettingsManagerFactory $settingsManagerFactory, PluginMetadata $metadata)
    {
        $settings = $settingsManagerFactory->create('global_sync_settings');
        parent::__construct($settings, $metadata);
    }

    public function get_settings_configuration(): array
    {
        $td = $this->get_plugin_metadata()->get_text_domain();
        return [
            'section' => [
                'id' => 'global_sync_settings',
                'title' => __('Global Sync Settings', $td),
                'description' => __('Global settings that apply to all synchronizers.', $td),
            ],
            'fields' => [
                'history_retention_days' => [
                    'id' => 'history_retention_days',
                    'label' => __('History Retention (Days)', $td),
                    'type' => 'number',
                    'description' => __('How many days to keep completed/cancelled sync records.', $td),
                    'default' => self::DEFAULT_HISTORY_RETENTION_DAYS,
                    'required' => true,
                    'attributes' => ['min' => 1, 'max' => 365],
                ],
                'history_failed_retention_days' => [
                    'id' => 'history_failed_retention_days',
                    'label' => __('Failed Sync Retention (Days)', $td),
                    'type' => 'number',
                    'description' => __('How many days to keep failed sync records (kept longer for debugging).', $td),
                    'default' => self::DEFAULT_HISTORY_FAILED_RETENTION_DAYS,
                    'required' => true,
                    'attributes' => ['min' => 1, 'max' => 365],
                ],
                'max_history_records' => [
                    'id' => 'max_history_records',
                    'label' => __('Maximum History Records', $td),
                    'type' => 'number',
                    'description' => __('Maximum total sync records to keep (enforced regardless of age).', $td),
                    'default' => self::DEFAULT_MAX_HISTORY_RECORDS,
                    'required' => true,
                    'attributes' => ['min' => 100, 'max' => 10000],
                ],
                'sync_timeout_minutes' => [
                    'id' => 'sync_timeout_minutes',
                    'label' => __('Sync Timeout (Minutes)', $td),
                    'type' => 'number',
                    'description' => __('How long to wait before marking a running sync as timed out.', $td),
                    'default' => self::DEFAULT_SYNC_TIMEOUT_MINUTES,
                    'required' => true,
                    'attributes' => ['min' => 3, 'max' => 120],
                ]
            ]
        ];
    }

    /**
     * Get history retention days for completed/cancelled syncs
     */
    public function get_history_retention_days(): int
    {
        return $this->settings()->get_scoped_option('history_retention_days', self::DEFAULT_HISTORY_RETENTION_DAYS);
    }

    /**
     * Get history retention days for failed syncs
     */
    public function get_history_failed_retention_days(): int
    {
        return $this->settings()->get_scoped_option('history_failed_retention_days', self::DEFAULT_HISTORY_FAILED_RETENTION_DAYS);
    }

    /**
     * Get maximum history records to keep
     */
    public function get_max_history_records(): int
    {
        return $this->settings()->get_scoped_option('max_history_records', self::DEFAULT_MAX_HISTORY_RECORDS);
    }

    public function get_sync_timeout_minutes(): int
    {
        return $this->settings()->get_scoped_option('sync_timeout_minutes', self::DEFAULT_SYNC_TIMEOUT_MINUTES);
    }
}