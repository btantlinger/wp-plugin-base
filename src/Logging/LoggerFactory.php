<?php

namespace WebMoves\PluginBase\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    public static function createLogger(string $plugin_name, ?string $plugin_file = null, ?string $channel = null): LoggerInterface
    {
        $factory = new static($plugin_name, $plugin_file);
        return $factory->create($channel);
    }

    private string $plugin_name;
    private ?string $plugin_file;
    private array $config;

    private function __construct(string $plugin_name, ?string $plugin_file = null)
    {
        $this->plugin_name = $plugin_name;
        $this->plugin_file = $plugin_file;
        $this->load_config();
    }

    private function create(string $channel = null): LoggerInterface
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

    private function load_config(): void
    {
        $config_file = $this->find_config_file();
        
        if ($config_file && file_exists($config_file)) {
            $this->config = require $config_file;
        } else {
            // Minimal default config
            $this->config = $this->get_default_config();
        }
    }

    private function find_config_file(): ?string
    {
        $search_paths = [];

        // 1. If plugin file is provided, look in that plugin's directory
        if ($this->plugin_file) {
            $plugin_dir = dirname($this->plugin_file);
            $search_paths[] = $plugin_dir . '/config/monolog.php';
            $search_paths[] = $plugin_dir . '/config/logging.php';
        }

        // 2. Look in current working directory (for CLI/development)
        $search_paths[] = getcwd() . '/config/monolog.php';
        $search_paths[] = getcwd() . '/config/logging.php';

        // 3. Look relative to where this factory is called from
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        foreach ($backtrace as $trace) {
            if (isset($trace['file'])) {
                $calling_dir = dirname($trace['file']);
                $search_paths[] = $calling_dir . '/config/monolog.php';
                $search_paths[] = $calling_dir . '/config/logging.php';
                
                // Also check parent directories (in case called from subdirectory)
                $parent_dir = dirname($calling_dir);
                $search_paths[] = $parent_dir . '/config/monolog.php';
                $search_paths[] = $parent_dir . '/config/logging.php';
            }
        }

        // Find first existing file
        foreach ($search_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
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
                        'level' => Logger::DEBUG,
                    ],
                ],
                'error_log' => [
                    'class' => ErrorLogHandler::class,
                    'constructor' => [
                        'messageType' => ErrorLogHandler::OPERATING_SYSTEM,
                        'level' => Logger::ERROR,
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