<?php

namespace WebMoves\PluginBase\Database;

use WebMoves\PluginBase\Contracts\Components\Component;
use WebMoves\PluginBase\Contracts\Database\DatabaseManager;
use WebMoves\PluginBase\Enums\Lifecycle;
use Psr\Log\LoggerInterface;

class DatabaseVersionChecker implements Component
{
	public function __construct(
		private DatabaseManager $databaseManager,
		private LoggerInterface $logger
	) {}

	public function register_on(): Lifecycle
	{
		return Lifecycle::INIT; // Run during WordPress init
	}

	public function register(): void
	{

		// Only perform expensive upgrade check if versions don't match
		// This provides a safety net for edge cases
		if (!$this->databaseManager->is_database_current()) {
			$this->logger->warning('Database version mismatch detected at runtime - running safety upgrade');
			$this->databaseManager->maybe_upgrade();
		} else {
			$this->logger->debug('Database version is current - no upgrade needed');
		}
	}

	public function can_register(): bool
	{
		return (!wp_doing_ajax() && !wp_doing_cron() && !wp_is_serving_rest_request()) && is_admin();
	}

	public function get_priority(): int
	{
		return 5; // Lower priority - run after other init components
	}
}