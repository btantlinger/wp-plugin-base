<?php

namespace WebMoves\PluginBase\Contracts\Configuration;

interface Configuration
{
	/**
	 * Load configuration files
	 */
	public function load(): void;

	/**
	 * Get configuration value using dot notation
	 */
	public function get(string $key, $default = null);

	/**
	 * Get all configuration
	 */
	public function all(): array;

	/**
	 * Check if configuration key exists
	 */
	public function has(string $key): bool;

	/**
	 * Set configuration value at runtime
	 */
	public function set(string $key, $value): void;

	/**
	 * Get required plugins from configuration
	 */
	public function getRequiredPlugins(): array;

	/**
	 * Get services from configuration
	 */
	public function getServices(): array;


	/**
	 * Get components from configuration
	 */
	public function getComponents(): array;

	/**
	 * Get logging configuration
	 */
	public function getLoggingConfig(): array;
}