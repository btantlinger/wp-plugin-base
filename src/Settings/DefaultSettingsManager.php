<?php

namespace WebMoves\PluginBase\Settings;

use WebMoves\PluginBase\Contracts\Settings\SettingsManager;

class DefaultSettingsManager implements SettingsManager
{
    private string $wp_option_name;
	private array $cache = [];
	private bool $cache_loaded = false;
	/**
	 * Constructor
	 *
	 * Creates a new settings manager instance that handles settings within a specific scope.
	 * The scope is created by concatenating the plugin_prefix and scope parameters with an
	 * underscore separator. For example, if plugin_prefix is "my-plugin" and scope is
	 * "product-sync", the resulting scope will be "my_plugin_product_sync_settings".
	 *
	 * @param ?string $plugin_prefix The plugin identifier prefix (e.g., "my-plugin")
	 * @param string $scope The settings scope (e.g., "product-sync")
	 */
	public function __construct(string $scope, ?string $plugin_prefix = null)
	{
		if(!empty($plugin_prefix)) {
			$scope = rtrim($plugin_prefix, "_-") . '_' . ltrim($scope, "_-");;
		}

		$scope = trim($scope, "_-");
		if(empty($scope)) {
			throw new \InvalidArgumentException('Scope cannot be empty');
		}

		// Convert scope to WordPress-safe option name
		$this->wp_option_name = $this->convert_scope_to_wp_option_name($scope);
	}

	private bool $dirty = false;

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

		$old_value = $this->cache[$key] ?? null;
		$this->cache[$key] = $value;
		$this->dirty = true;

		// Fire the update action with scope instead of full manager object
		do_action(
			self::UPDATE_HOOK . '_' . $this->wp_option_name,
			$this->wp_option_name,  // Just the scope
			$old_value,
			$value,
			$key
		);

		// Also fire the generic update action
		do_action(
			self::UPDATE_HOOK,
			$this->wp_option_name,  // Just the scope
			$old_value,
			$value,
			$key
		);
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

			// Fire the delete action with scope instead of full manager object
			do_action(
				self::DELETE_HOOK . '_' . $this->wp_option_name,
				$this->wp_option_name,  // Just the scope
				$key
			);

			// Also fire the generic delete action
			do_action(
				self::DELETE_HOOK,
				$this->wp_option_name,  // Just the scope
				$key
			);
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
            $old_value = $this->cache[$key] ?? null;
            $this->cache[$key] = $value;
            
            // Fire the update action for each option
            do_action(
                self::UPDATE_HOOK . '_' . $this->wp_option_name,
                $this->wp_option_name,
                $old_value,
                $value,
                $key
            );
            
            // Also fire the generic update action
            do_action(
                self::UPDATE_HOOK,
                $this->wp_option_name,
                $old_value,
                $value,
                $key
            );
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
        $this->load_cache();
        
        // Fire delete actions for all existing options
        foreach (array_keys($this->cache) as $key) {
            do_action(
                self::DELETE_HOOK . '_' . $this->wp_option_name,
                $this->wp_option_name,
                $key
            );
            
            // Also fire the generic delete action
            do_action(
                self::DELETE_HOOK,
                $this->wp_option_name,
                $key
            );
        }
        
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
        return  sanitize_key(str_replace(['.', '-'], '_', strtolower($scope)));
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