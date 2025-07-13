<?php

namespace WebMoves\PluginBase\Hooks;

use WebMoves\PluginBase\Contracts\Hooks\ComponentInterface;
use WebMoves\PluginBase\HookManager;

abstract class AbstractComponent implements ComponentInterface
{

    protected int $priority = 10;
    protected bool $should_load = true;

    public function __construct()
    {

    }

    /**
     * Get the priority for this handler
     *
     * @return int
     */
    public function get_priority(): int
    {
        return $this->priority;
    }

    /**
     * Check if this handler should be loaded
     *
     * @return bool
     */
    public function can_register(): bool
    {
        return $this->should_load;
    }

    /**
     * Add an action hook
     *
     * @param string $hook Hook name
     * @param string $method Method name to call
     * @param int $priority Priority
     * @param int $accepted_args Number of arguments
     * @return void
     */
    protected function add_action(string $hook, string $method, int $priority = 10, int $accepted_args = 1): void
    {
        add_action($hook, [$this, $method], $priority, $accepted_args);
    }

    /**
     * Add a filter hook
     *
     * @param string $hook Hook name
     * @param string $method Method name to call
     * @param int $priority Priority
     * @param int $accepted_args Number of arguments
     * @return void
     */
    protected function add_filter(string $hook, string $method, int $priority = 10, int $accepted_args = 1): void
    {
        add_filter($hook, [$this, $method], $priority, $accepted_args);
    }

    /**
     * Add a shortcode
     *
     * @param string $tag Shortcode tag
     * @param string $method Method name to call
     * @return void
     */
    protected function add_shortcode(string $tag, string $method): void
    {
        add_shortcode($tag, [$this, $method]);
    }

    /**
     * Check if we're in admin area
     *
     * @return bool
     */
    protected function is_admin(): bool
    {
        return is_admin();
    }

    /**
     * Check if we're in frontend
     *
     * @return bool
     */
    protected function is_frontend(): bool
    {
        return !is_admin() && !wp_doing_ajax() && !wp_doing_cron();
    }

    /**
     * Check if this is an AJAX request
     *
     * @return bool
     */
    protected function is_ajax(): bool
    {
        return wp_doing_ajax();
    }

    /**
     * Check if this is a cron request
     *
     * @return bool
     */
    protected function is_cron(): bool
    {
        return wp_doing_cron();
    }

    /**
     * Check if current user has capability
     *
     * @param string $capability
     * @return bool
     */
    protected function current_user_can(string $capability): bool
    {
        return current_user_can($capability);
    }

    /**
     * Get current user ID
     *
     * @return int
     */
    protected function get_current_user_id(): int
    {
        return get_current_user_id();
    }

    /**
     * Verify nonce
     *
     * @param string $nonce
     * @param string $action
     * @return bool
     */
    protected function verify_nonce(string $nonce, string $action): bool
    {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Log a message (requires a logger service to be registered)
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    protected function log(string $message, string $level = 'info'): void
    {
        if (function_exists('error_log')) {
            error_log(sprintf('[%s] %s: %s', strtoupper($level), get_class($this), $message));
        }
    }
}