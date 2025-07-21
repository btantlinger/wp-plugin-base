<?php

namespace WebMoves\PluginBase\Components\Database;

use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Database\DatabaseManager;
use WebMoves\PluginBase\Enums\Lifecycle;
use Psr\Log\LoggerInterface;

class DatabaseUninstaller implements Component
{
    public function __construct(
        private DatabaseManager $databaseManager,
        private LoggerInterface $logger,
        private bool $cleanupTables = false, // Safety flag - must be explicitly enabled
        private bool $cleanupOptions = true,  // Clean plugin options by default
        private string $optionPrefix = ''     // Plugin-specific option prefix
    ) {}

    public function register_on(): Lifecycle
    {
        return Lifecycle::UNINSTALL;
    }

    public function register(): void
    {
        $this->logger->info('Starting database cleanup during plugin uninstall', [
            'cleanup_tables' => $this->cleanupTables,
            'cleanup_options' => $this->cleanupOptions,
            'option_prefix' => $this->optionPrefix
        ]);
        
        if ($this->cleanupTables) {
            // WARNING: This permanently deletes data!
            $this->logger->warning('Dropping all plugin tables - this action cannot be undone');
            $this->databaseManager->drop_tables();
        } else {
            $this->logger->info('Table cleanup disabled - preserving data');
        }
        
        if ($this->cleanupOptions) {
            $this->cleanup_plugin_options();
        }
        
        $this->logger->info('Database cleanup completed');
    }

    private function cleanup_plugin_options(): void
    {
        if (empty($this->optionPrefix)) {
            $this->logger->warning('No option prefix provided - skipping option cleanup for safety');
            return;
        }
        
        global $wpdb;
        
        // Clean up plugin-specific options
        $option_pattern = $wpdb->esc_like($this->optionPrefix) . '%';
        
        $options_deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $option_pattern
        ));
        
        $this->logger->info('Plugin options cleaned up', [
            'prefix' => $this->optionPrefix,
            'options_deleted' => $options_deleted
        ]);
    }

    public function can_register(): bool
    {
        return true;
    }

    public function get_priority(): int
    {
        return 99; // Run last during uninstall
    }
}