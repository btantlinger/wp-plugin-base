<?php

namespace WebMoves\PluginBase\Contracts;

interface DatabaseManagerInterface {

	/**
	 * Register a table schema
	 *
	 * @param string $table_name Table name (without prefix)
	 * @param string $schema SQL schema
	 * @return void
	 */
	public function register_table(string $table_name, string $schema): void;
	/**
	 * Create all registered tables
	 *
	 * @return void
	 */

	public function create_tables(): void;

	/**
	 * Check if database needs upgrade
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void;



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
}