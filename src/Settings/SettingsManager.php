<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsManagerInterface;

class SettingsManager implements SettingsManagerInterface
{
    private string $wp_option_name;
    private array $cache = [];
    private bool $cache_loaded = false;
    private bool $dirty = false;

    /**
     * Constructor
     *
     * @param string $scope The scope for this settings manager (e.g., 'my-plugin.product-sync')
     *                      Will be converted to WordPress-safe format internally
     */
    public function __construct(string $scope)
    {
        // Convert scope to WordPress-safe option name
        $this->wp_option_name = $this->convert_scope_to_wp_option_name($scope);
    }

    /**
     * Get the WordPress-safe settings scope for use as an option name
     *
     * @return string The WordPress-safe settings scope/option name
     */
    public function get_settings_scope(): string
    {
        return $this->wp_option_name;
    }

    /**
     * Get all scoped options for this manager
     *
     * @return array All scoped options
     */
    public function get_all_scoped_options(): array
    {
        $this->load_cache();
        
        return $this->cache;
    }

    /**
     * Get a scoped option value
     *
     * @param string $key The option key (without scope prefix)
     * @param mixed $default The default value to return if option doesn't exist
     * @return mixed The option value or default if not found
     */
    public function get_scoped_option(string $key, $default = null): mixed
    {
        $this->load_cache();
        
        return $this->cache[$key] ?? $default;
    }

    /**
     * Set a scoped option value
     *
     * @param string $key The option key (without scope prefix)
     * @param mixed $value The value to store
     * @return void
     */
    public function set_scoped_option(string $key, mixed $value): void
    {
        $this->load_cache();
        
        $this->cache[$key] = $value;
        $this->dirty = true;
    }

    /**
     * Delete a scoped option
     *
     * @param string $key The option key (without scope prefix)
     * @return void
     */
    public function delete_scoped_option(string $key): void
    {
        $this->load_cache();
        
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            $this->dirty = true;
        }
    }

    /**
     * Check if a scoped option exists
     *
     * @param string $key The option key (without scope prefix)
     * @return bool True if the option exists, false otherwise
     */
    public function has_scoped_option(string $key): bool
    {
        $this->load_cache();
        
        return isset($this->cache[$key]);
    }

    /**
     * Set multiple scoped options at once
     *
     * @param array $options Array of key => value pairs
     * @return void
     */
    public function set_multiple_scoped_options(array $options): void
    {
        $this->load_cache();
        
        foreach ($options as $key => $value) {
            $this->cache[$key] = $value;
        }
        
        $this->dirty = true;
    }

    /**
     * Clear all scoped options
     *
     * @return void
     */
    public function clear_all_scoped_options(): void
    {
        $this->cache = [];
        $this->cache_loaded = true;
        $this->dirty = true;
    }

    /**
     * Save any pending changes to the database
     *
     * @return bool True if saved successfully, false otherwise
     */
    public function save(): bool
    {
        if (!$this->dirty) {
            return true; // Nothing to save
        }

        $result = update_option($this->wp_option_name, $this->cache);
        
        if ($result) {
            $this->dirty = false;
        }
        
        return $result;
    }

    /**
     * Reload the cache from the database
     *
     * @return void
     */
    public function refresh(): void
    {
        $this->cache_loaded = false;
        $this->dirty = false;
        $this->load_cache();
    }

    /**
     * Check if there are unsaved changes
     *
     * @return bool True if there are pending changes, false otherwise
     */
    public function is_dirty(): bool
    {
        return $this->dirty;
    }

    /**
     * Convert scope to WordPress-safe option name
     * e.g., 'my-plugin.product-sync' -> 'my_plugin_product_sync_settings'
     */
    private function convert_scope_to_wp_option_name(string $scope): string
    {
        $safe_scope = str_replace(['.', '-'], '_', $scope);
        return $safe_scope . '_settings';
    }

    /**
     * Load the settings cache from WordPress options
     *
     * @return void
     */
    private function load_cache(): void
    {
        if ($this->cache_loaded) {
            return;
        }

        $this->cache = get_option($this->wp_option_name, []);
        $this->cache_loaded = true;
    }

    /**
     * Auto-save when the object is destroyed
     */
    public function __destruct()
    {
        if ($this->dirty) {
            $this->save();
        }
    }
}