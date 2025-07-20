<?php

namespace WebMoves\PluginBase\Enums;

enum Lifecycle: string {
    // Plugin lifecycle events
    case INSTALL = 'install';         // Plugin installation
    case ACTIVATE = 'activate';       // Plugin activation
    case DEACTIVATE = 'deactivate';   // Plugin deactivation  
    case UNINSTALL = 'uninstall';     // Plugin uninstall/deletion

    // Runtime lifecycle events
    case BOOTSTRAP = 'bootstrap';     // Very early, before WordPress fully loads
    case INIT = 'init';              // Standard init hook
    case ADMIN_INIT = 'admin_init';  // Admin-specific initialization
    case READY = 'ready';            // After everything is loaded

    /**
     * Get the WordPress hook name for this lifecycle
     */
    public function getHookName(): string
    {
        return match($this) {
            self::BOOTSTRAP => 'plugins_loaded',
            self::INIT => 'init',
            self::ADMIN_INIT => 'admin_init',
            self::READY => 'wp_loaded',
            // Plugin lifecycle events don't have direct WordPress hooks
            default => $this->value
        };
    }

    /**
     * Get the hook priority for this lifecycle
     */
    public function getHookPriority(): int
    {
        return match($this) {
            self::BOOTSTRAP => 1,  // Very early
            self::INIT => 10,      // Default priority
            self::ADMIN_INIT => 10, // Default priority
            self::READY => 10,     // Default priority
            default => 10
        };
    }

    /**
     * Check if this lifecycle uses WordPress hooks (vs plugin activation hooks)
     */
    public function usesWordPressHooks(): bool
    {
        return match($this) {
            self::BOOTSTRAP,
            self::INIT,
            self::ADMIN_INIT,
            self::READY => true,
            default => false
        };
    }

    /**
     * Get all runtime lifecycles (those that use WordPress hooks)
     */
    public static function getRuntimeLifecycles(): array
    {
        return array_filter(self::cases(), fn($lifecycle) => $lifecycle->usesWordPressHooks());
    }

    /**
     * Get all plugin management lifecycles
     */
    public static function getPluginLifecycles(): array
    {
        return array_filter(self::cases(), fn($lifecycle) => !$lifecycle->usesWordPressHooks());
    }
}