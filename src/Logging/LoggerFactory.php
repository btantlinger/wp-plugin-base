<?php

namespace WebMoves\PluginBase\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Contracts\Configuration\Configuration;

class LoggerFactory
{
    private string $plugin_name;

    private array $config;

    public function __construct(Configuration $config, string $plugin_name)
    {
		$this->config = $config->get('logging', $this->get_default_config());
		$this->plugin_name = $plugin_name;
    }

    public function create(string $channel = null): LoggerInterface
    {
        $channel = $channel ?? 'default';
        $channel_name = "{$this->plugin_name}.{$channel}";
        
        $logger = new Logger($channel_name);

        // Get channel config
        $channel_config = $this->config['channels'][$channel] ?? $this->config['channels']['default'];

        // Add handlers
        foreach ($channel_config['handlers'] as $handler_name) {
            $handler = $this->create_handler($handler_name);
            if ($handler) {
                $logger->pushHandler($handler);
            }
        }

        // Add processors
        foreach ($channel_config['processors'] as $processor) {
            $logger->pushProcessor($processor);
        }

        return $logger;
    }


    private function get_default_config(): array
    {
        return [
            'channels' => [
                'default' => [
                    'handlers' => ['stream', 'error_log'],
                    'processors' => [],
                ],
                'app' => [
                    'handlers' => ['stream'],
                    'processors' => [],
                ],
                'database' => [
                    'handlers' => ['stream'],
                    'processors' => [],
                ],
                'api' => [
                    'handlers' => ['stream', 'error_log'],
                    'processors' => [],
                ],
            ],
            'handlers' => [
                'stream' => [
                    'class' => StreamHandler::class,
                    'constructor' => [
                        'stream' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? WP_CONTENT_DIR . '/debug.log' : 'php://stderr',
                        'level' => \Monolog\Level::Debug,
                    ],
                ],
                'error_log' => [
                    'class' => ErrorLogHandler::class,
                    'constructor' => [
                        'messageType' => ErrorLogHandler::OPERATING_SYSTEM,
                        'level' => \Monolog\Level::Error,
                    ],
                ],
            ],
            'formatters' => [],
        ];
    }

    private function create_handler(string $handler_name): ?object
    {
        $handler_config = $this->config['handlers'][$handler_name] ?? null;
        if (!$handler_config) {
            return null;
        }

        $class = $handler_config['class'];
        $constructor_args = $handler_config['constructor'] ?? [];

        // Create handler
        $handler = new $class(...array_values($constructor_args));

        // Set formatter if specified
        if (isset($handler_config['formatter'])) {
            $formatter = $this->create_formatter($handler_config['formatter']);
            if ($formatter) {
                $handler->setFormatter($formatter);
            }
        }

        return $handler;
    }

    private function create_formatter(string $formatter_name): ?object
    {
        $formatter_config = $this->config['formatters'][$formatter_name] ?? null;
        if (!$formatter_config) {
            return null;
        }

        $class = $formatter_config['class'];
        $constructor_args = $formatter_config['constructor'] ?? [];

        return new $class(...array_values($constructor_args));
    }
}