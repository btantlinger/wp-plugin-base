<?php

namespace WebMoves\PluginBase\Database;

use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Database\DatabaseManager;
use WebMoves\PluginBase\Enums\Lifecycle;
use Psr\Log\LoggerInterface;

class DatabaseInstaller implements Component
{
	public function __construct(
		private DatabaseManager $databaseManager,
		private LoggerInterface $logger
	) {}

	public function register_on(): Lifecycle
	{
		return Lifecycle::INSTALL; // Only run on first installation
	}

	public function register(): void
	{
		$this->logger->info('Installing database tables for the first time');

		// Create all tables for first installation
		$this->databaseManager->create_tables();

		// The version will be set automatically by create_tables/upgrade process
		$this->logger->info('Database installation completed');
	}

	public function can_register(): bool
	{
		return true;
	}

	public function get_priority(): int
	{
		return 1; // Highest priority - create tables first
	}
}