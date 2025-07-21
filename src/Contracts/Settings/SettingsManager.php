<?php

namespace WebMoves\PluginBase\Contracts\Settings;

interface SettingsManager
{
    /**
     * Get the WordPress-safe settings scope for use as an option name
     * 
     * This should return a string that can be used directly as a WordPress option name.
     * For example: 'my_plugin_product_sync_settings' 
     * 
     * The implementation is responsible for converting any logical scope 
     * (like 'my-plugin.product-sync') into a WordPress-safe format.
     *
     * @return string The WordPress-safe settings scope/option name
     */
    public function get_settings_scope(): string;

    /**
     * Get all scoped options for this manager
     *
     * @return array All scoped options
     */
    public function get_all_scoped_options(): array;

    /**
     * Get a scoped option value
     *
     * @param string $key The option key (without scope prefix)
     * @param mixed $default The default value to return if option doesn't exist
     * @return mixed The option value or default if not found
     */
    public function get_scoped_option(string $key, $default = null): mixed;

    /**
     * Set a scoped option value
     *
     * @param string $key The option key (without scope prefix)
     * @param mixed $value The value to store
     * @return void
     */
    public function set_scoped_option(string $key, mixed $value): void;

    /**
     * Delete a scoped option
     *
     * @param string $key The option key (without scope prefix)
     * @return void
     */
    public function delete_scoped_option(string $key): void;

    /**
     * Check if a scoped option exists
     *
     * @param string $key The option key (without scope prefix)
     * @return bool True if the option exists, false otherwise
     */
    public function has_scoped_option(string $key): bool;

    /**
     * Check if there are unsaved changes
     *
     * @return bool True if there are pending changes, false otherwise
     */
    public function is_dirty(): bool;

    /**
     * Save any pending changes
     *
     * @return bool True if saved successfully, false otherwise
     */
    public function save(): bool;
}