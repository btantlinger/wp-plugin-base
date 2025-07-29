<?php

namespace WebMoves\PluginBase\Contracts\Controllers;

/**
 * Interface for AJAX controllers
 */
interface AjaxController extends Controller
{
    /**
     * Create AJAX nonce for this controller's action
     *
     * @param array $params Additional parameters for nonce generation
     * @return string The nonce value
     */
    public function create_ajax_nonce(array $params = []): string;

    /**
     * Handle AJAX requests
     * This method is called by WordPress AJAX hooks
     */
    public function handle_ajax_request(): void;
}