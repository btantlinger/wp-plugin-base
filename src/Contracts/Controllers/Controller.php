<?php

namespace WebMoves\PluginBase\Contracts\Controllers;

use WebMoves\PluginBase\Contracts\Components\Component;

/**
 * Base controller interface for all request handlers
 *
 * Note: These are "controllers" in the WordPress sense - they handle
 * specific actions/routes rather than managing entire resources like
 * traditional MVC controllers.
 */

interface Controller extends Component
{
    /**
     * Get the action identifier for this controller
     */
    public function get_action(): string;

    /**
     * Create a URL for this controller's action
     * 
     * @param array $params Additional URL parameters
     * @param string|null $base_url Base URL
     * @return string The nonce-protected URL
     */
    public function create_action_url(array $params = []): string;

	public function get_nonce_key(): string;
}