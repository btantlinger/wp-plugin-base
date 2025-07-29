<?php

namespace WebMoves\PluginBase\Controllers;

use WebMoves\PluginBase\Contracts\Controllers\RestController;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Enums\Lifecycle;

abstract class AbstractRestController implements RestController  {

	protected string $nonce_key = 'X-WP-Nonce';

	protected string $nonce_action = 'wp_rest';

	protected int $priority = 1;


	private string $route;

	private string $namespace;



	private array $methods;

	private array $args;


	public function __construct(string $route, string $namespace, array $args = [], array $methods = ['GET']) {
		$this->route = $route;
		$this->namespace = $namespace;
		$this->methods = $methods;
		$this->args = $args;
	}

	abstract public function handle_rest_request(\WP_REST_Request $request): \WP_REST_Response|\WP_Error;

	public function get_nonce_key(): string
	{
		return $this->nonce_key;
	}

	public function create_rest_nonce(): string
	{
		return wp_create_nonce($this->nonce_action); // Always 'wp_rest' for REST API
	}

	public function create_action_url(array $params = []): string
	{
		$url = rest_url($this->get_namespace() . '/' . $this->route);
		return empty($params) ? $url : add_query_arg($params, $url);
	}

	public function register_on(): Lifecycle {
		return Lifecycle::INIT;
	}

	public function register(): void {
		add_action('rest_api_init', [$this, 'register_route']);
	}

	private function register_route(): void {
		register_rest_route(
			$this->get_namespace(),
			$this->get_action(),
			[
				'methods' => $this->get_methods(),
				'callback' => [$this, 'handle_rest_request'],
				'permission_callback' => $this->get_permission_callback(),
				'args' => $this->get_args(),
			]
		);
	}

	public function get_priority(): int {
		return $this->priority;
	}

	public function get_methods(): array {
		return $this->methods;
	}

	public function get_args(): array {
		return $this->args;
	}

	public function get_permission_callback(): callable {
		return [$this, 'can_execute_action'];
	}

	protected function can_execute_action(): bool {
		return current_user_can('manage_options');
	}

	/**
	 * Determines if the current user can register.
	 * This is the default implementation that can be overridden by subclasses.
	 * 
	 * @return bool True if the user is an administrator and has the capability to manage options, otherwise false.
	 */
	public function can_register(): bool {
		return $this->can_execute_action();
	}

	public function get_action(): string {
		return $this->route;
	}

	public function get_namespace(): string	{
		return $this->namespace;
	}
}