<?php

namespace WebMoves\PluginBase\Components\Database;

use WebMoves\PluginBase\Contracts\Components\ComponentInterface;
use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Enums\Lifecycle;
use Psr\Log\LoggerInterface;

class DatabaseVersionChecker implements ComponentInterface
{
	public function __construct(
		private DatabaseManagerInterface $databaseManager,
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
		// Only register in admin or during cron to avoid frontend performance impact
		return true;//is_admin() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI);
	}

	public function get_priority(): int
	{
		return 5; // Lower priority - run after other init components
	}
}