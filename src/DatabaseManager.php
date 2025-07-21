<?php

namespace WebMoves\PluginBase;

use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\Configuration\ConfigurationManagerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;

/**
 * Database Manager
 * 
 * Manages database operations including table creation, version tracking, and upgrades
 * for WordPress plugins. Automatically loads table schemas from configuration and 
 * provides WordPress action hooks for custom upgrade logic.
 * 
 * ## WordPress Actions Fired
 * 
 * During database upgrades, the following WordPress actions are fired in sequence:
 * 
 * ### 1. Pre-Upgrade Action
 * **Hook:** `{hook_prefix}_database_upgrade_start`
 * **Parameters:** `$old_version`, `$new_version`, `$core`
 * **When:** Before any upgrade operations begin
 * **Use case:** Backup data, prepare environment, validation
 * 
 * ### 2. General Upgrade Action
 * **Hook:** `{hook_prefix}_database_upgrade`
 * **Parameters:** `$old_version`, `$new_version`, `$core`
 * **When:** After tables are created/updated, before version-specific actions
 * **Use case:** General upgrade logic, data migrations
 * 
 * ### 3. Version-Specific Actions
 * **Hook:** `{hook_prefix}_database_upgrade_to_{sanitized_version}`
 * **Parameters:** `$old_version`, `$new_version`, `$core`
 * **When:** For each version being upgraded to
 * **Version format:** Dots replaced with underscores (e.g., "1.2.0" becomes "1_2_0")
 * **Use case:** Version-specific database changes, data transformations
 * 
 * ### 4. Post-Upgrade Action (Success)
 * **Hook:** `{hook_prefix}_database_upgrade_complete`
 * **Parameters:** `$old_version`, `$new_version`, `$core`
 * **When:** After successful upgrade completion
 * **Use case:** Cleanup, notifications, post-upgrade tasks
 * 
 * ### 5. Error Action (Failure)
 * **Hook:** `{hook_prefix}_database_upgrade_failed`
 * **Parameters:** `$old_version`, `$new_version`, `$exception`, `$core`
 * **When:** When an upgrade fails with an exception
 * **Use case:** Error handling, rollback, notifications
 * 
 * ## Usage Examples
 * 
 * ```php
 * // General upgrade hook
 * add_action('my_plugin_database_upgrade', function($old_version, $new_version, $core) {
 *     // Custom upgrade logic here
 *     error_log("Upgrading from {$old_version} to {$new_version}");
 * });
 * 
 * // Version-specific upgrade (e.g., to version 1.2.0)
 * add_action('my_plugin_database_upgrade_to_1_2_0', function($old_version, $new_version, $core) {
 *     // Specific logic for version 1.2.0
 *     // e.g., migrate data, add new columns, etc.
 * });
 * 
 * // Pre-upgrade preparation
 * add_action('my_plugin_database_upgrade_start', function($old_version, $new_version, $core) {
 *     // Backup data before upgrade
 *     do_backup();
 * });
 * 
 * // Handle upgrade failures
 * add_action('my_plugin_database_upgrade_failed', function($old_version, $new_version, $exception, $core) {
 *     error_log('Database upgrade failed: ' . $exception->getMessage());
 *     // Send admin notification, etc.
 * });
 * ```
 * 
 * ## Configuration
 * 
 * Tables are loaded from configuration at: `database.tables`
 * Database version is read from: `database.version`
 * 
 * @package WebMoves\PluginBase
 * @since 1.0.0
 */
class DatabaseManager implements DatabaseManagerInterface
{
    /**
     * Current database version from configuration
     */
    private string $version;

    /**
     * WordPress option name for storing database version
     */
    private string $version_option_name;

    /**
     * Plugin name
     */
    private string $plugin_name;

    /**
     * Hook prefix for WordPress actions (from PluginCore::get_hook_prefix())
     */
    private string $hook_prefix;

    /**
     * Registered table schemas
     * @var array<string, array{schema: string, metadata: array}>
     */
    private array $tables = [];
    
    /**
     * Logger instance for database operations
     */
    protected LoggerInterface $logger;

    /**
     * Plugin core instance
     */
    protected PluginCoreInterface $core;
    
    /**
     * Cache for database version check to avoid repeated queries
     */
    private static ?bool $is_current_cached = null;



    /**
     * Initialize the database manager
     * 
     * Loads database version and tables from configuration, sets up logging,
     * and registers all configured tables automatically.
     *
     * @param PluginCoreInterface $core Plugin core instance
     * @param ConfigurationManagerInterface $config Configuration manager
     */
    public function __construct(PluginCoreInterface $core, ConfigurationManagerInterface $config)
    {
        $this->core = $core;

        $this->version = $config->get('database.version', '1.0.0');
        $this->plugin_name = $core->get_plugin_name();
        $this->hook_prefix = $core->get_hook_prefix();
        $this->version_option_name = $this->generate_version_option_name($this->plugin_name);
        $this->logger = $core->get_logger('database');
		
		$tables = $config->get('database.tables', []);
		
		foreach ($tables as $table_name => $table_data) {
			$this->register_table($table_name, $table_data);
		}	
    }

    /**
     * Register a table schema with optional metadata
     * 
     * @param string $table_name Table name (without WordPress prefix)
     * @param string $schema SQL CREATE TABLE statement with placeholders
     * @param array $metadata Optional metadata for the table
     */
    public function register_table(string $table_name, string $schema, array $metadata = []): void
    {
        $this->tables[$table_name] = [
            'schema' => $schema,
            'metadata' => array_merge([
                'created_in_version' => $this->version,
                'last_modified_in_version' => $this->version,
                'registered_at' => current_time('mysql'),
            ], $metadata)
        ];
    }

    /**
     * Create/update all registered tables using dbDelta
     * 
     * Processes all registered tables and uses WordPress dbDelta function
     * to create or update table schemas. Handles placeholder replacement
     * for {table_name} and {charset_collate}.
     */
    public function create_tables(): void
    {
        if (empty($this->tables)) {
            $this->logger->debug('No tables registered, skipping create_tables()');
            return;
        }

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_queries = [];

        foreach ($this->tables as $table_name => $table_data) {
            $full_table_name = $wpdb->prefix . $table_name;
            $schema = is_array($table_data) ? $table_data['schema'] : $table_data;
            
            // Check if table exists for better logging
            if (!$this->table_exists($full_table_name)) {
                $this->logger->info("Creating new table: {$full_table_name}");
            } else {
                $this->logger->debug("Updating existing table: {$full_table_name}");
            }
            
            $sql = str_replace('{table_name}', $full_table_name, $schema);
            $sql = str_replace('{charset_collate}', $wpdb->get_charset_collate(), $sql);
            $sql_queries[] = $sql;
        }

        // Run dbDelta with all table schemas
        $results = dbDelta($sql_queries);

        // Log the results for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            foreach ($results as $result) {
                $this->logger->info("Database update result: " . $result);
            }
        }
        
        $this->logger->info("Database tables processed", [
            'total_tables' => count($this->tables),
            'queries_run' => count($sql_queries)
        ]);
    }

    /**
     * Create/update specific tables only
     * 
     * @param array $table_names Array of table names to process
     */
    public function create_specific_tables(array $table_names): void
    {
        if (empty($table_names) || empty($this->tables)) {
            $this->logger->debug('No tables to update specifically');
            return;
        }

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_queries = [];

        foreach ($table_names as $table_name) {
            if (!isset($this->tables[$table_name])) {
                $this->logger->warning("Table {$table_name} not registered, skipping");
                continue;
            }

            $full_table_name = $wpdb->prefix . $table_name;
            $table_data = $this->tables[$table_name];
            $schema = is_array($table_data) ? $table_data['schema'] : $table_data;
            
            $sql = str_replace('{table_name}', $full_table_name, $schema);
            $sql = str_replace('{charset_collate}', $wpdb->get_charset_collate(), $sql);
            $sql_queries[] = $sql;
            
            $this->logger->info("Updating specific table: {$full_table_name}");
        }

        if (!empty($sql_queries)) {
            $results = dbDelta($sql_queries);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                foreach ($results as $result) {
                    $this->logger->info("Selective database update: " . $result);
                }
            }
        }
    }

    /**
     * Check if database needs upgrade and run it (lightweight check)
     * 
     * This should primarily be called during runtime checks, but also has
     * a safety net for edge cases. Uses cached version check to avoid
     * repeated database queries.
     */
    public function maybe_upgrade(): void
    {
        if (! $this->is_database_current()) {
			$installed_version = get_option($this->version_option_name);
            $this->logger->info("Database upgrade needed during maybe_upgrade", [
                'from' => $installed_version ?: 'none',
                'to' => $this->version
            ]);
            $this->upgrade_database($installed_version);
        }
    }

    /**
     * Force database upgrade check - used during activation
     * 
     * This is more explicit and always checks regardless of caching.
     * Should be called during plugin activation to ensure database is current.
     */
    public function check_and_upgrade(): void
    {
        // Clear static cache to ensure fresh check
        self::$is_current_cached = null;
        
        $installed_version = get_option($this->version_option_name);
        
        $this->logger->info("Checking database version during activation", [
            'installed' => $installed_version ?: 'none',
            'current' => $this->version
        ]);

        if ($installed_version !== $this->version) {
            $this->logger->info("Database upgrade needed during activation", [
                'from' => $installed_version ?: 'none',
                'to' => $this->version
            ]);
            
            $this->upgrade_database($installed_version);
        } else {
            $this->logger->debug("Database version is current");
        }
    }

    /**
     * Get a cached database version check to avoid repeated queries
     * 
     * Used for runtime checks that shouldn't hit the database every time.
     * 
     * @return bool True if database version is current
     */
    public function is_database_current(): bool
    {
        if (self::$is_current_cached === null) {
            $installed_version = get_option($this->version_option_name);
            self::$is_current_cached = ($installed_version === $this->version);
            
            $this->logger->debug("Cached database version check", [
                'installed' => $installed_version ?: 'none',
                'current' => $this->version,
                'is_current' => self::$is_current_cached
            ]);
        }
        
        return self::$is_current_cached;
    }

    /**
     * Upgrade database from old version to current version
     * 
     * **WordPress Actions Fired (in order):**
     * 1. `{hook_prefix}_database_upgrade_start` - Before upgrade begins
     * 2. `{hook_prefix}_database_upgrade` - General upgrade action
     * 3. `{hook_prefix}_database_upgrade_to_{version}` - Version-specific actions
     * 4. `{hook_prefix}_database_upgrade_complete` - After successful completion
     * 5. `{hook_prefix}_database_upgrade_failed` - On failure (with exception)
     * 
     * @param string|null $old_version Previous database version
     * @throws \Exception If upgrade fails
     */
    private function upgrade_database(?string $old_version): void
    {
        $start_time = microtime(true);
        
        try {
            $this->logger->info("Starting database upgrade", [
                'from' => $old_version ?: 'none',
                'to' => $this->version,
                'plugin' => $this->plugin_name
            ]);

            // Fire pre-upgrade action
            do_action($this->hook_prefix . '_database_upgrade_start', $old_version, $this->version, $this->core);

            // Always run dbDelta to ensure tables are up to date
            $this->create_tables();

            // Fire version-specific upgrade actions
            if ($old_version) {
                $this->fire_version_upgrade_actions($old_version);
            }

            // Update version after successful upgrade
            $this->update_version();

            // Clear the cached version check since we just upgraded
            self::$is_current_cached = true;

            // Fire post-upgrade action for external integrations
            do_action($this->hook_prefix . '_database_upgrade_complete', $old_version, $this->version, $this->core);

            $duration = microtime(true) - $start_time;
            
            $this->logger->info("Database upgrade completed successfully", [
                'from' => $old_version ?: 'none',
                'to' => $this->version,
                'plugin' => $this->plugin_name,
                'duration_ms' => round($duration * 1000, 2)
            ]);

        } catch (\Exception $e) {
            // Clear cached version on error
            self::$is_current_cached = null;
            
            $duration = microtime(true) - $start_time;
            
            $this->logger->error("Database upgrade failed", [
                'from' => $old_version ?: 'none',
                'to' => $this->version,
                'plugin' => $this->plugin_name,
                'error' => $e->getMessage(),
                'duration_ms' => round($duration * 1000, 2)
            ]);
            
            // Fire error action
            do_action($this->hook_prefix . '_database_upgrade_failed', $old_version, $this->version, $e, $this->core);
            
            throw $e;
        }
    }

    /**
     * Fire version-specific upgrade actions using WordPress hooks
     * 
     * **Actions fired:**
     * - `{hook_prefix}_database_upgrade` - General upgrade action
     * - `{hook_prefix}_database_upgrade_to_{sanitized_version}` - For each version
     * 
     * @param string $old_version Previous database version
     */
    private function fire_version_upgrade_actions(string $old_version): void
    {
        $this->logger->info("Firing version upgrade actions", [
            'old_version' => $old_version,
            'new_version' => $this->version
        ]);

        // Fire a general upgrade action with version info
        do_action($this->hook_prefix . '_database_upgrade', $old_version, $this->version, $this->core);

        // Fire version-specific actions for each version between old and new
        $versions_to_process = $this->get_versions_between($old_version, $this->version);
        
        foreach ($versions_to_process as $version) {
            $sanitized_version = str_replace('.', '_', $version);
            $action_name = $this->hook_prefix . '_database_upgrade_to_' . $sanitized_version;
            
            $this->logger->debug("Firing version-specific action: {$action_name}");
            
            do_action($action_name, $old_version, $this->version, $this->core);
        }
    }

    /**
     * Get list of versions that need processing between old and current version
     * 
     * This is a simple implementation that returns only the current version.
     * You could expand this to include intermediate versions if needed for
     * complex upgrade paths.
     * 
     * @param string $old_version Previous version
     * @param string $current_version Target version
     * @return array Array of versions to process
     */
    private function get_versions_between(string $old_version, string $current_version): array
    {
        // For now, just return the current version
        // You could expand this to include intermediate versions if needed
        return [$current_version];
    }

    /**
     * Get current database version from WordPress options
     * 
     * @return string|false Current version or false if not set
     */
    public function get_version(): string|false
    {
        return get_option($this->version_option_name);
    }

    /**
     * Drop all registered tables
     * 
     * Used during plugin uninstallation. Removes all registered tables
     * and cleans up the version option.
     */
    public function drop_tables(): void
    {
        if (empty($this->tables)) {
            $this->logger->debug('No tables to drop');
            return;
        }
        
        global $wpdb;
        
        $dropped_tables = [];

        foreach ($this->tables as $table_name => $table_data) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            if ($this->table_exists($full_table_name)) {
                $result = $wpdb->query("DROP TABLE IF EXISTS {$full_table_name}");
                
                if ($result !== false) {
                    $dropped_tables[] = $full_table_name;
                    $this->logger->info("Dropped table: {$full_table_name}");
                } else {
                    $this->logger->error("Failed to drop table: {$full_table_name}");
                }
            } else {
                $this->logger->debug("Table does not exist, skipping drop: {$full_table_name}");
            }
        }

        // Clean up version option
        delete_option($this->version_option_name);
        
        // Clear cached version
        self::$is_current_cached = null;
        
        $this->logger->info("Database cleanup completed", [
            'tables_dropped' => count($dropped_tables),
            'tables' => $dropped_tables
        ]);
    }

    /**
     * Get table name with WordPress prefix
     * 
     * @param string $table_name Table name without prefix
     * @return string Full table name with WordPress prefix
     */
    public function get_table_name(string $table_name): string
    {
        global $wpdb;
        return $wpdb->prefix . $table_name;
    }

    /**
     * Get table metadata for debugging/introspection
     * 
     * @param string $table_name Table name
     * @return array|null Metadata array or null if table not found
     */
    public function get_table_metadata(string $table_name): ?array
    {
        if (!isset($this->tables[$table_name])) {
            return null;
        }
        
        $table_data = $this->tables[$table_name];
        return is_array($table_data) ? $table_data['metadata'] : null;
    }

    /**
     * Check if a table exists in the database
     * 
     * @param string $table_name Full table name with prefix
     * @return bool True if table exists
     */
    private function table_exists(string $table_name): bool
    {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        return $result === $table_name;
    }

    /**
     * Generate a unique version option name based on plugin name
     * 
     * @param string $plugin_name Plugin name
     * @return string WordPress option name for storing database version
     */
    private function generate_version_option_name(string $plugin_name): string
    {
        $safe_name = sanitize_key($plugin_name);
        return $safe_name . '_db_version';
    }

    /**
     * Update the database version in WordPress options
     */
    private function update_version(): void
    {
        update_option($this->version_option_name, $this->version);
        
        $this->logger->debug("Database version updated", [
            'version' => $this->version,
            'option_name' => $this->version_option_name
        ]);
    }
}