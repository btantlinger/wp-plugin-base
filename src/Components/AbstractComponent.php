<?php

namespace WebMoves\PluginBase\Components;

use WebMoves\PluginBase\Contracts\Components\ComponentInterface;

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

}