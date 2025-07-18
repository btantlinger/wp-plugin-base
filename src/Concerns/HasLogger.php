<?php

namespace WebMoves\PluginBase\Concerns;

use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Logging\LoggerFactory;

trait HasLogger {
	use PluginCoreHelper;

	protected ?LoggerInterface $logger = null;

	protected string $log_channel = 'default';

	protected function get_log_channel(): string {
		return $this->log_channel;
	}

	public function log(): LoggerInterface {
		if (!$this->logger) {
			$core = $this->get_plugin_core();
			if ($core) {
				$this->logger = $core->get_logger($this->get_log_channel());
			} else {
				$this->logger = LoggerFactory::createLogger('plugin', null, $this->get_log_channel());
			}
		}
		return $this->logger;
	}
}