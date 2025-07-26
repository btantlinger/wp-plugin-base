<?php

namespace WebMoves\PluginBase\Contracts\Settings;

interface SettingsManager
{
	
	/**
	 * Hook prefix for option update actions
	 *
	 * Implementations should do actions that start with this prefix concatenated with
	 * the settings scope (from get_settings_scope). The action callback will receive
	 * these parameters:
	 * - string $scope: The settings scope/option name where the option changed
	 * - mixed $old_value: The previous value of the option
	 * - mixed $new_value: The new value being set
	 * - string $option_name: The name of the option being updated
	 *
	 * For example, if scope is 'my_plugin_settings', the action would be:
	 * 'update_scoped_option_my_plugin_settings'
	 *
	 * If no scope is specified when hooking (using just UPDATE_HOOK), the action
	 * will fire for any scoped option change, similar to how update_option fires
	 * for all options while update_option_name fires only for specific options.
	 */
	const UPDATE_HOOK = 'update_scoped_option';


	/**
	 * Hook prefix for option deletion actions
	 *
	 * Implementations should do actions that start with this prefix concatenated with
	 * the settings scope (from get_settings_scope). The action callback will receive
	 * these parameters:
	 * - string $scope: The settings scope/option name where the option was deleted
	 * - string $option_name: The name of the option being deleted
	 *
	 * For example, if scope is 'my_plugin_settings', the action would be:
	 * 'delete_scoped_option_my_plugin_settings'
	 *
	 * If no scope is specified when hooking (using just DELETE_HOOK), the action
	 * will fire for any scoped option deletion, similar to how delete_option fires
	 * for all options while delete_option_name fires only for specific options.
	 */
	const DELETE_HOOK = 'delete_scoped_option';
	

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