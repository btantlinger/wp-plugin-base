<?php

namespace WebMoves\PluginBase;

use WebMoves\PluginBase\Contracts\PluginCoreInterface;

class DatabaseManager implements \WebMoves\PluginBase\Contracts\DatabaseManagerInterface
{
    private string $version;
    private string $version_option_name;
    private string $plugin_name;
    private array $tables = [];
    private array $version_callbacks = [];

    public function __construct(PluginCoreInterface $core)
    {
		$db_ver = $core->get_database_version();
        $this->version = $db_ver ? $db_ver : '1.0.0';
        $this->plugin_name = $core->get_name();
        $this->version_option_name = $this->generate_version_option_name($this->plugin_name);
    }

    /**
     * Register a table schema
     */
    public function register_table(string $table_name, string $schema): void
    {
        $this->tables[$table_name] = $schema;
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
            return;
        }

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_queries = [];

        foreach ($this->tables as $table_name => $schema) {
            $table_name = $wpdb->prefix . $table_name;
            $sql = str_replace('{table_name}', $table_name, $schema);
            $sql_queries[] = $sql;
        }

        // Run dbDelta with all table schemas
        $results = dbDelta($sql_queries);

        // Log the results for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            foreach ($results as $result) {
                error_log("Database update: " . $result);
            }
        }
    }

    /**
     * Check if database needs upgrade and run it
     */
    public function maybe_upgrade(): void
    {
        $installed_version = get_option($this->version_option_name);

        if ($installed_version !== $this->version) {
            $this->upgrade_database($installed_version);
        }
    }

    /**
     * Upgrade database from old version to current version
     */
    private function upgrade_database($old_version): void
    {
        try {
            // Always run dbDelta to ensure tables are up to date
            $this->create_tables();

            // Run version-specific callbacks
            if ($old_version) {
                $this->run_version_upgrades($old_version);
            }

            // Update version after successful upgrade
            $this->update_version();

            // Fire action for external integrations
            do_action($this->plugin_name . '_database_upgraded', $old_version, $this->version);

            // Log successful upgrade
            error_log("Database upgraded for {$this->plugin_name} from {$old_version} to {$this->version}");

        } catch (\Exception $e) {
            error_log("Database upgrade failed for {$this->plugin_name}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Run version-specific upgrade callbacks
     */
    private function run_version_upgrades(string $old_version): void
    {
        if (empty($this->version_callbacks)) {
            return;
        }

        // Sort versions to ensure proper order
        uksort($this->version_callbacks, 'version_compare');

        foreach ($this->version_callbacks as $callback_version => $callback) {
            // Only run callbacks for versions newer than old version
            // but not newer than current version
            if (version_compare($old_version, $callback_version, '<') && 
                version_compare($callback_version, $this->version, '<=')) {
                
                try {
                    $callback($old_version, $this->version);
                    error_log("Version callback {$callback_version} executed for {$this->plugin_name}");
                } catch (Exception $e) {
                    error_log("Version callback {$callback_version} failed for {$this->plugin_name}: " . $e->getMessage());
                    throw $e;
                }
            }
        }
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
        global $wpdb;

        foreach ($this->tables as $table_name => $schema) {
            $table_name = $wpdb->prefix . $table_name;
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        }

        delete_option($this->version_option_name);
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
    }
}