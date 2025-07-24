<?php

namespace WebMoves\PluginBase\Concerns;

use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Logging\LoggerFactory;

trait HasLogger
{
    protected ?LoggerInterface $logger = null;

	protected string $logging_channel = 'default';

    public function log(): LoggerInterface
    {
        if (!$this->logger) {
            $this->logger = LoggerFactory::logger($this->logging_channel);
        }
        return $this->logger;
    }
}