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
    
    // Static reference to factory instances by plugin name
    private static array $instances = [];

    // Current context plugin name for static access
    private static ?string $current_context = null;

    // Cache for logger instances by channel
    private array $loggers = [];

    public function __construct(Configuration $config, string $plugin_name)
    {
        $this->config = $config->get('logging', $this->get_default_config());
        $this->plugin_name = $plugin_name;

        // Auto-register this instance for static access using plugin name as key
        self::$instances[$plugin_name] = $this;

        // Set as current context if none is set
        if (self::$current_context === null) {
            self::$current_context = $plugin_name;
        }
    }

    /**
     * Set the current plugin context for static logger access
     */
    public static function set_context(string $plugin_name): void
    {
        if (!isset(self::$instances[$plugin_name])) {
            throw new \RuntimeException("LoggerFactory for plugin '{$plugin_name}' has not been initialized yet");
        }
        self::$current_context = $plugin_name;
    }

    /**
     * Get the current plugin context
     */
    public static function get_context(): ?string
    {
        return self::$current_context;
    }

    /**
     * Get a factory instance by plugin name
     */
    public static function get_instance(string $plugin_name): ?self
    {
        return self::$instances[$plugin_name] ?? null;
    }

    /**
     * Create/get a logger instance statically (cached)
     * Optionally specify plugin_name to use a specific plugin's factory
     */
    public static function logger(string $channel = null, ?string $plugin_name = null): LoggerInterface
    {
        // Use specified plugin name, or fall back to current context
        $plugin_name = $plugin_name ?? self::$current_context;

        if ($plugin_name === null) {
            throw new \RuntimeException('LoggerFactory context not set. Call LoggerFactory::set_context() or pass plugin_name parameter.');
        }

        if (!isset(self::$instances[$plugin_name])) {
            throw new \RuntimeException("LoggerFactory for plugin '{$plugin_name}' has not been initialized yet");
        }

        return self::$instances[$plugin_name]->create($channel);
    }

    /**
     * Create/get a logger instance (cached)
     */
    public function create(string $channel = null): LoggerInterface
    {
        $channel = $channel ?? 'default';
        
        // Return cached instance if it exists
        if (isset($this->loggers[$channel])) {
            return $this->loggers[$channel];
        }
        
        // Create new logger instance
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

        // Cache and return
        $this->loggers[$channel] = $logger;
        return $logger;
    }

    /**
     * Clear cached loggers (useful for testing)
     */
    public function clear_cache(): void
    {
        $this->loggers = [];
    }

    /**
     * Get all cached logger instances
     */
    public function get_cached_loggers(): array
    {
        return $this->loggers;
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