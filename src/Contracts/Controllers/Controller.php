<?php

namespace WebMoves\PluginBase\Contracts\Controllers;

use WebMoves\PluginBase\Contracts\Components\Component;

/**
 * Base controller interface for all controllers
 */
interface Controller extends Component
{
    /**
     * WordPress nonce key
     */
    const NONCE_KEY = '_wpnonce';

    /**
     * Get the action identifier for this controller
     */
    public function get_action(): string;

    /**
     * Create a nonce URL for this controller's action
     * 
     * @param array $params Additional URL parameters
     * @param string|null $base_url Base URL (defaults to admin.php)
     * @return string The nonce-protected URL
     */
    public function create_action_url(array $params = [], ?string $base_url = null): string;
}