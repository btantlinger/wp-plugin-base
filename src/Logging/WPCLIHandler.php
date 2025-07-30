<?php

namespace WebMoves\PluginBase\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class WPCLIHandler extends AbstractProcessingHandler
{
    public function __construct($level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * Check if this handler can handle the record
     * Only handle if we're in WP-CLI context
     */
    public function isHandling(LogRecord $record): bool
    {
        // If WP-CLI is not available, don't handle anything
        if (!defined('WP_CLI') || !WP_CLI) {
            return false;
        }

        return parent::isHandling($record);
    }

    protected function write(LogRecord $record): void
    {
        // Double-check (though isHandling should prevent this)
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        // Use the formatted message (this uses the formatter if set)
        $message = rtrim((string) $record->formatted);

        // Map Monolog levels to WP-CLI output methods
        switch ($record->level) {
            case Level::Emergency:
            case Level::Alert:
            case Level::Critical:
            case Level::Error:
                \WP_CLI::error($message, false);
                break;

            case Level::Warning:
                \WP_CLI::warning($message);
                break;

            case Level::Notice:
                \WP_CLI::success($message);
                break;

            case Level::Info:
            case Level::Debug:
            default:
                \WP_CLI::log($message);
                break;
        }
    }
}