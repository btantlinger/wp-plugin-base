<?php

namespace WebMoves\PluginBase\Contracts;

interface HookManagerInterface
{
	/**
	 * Register a callback for a WordPress hook
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Function/method to call on event
	 * @param int $priority Priority number. Lower numbers execute earlier
	 * @param int $accepted_args Number of arguments the callback accepts
	 * @return void
	 */
	public function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void;

	/**
	 * Register a callback for a WordPress filter
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Function/method to call on event
	 * @param int $priority Priority number. Lower numbers execute earlier
	 * @param int $accepted_args Number of arguments the callback accepts
	 * @return void
	 */
	public function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void;

	/**
	 * Remove a registered action hook
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Function/method to remove
	 * @param int $priority Priority number used when registering
	 * @return bool
	 */
	public function remove_action(string $hook, callable $callback, int $priority = 10): bool;

	/**
	 * Remove a registered filter hook
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Function/method to remove
	 * @param int $priority Priority number used when registering
	 * @return bool
	 */
	public function remove_filter(string $hook, callable $callback, int $priority = 10): bool;
}