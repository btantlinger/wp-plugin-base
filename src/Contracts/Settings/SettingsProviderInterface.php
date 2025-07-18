<?php

namespace WebMoves\PluginBase\Contracts\Settings;

interface SettingsProviderInterface
{


	public function settings(): SettingsManagerInterface;

    /**
     * Get the settings configuration for this synchronizer
     *
     * Returns an array containing the configuration for registering WordPress settings
     * for this synchronizer, including both the settings section and field definitions.
     *
     * @return array Configuration array with the following structure:
     *               [
     *                   'section' => [
     *                       'id' => string,          // Unique section ID (required)
     *                       'title' => string,       // Section title displayed in admin (required)
     *                       'description' => string, // Optional section description
     *                   ],
     *                   'fields' => [
     *                       'short_option_name' => [  // Array keyed by short option name (e.g., 'schedule_enabled')
     *                           'id' => string,       // Short field ID, used for get_option() calls (required)
     *                           'name' => string,     // Full WordPress option key for database storage (required)
     *                           'label' => string,    // Field label (required)
     *                           'type' => string,     // Field type: 'text', 'checkbox', 'select', 'textarea' (required)
     *                           'description' => string,     // Optional field description/help text
     *                           'default' => mixed,          // Default value for the field (optional)
     *                           'options' => array,          // For 'select' type: array of value => label pairs (optional)
     *                           'sanitize_callback' => callable, // Custom sanitization function (optional)
     *                           'required' => true, // if the field is required
     *                           'validate_callback' => callable, // Custom validation function (optional)
     *                           'attributes' => array,       // Additional HTML attributes (optional)
     *                       ],
     *                       // ... additional fields keyed by their short option names
     *                   ]
     *               ]
     *
     * @example
     * return [
     *     'section' => [
     *         'id' => 'product_sync_settings',
     *         'title' => __('Product Sync Settings', DUFFELLS_SYNC_TD),
     *         'description' => __('Configure product synchronization options.', DUFFELLS_SYNC_TD),
     *     ],
     *     'fields' => [
     *         'schedule_enabled' => [
     *             'id' => 'schedule_enabled',
     *             'name' => 'duffells_sync_product_schedule_enabled',
     *             'label' => __('Enable Scheduling', DUFFELLS_SYNC_TD),
     *             'type' => 'checkbox',
     *             'description' => __('Enable automatic scheduled synchronization.', DUFFELLS_SYNC_TD),
     *             'default' => false,
     *         ],
     *         'schedule_interval' => [
     *             'id' => 'schedule_interval',
     *             'name' => 'duffells_sync_product_schedule_interval',
     *             'label' => __('Schedule Interval', DUFFELLS_SYNC_TD),
     *             'type' => 'select',
     *             'description' => __('How often to run automatic synchronization.', DUFFELLS_SYNC_TD),
     *             'options' => [
     *                 'every_1_hour' => __('Every Hour', DUFFELLS_SYNC_TD),
     *                 'every_6_hours' => __('Every 6 Hours', DUFFELLS_SYNC_TD),
     *                 'daily' => __('Daily', DUFFELLS_SYNC_TD),
     *             ],
     *             'default' => 'every_6_hours',
     *         ],
     *         'api_timeout' => [
     *             'id' => 'api_timeout',
     *             'name' => 'duffells_sync_product_api_timeout',
     *             'label' => __('API Timeout', DUFFELLS_SYNC_TD),
     *             'type' => 'text',
     *             'description' => __('API request timeout in seconds.', DUFFELLS_SYNC_TD),
     *             'default' => 30,
     *             'attributes' => ['type' => 'number', 'min' => 1, 'max' => 300],
     *             'sanitize_callback' => 'absint',
     *         ],
     *     ]
     * ];
     */

    public function get_settings_configuration(): array;

}