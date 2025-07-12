<?php

namespace WebMoves\PluginBase\Contracts;

interface HandlerManagerInterface {
	/**
	 * Register a handler
	 *
	 * @param HandlerInterface $handler
	 * @return void
	 */
	public function register(HandlerInterface $handler): void;

	/**
	 * Initialize all registered handlers
	 *
	 * @return void
	 */
	public function initialize_handlers(): void;

	/**
	 * Get all registered handlers
	 *
	 * @return HandlerInterface[]
	 */
	public function get_handlers(): array;


	/**
	 * Get handlers by class name
	 *
	 * @param string $class_name
	 * @return HandlerInterface[]
	 */
	public function get_handlers_by_class(string $class_name): array;

	/**
	 * Check if handler is registered
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public function has_handler(string $class_name): bool;

	/**
	 * Remove a handler by class name
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public function remove_handler(string $class_name): bool;

}