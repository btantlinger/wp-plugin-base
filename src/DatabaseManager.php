<?php

namespace WebMoves\PluginBase;

use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;

class DatabaseManager implements DatabaseManagerInterface
{
    private string $version;
    private string $version_option_name;
    private string $plugin_name;
    private array $tables = [];
    private array $version_callbacks = [];
    
    protected LoggerInterface $logger;
    
    // Cache for database version check to avoid repeated queries
    private static ?bool $is_current_cached = null;

    public function __construct(PluginCoreInterface $core)
    {
        $db_ver = $core->get_database_version();
        $this->version = $db_ver ? $db_ver : '1.0.0';
        $this->plugin_name = $core->get_name();
        $this->version_option_name = $this->generate_version_option_name($this->plugin_name);
        $this->logger = $core->get_logger('database');
    }

    /**
     * Register a table schema with optional metadata
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
     * Register a callback for a specific version upgrade
     */
    public function register_version_callback(string $version, callable $callback): void
    {
        $this->version_callbacks[$version] = $callback;
    }

    /**
     * Create/update all registered tables using dbDelta
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
        // Note: dbDelta is already optimized - it only makes necessary changes
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
     * This should primarily be called during activation, but also has
     * a safety net for edge cases
     */
    public function maybe_upgrade(): void
    {
        $installed_version = get_option($this->version_option_name);

        if ($installed_version !== $this->version) {
            $this->logger->info("Database upgrade needed during maybe_upgrade", [
                'from' => $installed_version ?: 'none',
                'to' => $this->version
            ]);
            $this->upgrade_database($installed_version);
        }
    }

    /**
     * Force database upgrade check - used during activation
     * This is more explicit and always checks regardless of caching
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
     * Used for runtime checks that shouldn't hit the database every time
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
     */
    private function upgrade_database($old_version): void
    {
        $start_time = microtime(true);
        
        try {
            $this->logger->info("Starting database upgrade", [
                'from' => $old_version ?: 'none',
                'to' => $this->version,
                'plugin' => $this->plugin_name
            ]);

            // Always run dbDelta to ensure tables are up to date
            $this->create_tables();

            // Run version-specific callbacks
            if ($old_version) {
                $this->run_version_upgrades($old_version);
            }

            // Update version after successful upgrade
            $this->update_version();

            // Clear the cached version check since we just upgraded
            self::$is_current_cached = true;

            // Fire action for external integrations
            do_action($this->plugin_name . '_database_upgraded', $old_version, $this->version);

            $duration = microtime(true) - $start_time;
            
            // Log successful upgrade
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
            
            throw $e;
        }
    }

    /**
     * Run version-specific upgrade callbacks
     */
    private function run_version_upgrades(string $old_version): void
    {
        if (empty($this->version_callbacks)) {
            $this->logger->debug("No version callbacks registered");
            return;
        }

        // Sort versions to ensure proper order
        uksort($this->version_callbacks, 'version_compare');

        $callbacks_run = 0;
        
        foreach ($this->version_callbacks as $callback_version => $callback) {
            // Only run callbacks for versions newer than old version
            // but not newer than current version
            if (version_compare($old_version, $callback_version, '<') && 
                version_compare($callback_version, $this->version, '<=')) {
                
                try {
                    $this->logger->info("Running version callback", [
                        'version' => $callback_version,
                        'plugin' => $this->plugin_name
                    ]);
                    
                    $callback($old_version, $this->version);
                    $callbacks_run++;
                    
                    $this->logger->info("Version callback completed", [
                        'version' => $callback_version,
                        'plugin' => $this->plugin_name
                    ]);
                    
                } catch (\Exception $e) {
                    $this->logger->error("Version callback failed", [
                        'version' => $callback_version,
                        'plugin' => $this->plugin_name,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }
        }
        
        $this->logger->info("Version callbacks completed", [
            'callbacks_run' => $callbacks_run,
            'total_callbacks' => count($this->version_callbacks)
        ]);
    }

    /**
     * Get current database version
     */
    public function get_version(): string|false
    {
        return get_option($this->version_option_name);
    }

    /**
     * Drop all registered tables
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
     * Get table name with prefix
     */
    public function get_table_name(string $table_name): string
    {
        global $wpdb;
        return $wpdb->prefix . $table_name;
    }

    /**
     * Get table metadata for debugging/introspection
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
     * Check if a table exists
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
     */
    private function generate_version_option_name(string $plugin_name): string
    {
        $safe_name = sanitize_key($plugin_name);
        return $safe_name . '_db_version';
    }

    /**
     * Update the database version
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