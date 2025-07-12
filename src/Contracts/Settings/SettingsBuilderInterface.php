<?php

namespace WebMoves\PluginBase\Contracts\Settings;

interface SettingsBuilderInterface
{
    /**
     * Add a settings provider to the builder
     *
     * @param SettingsProvider $provider
     * @return void
     */
    public function add_provider(SettingsProvider $provider): void;

    /**
     * Initialize the settings builder (register WordPress hooks)
     *
     * @return void
     */
    public function init(): void;

    /**
     * Register all settings with WordPress
     * (Called by WordPress admin_init hook)
     *
     * @return void
     */
    public function register_settings(): void;

    /**
     * Render a single settings field
     * (Called by WordPress settings field callback)
     *
     * @param array $args Field arguments from WordPress
     * @return void
     */
    public function render_settings_field(array $args): void;

    /**
     * Render the complete settings page
     * (Called by your settings page template or callback)
     *
     * @return void
     */
    public function render_settings_page(): void;
}