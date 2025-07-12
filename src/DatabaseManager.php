<?php

namespace WebMoves\PluginBase;

class DatabaseManager implements \WebMoves\PluginBase\Contracts\DatabaseManagerInterface
{
    private string $version;
    private string $version_option_name;
    private array $tables = [];

    public function __construct(string $version, string $plugin_name = 'plugin-base')
    {
        $this->version = $version;
        $this->version_option_name = $this->generate_version_option_name($plugin_name);
    }

    /**
     * Generate a unique version option name based on plugin name
     *
     * @param string $plugin_name
     * @return string
     */
    private function generate_version_option_name(string $plugin_name): string
    {
        // Convert plugin name to a safe option name format
        $safe_name = sanitize_key($plugin_name);
        return $safe_name . '_db_version';
    }

    /**
     * Register a table schema
     *
     * @param string $table_name Table name (without prefix)
     * @param string $schema SQL schema
     * @return void
     */
    public function register_table(string $table_name, string $schema): void
    {
        $this->tables[$table_name] = $schema;
    }

    /**
     * Create all registered tables
     *
     * @return void
     */
    public function create_tables(): void
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        foreach ($this->tables as $table_name => $schema) {
            $table_name = $wpdb->prefix . $table_name;
            $sql = str_replace('{table_name}', $table_name, $schema);
            dbDelta($sql);
        }

        $this->update_version();
    }

    /**
     * Check if database needs upgrade
     *
     * @return void
     */
    public function maybe_upgrade(): void
    {
        $installed_version = get_option($this->version_option_name);

        if ($installed_version !== $this->version) {
            $this->upgrade_database($installed_version);
        }
    }

    /**
     * Upgrade database
     *
     * @param string|false $old_version
     * @return void
     */
    private function upgrade_database($old_version): void
    {
        // Create tables if they don't exist
        $this->create_tables();

        // Run version-specific upgrades
        if ($old_version) {
            $this->run_version_upgrades($old_version);
        }

        do_action('plugin_base_database_upgraded', $old_version, $this->version);
    }

    /**
     * Run version-specific database upgrades
     *
     * @param string $old_version
     * @return void
     */
    private function run_version_upgrades(string $old_version): void
    {
        // Example: version comparison and upgrades
        if (version_compare($old_version, '1.1.0', '<')) {
            // Run upgrades for version 1.1.0
            do_action('plugin_base_upgrade_to_1_1_0');
        }

        if (version_compare($old_version, '1.2.0', '<')) {
            // Run upgrades for version 1.2.0
            do_action('plugin_base_upgrade_to_1_2_0');
        }
    }

    /**
     * Update the database version
     *
     * @return void
     */
    private function update_version(): void
    {
        update_option($this->version_option_name, $this->version);
    }

    /**
     * Get current database version
     *
     * @return string|false
     */
    public function get_version(): string|false
    {
        return get_option($this->version_option_name);
    }

    /**
     * Drop all registered tables
     *
     * @return void
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
     *
     * @param string $table_name Table name without prefix
     * @return string
     */
    public function get_table_name(string $table_name): string
    {
        global $wpdb;
        return $wpdb->prefix . $table_name;
    }

    /**
     * Get the version option name being used
     *
     * @return string
     */
    public function get_version_option_name(): string
    {
        return $this->version_option_name;
    }
}