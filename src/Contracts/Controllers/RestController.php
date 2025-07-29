<?php

namespace WebMoves\PluginBase\Contracts\Controllers;

interface RestController extends Controller {
	/**
	 * Get the REST namespace (e.g., 'my-plugin/v1')
	 */
	public function get_namespace(): string;

	/**
	 * Get HTTP methods this endpoint supports
	 */
	public function get_methods(): array;

	/**
	 * Get the permission callback for this endpoint
	 */
	public function get_permission_callback(): callable;

	/**
	 * Get the arguments schema for validation
	 */
	public function get_args(): array;

	/**
	 * Create REST nonce for authentication
	 */
	public function create_rest_nonce(): string;

	/**
	 * Handle REST requests
	 */
	public function handle_rest_request(\WP_REST_Request $request): \WP_REST_Response|\WP_Error;

}