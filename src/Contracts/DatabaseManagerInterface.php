<?php

namespace WebMoves\PluginBase\Contracts;

interface DatabaseManagerInterface {

	/**
	 * Register a table schema with optional metadata
	 *
	 * @param string $table_name Table name (without prefix)
	 * @param string $schema SQL schema
	 * @param array $metadata Optional metadata for the table
	 * @return void
	 */
	public function register_table(string $table_name, string $schema, array $metadata = []): void;

	/**
	 * Register a version-specific upgrade callback
	 *
	 * @param string $version
	 * @param callable $callback
	 * @return void
	 */
	public function register_version_callback(string $version, callable $callback): void;

	/**
	 * Create/update all registered tables using dbDelta
	 *
	 * @return void
	 */
	public function create_tables(): void;

	/**
	 * Create/update specific tables only
	 *
	 * @param array $table_names Array of table names to update
	 * @return void
	 */
	public function create_specific_tables(array $table_names): void;

	/**
	 * Check if database needs upgrade and run it (lightweight check)
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void;

	/**
	 * Force database upgrade check - used during activation
	 *
	 * @return void
	 */
	public function check_and_upgrade(): void;

	/**
	 * Get a cached database version check to avoid repeated queries
	 *
	 * @return bool
	 */
	public function is_database_current(): bool;

	/**
	 * Get current database version
	 *
	 * @return string|false
	 */
	public function get_version(): string|false;

	/**
	 * Drop all registered tables
	 *
	 * @return void
	 */
	public function drop_tables(): void;

	/**
	 * Get table name with prefix
	 *
	 * @param string $table_name Table name without prefix
	 * @return string
	 */
	public function get_table_name(string $table_name): string;

	/**
	 * Get table metadata for debugging/introspection
	 *
	 * @param string $table_name Table name without prefix
	 * @return array|null
	 */
	public function get_table_metadata(string $table_name): ?array;
}