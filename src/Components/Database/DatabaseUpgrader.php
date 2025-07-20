<?php

namespace WebMoves\PluginBase\Components\Database;

use WebMoves\PluginBase\Contracts\Components\ComponentInterface;
use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Enums\Lifecycle;
use Psr\Log\LoggerInterface;

class DatabaseUpgrader implements ComponentInterface
{
	public function __construct(
		private DatabaseManagerInterface $databaseManager,
		private LoggerInterface $logger
	) {}

	public function register_on(): Lifecycle
	{
		return Lifecycle::ACTIVATE; // Run on every activation
	}

	public function register(): void
	{
		$this->logger->info('Checking database during plugin activation');

		// Force database upgrade check during activation
		// This handles plugin updates since they don't trigger activation hooks
		$this->databaseManager->check_and_upgrade();

		// Note: create_tables() is called within check_and_upgrade() if needed
		$this->logger->info('Database activation check completed');
	}

	public function can_register(): bool
	{
		return true;
	}

	public function get_priority(): int
	{
		return 1; // High priority - run early in activation
	}
}