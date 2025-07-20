<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Components\ComponentManager;
use WebMoves\PluginBase\Contracts\Components\ComponentManagerInterface;
use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use WebMoves\PluginBase\Components\Support\DependencyManager;
use WebMoves\PluginBase\Components\Support\DependencyNotice;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactoryInterface;
use WebMoves\PluginBase\Contracts\Templates\TemplateRendererInterface;
use WebMoves\PluginBase\DatabaseManager;
use WebMoves\PluginBase\Logging\LoggerFactory;
use WebMoves\PluginBase\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Templates\TemplateRenderer;
use function DI\create;
use function DI\factory;
use function DI\get;

return [
	/*
	|--------------------------------------------------------------------------
	| Plugin Dependencies
	|--------------------------------------------------------------------------
	|
	| Define the list of plugins that must be installed and activated
	| for the plugin to function properly. The array key should be the plugin's
	| path relative to the plugins directory, and the value should be the plugin's
	| display name.
	|
	*/
	'dependencies' => [
		'required_plugins' => [
			'woocommerce/woocommerce.php' => 'FooCommerce',
			'advanced-custom-fields/acf.php' => 'Advanced Custom Fields',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Components
	|--------------------------------------------------------------------------
	|
	| Components that implement ComponentInterface and will be registered
	| with the ComponentManager for lifecycle management.
	|
	*/
	'components' => [
		// Core framework components
		DependencyManager::class => create(DependencyManager::class)->constructor(get(PluginCoreInterface::class)),
		DependencyNotice::class => create(DependencyNotice::class)->constructor(get(DependencyManager::class)),
	],

	/*
	|--------------------------------------------------------------------------
	| Services
	|--------------------------------------------------------------------------
	|
	| Non-component services, utilities, data objects, API clients, etc.
	| These are registered in the container but don't implement ComponentInterface.
	|
	*/
	'services' => [
		// Example framework services - these could be in core-dependencies.php instead
		// 'cache.manager' => create(CacheManager::class),
		// 'http.client' => create(HttpClient::class)->constructor(get('plugin.name')),

		// Plugin-specific services would go here or be overridden in plugin configs
		DatabaseManagerInterface::class => create(DatabaseManager::class)->constructor(get(PluginCoreInterface::class)),
		ComponentManagerInterface::class => create(ComponentManager::class),

		SettingsManagerFactoryInterface::class => create(SettingsManagerFactory::class),
		TemplateRendererInterface::class => create(TemplateRenderer::class)->constructor(get(PluginCoreInterface::class)),

		// Logger Factory
		LoggerFactory::class => create(LoggerFactory::class)
			->constructor(get('plugin.name'), get('plugin.file')),

		// Default logger (for backward compatibility)
		LoggerInterface::class => factory(function ($container) {
			return $container->get(LoggerFactory::class)->create();
		}),

		'logger.default' => factory(function ($container) {
			return $container->get(LoggerFactory::class)->create();
		}),

		// Channel-specific loggers
		'logger.app' => factory(function ($container) {
			return LoggerFactory::createLogger(
				$container->get('plugin.name'),
				$container->get('plugin.file'),
				'app'
			);
		}),

		'logger.database' => factory(function ($container) {
			return LoggerFactory::createLogger(
				$container->get('plugin.name'),
				$container->get('plugin.file'),
				'database'
			);
		}),

		'logger.api' => factory(function ($container) {
			return LoggerFactory::createLogger(
				$container->get('plugin.name'),
				$container->get('plugin.file'),
				'api'
			);
		}),
	],


	/*
	|--------------------------------------------------------------------------
	| Logging Configuration
	|--------------------------------------------------------------------------
	*/
	'logging' => [
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
					'stream' => WP_CONTENT_DIR . '/debug.log',
					'level' => Level::Debug,
				],
				'formatter' => 'line',
			],
			'error_log' => [
				'class' => ErrorLogHandler::class,
				'constructor' => [
					'messageType' => ErrorLogHandler::OPERATING_SYSTEM,
					'level' => Level::Error,
				],
				'formatter' => 'line',
			],
		],
		'formatters' => [
			'line' => [
				'class' => LineFormatter::class,
				'constructor' => [
					'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
					'dateFormat' => 'Y-m-d H:i:s',
				],
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Database Configuration
	|--------------------------------------------------------------------------
	|
	| Database related settings including migration settings, table prefixes, etc.
	|
	*/
	'database' => [
		'auto_migrate' => true,
		'backup_before_migration' => false,
		'migration_timeout' => 30, // seconds
	],

	/*
	|--------------------------------------------------------------------------
	| Asset Configuration
	|--------------------------------------------------------------------------
	|
	| Configuration for asset loading, versioning, and optimization.
	|
	*/
	'assets' => [
		'version_strategy' => 'file_time', // 'file_time', 'plugin_version', 'manual'
		'minify_in_production' => true,
		'combine_css' => false,
		'combine_js' => false,
	],
];