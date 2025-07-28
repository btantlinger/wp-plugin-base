<?php

namespace WebMoves\PluginBase\Contracts\Settings;
use WebMoves\PluginBase\Contracts\Components\Component;

interface FlashData extends Component
{
	/**
	 * Display all pending notices
	 */
	public function display_notices(): void;

	/**
	 * Add an admin notice
	 */
	public function add_notice(string $message, string $type = 'success', bool $dismissible = true): void;

	/**
	 * Add an error notice
	 */
	public function add_error(string $message, bool $dismissible = true): void;

	/**
	 * Add a success notice
	 */
	public function add_success(string $message, bool $dismissible = true): void;

	/**
	 * Add a warning notice
	 */
	public function add_warning(string $message, bool $dismissible = true): void;

	/**
	 * Add multiple field errors as notices
	 */
	public function add_field_errors(array $errors): void;

	/**
	 * Check if there are any error notices
	 */
	public function has_errors(): bool;

	/**
	 * Check if there are any notices of a specific type
	 */
	public function has_notices(string $type = null): bool;

	/**
	 * Get count of notices by type
	 */
	public function get_notice_count(string $type = null): int;

	/**
	 * Store form data for redisplay after validation errors
	 */
	public function set_form_data(string $form_key, array $data): void;

	/**
	 * Get form data for redisplay
	 */
	public function get_form_data(string $form_key): array;

	/**
	 * Store flash data for later retrieval
	 */
	public function set(string $key, $value): void;

	/**
	 * Get flash data and mark for deletion
	 */
	public function get(string $key, $default = null);

	/**
	 * Clear specific flash data item
	 */
	public function clear(string $key): void;

	/**
	 * Clean up expired items
	 */
	public function cleanup_expired(): void;

	/**
	 * Actually delete marked items
	 */
	public function cleanup_marked_items(): void;
}