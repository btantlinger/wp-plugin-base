<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use WebMoves\PluginBase\Database\DatabaseUninstaller;
use WebMoves\PluginBase\Plugin\TextDomainLoader;
use WebMoves\PluginBase\Contracts\Database\DatabaseManager;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Plugin\DependencyManager;
use WebMoves\PluginBase\Plugin\DependencyNotice;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Contracts\Settings\SettingsProcessor;
use WebMoves\PluginBase\Contracts\Templates\TemplateRenderer;
use WebMoves\PluginBase\Database\DefaultDatabaseManager;
use WebMoves\PluginBase\Logging\LoggerFactory;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Settings\DefaultSettingsManagerFactory;
use WebMoves\PluginBase\Templates\DefaultTemplateRenderer;
use WebMoves\PluginBase\Database\DatabaseInstaller;
use WebMoves\PluginBase\Database\DatabaseVersionChecker;
use WebMoves\PluginBase\Contracts\Configuration\Configuration;
use WebMoves\PluginBase\Contracts\Settings\FlashData;
use WebMoves\PluginBase\Settings\DefaultFlashData;
use WebMoves\PluginBase\Settings\DefaultSettingsProcessor;
use WebMoves\PluginBase\Logging\WPCLIHandler;
use WebMoves\PluginBase\Synchronizers\DatabaseSyncService;
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
	|
	| Example: 'required_plugins' => [
	|   'woocommerce/woocommerce.php' => ['name' => 'WooCommerce', 'min_version' => '8.0.0'],
	|   'acf/acf.php' => 'Advanced Custom Fields'
	| ]
	|
	*/
	'dependencies' => [
		'required_plugins' => [

		],
	],


	/*
	|--------------------------------------------------------------------------
	| Services
	|--------------------------------------------------------------------------
	|
	| Non-component services, utilities, data objects, API clients, etc.
	| These are registered in the container but don't implement Component.
	|
	*/
	'services' => [
		// Example framework services - these could be in core-dependencies.php instead
		// 'cache.manager' => create(CacheManager::class),
		// 'http.client' => create(HttpClient::class)->constructor(get('plugin.name')),

		// Plugin-specific services would go here or be overridden in plugin configs
		DatabaseManager::class => create(DefaultDatabaseManager::class)
			->constructor(
				get(PluginCore::class),
				get(Configuration::class)
			),
		//ComponentManager::class => create(DefaultComponentManager::class),

		SettingsManagerFactory::class => create(DefaultSettingsManagerFactory::class)
			->constructor(get(PluginMetadata::class)),
		SettingsProcessor::class => create(DefaultSettingsProcessor::class)
			->constructor(get("plugin.text_domain")),

		TemplateRenderer::class => create(DefaultTemplateRenderer::class)
			->constructor(get(PluginCore::class)),

		// Logger Factory
		LoggerFactory::class => create(LoggerFactory::class)
			->constructor(get(Configuration::class), get('plugin.name')),

		// Default logger (for backward compatibility)
		LoggerInterface::class => factory(function($container){
			return $container->get(LoggerFactory::class)->create('default');
		}),
		"logger.default" => factory(function($container){
			return $container->get(LoggerInterface::class);
		}),
		"logger.app" => factory(function($container){
			return $container->get(LoggerFactory::class)->create('app');
		}),
		"logger.database" => factory(function($container){
			return $container->get(LoggerFactory::class)->create('database');
		}),
		"logger.api" => factory(function($container){
			return $container->get(LoggerFactory::class)->create('api');
		}),
	],


	/*
	|--------------------------------------------------------------------------
	| Components
	|--------------------------------------------------------------------------
	|
	| Components that implement Component and will be registered
	| with the DefaultComponentManager for lifecycle management.
	|
	*/
	'components' => [
		// Core framework components
		DependencyManager::class => create(DependencyManager::class)
			->constructor(get(PluginCore::class)),
		DependencyNotice::class => create(DependencyNotice::class)
			->constructor(get(DependencyManager::class)),
		DatabaseInstaller::class => create(DatabaseInstaller::class)
			->constructor(get(DatabaseManager::class), get(LoggerInterface::class)),
		DatabaseVersionChecker::class => create(DatabaseVersionChecker::class)
			->constructor(get(DatabaseManager::class), get(LoggerInterface::class)),
		DatabaseUninstaller::class => create( DatabaseUninstaller::class)
			->constructor(
				get(DatabaseManager::class),
				get(PluginMetadata::class),
				get(Configuration::class),
				get(LoggerInterface::class)
			),
		TextDomainLoader::class => create( TextDomainLoader::class)->constructor(get( PluginMetadata::class)),

		FlashData::class => create(DefaultFlashData::class),
	],



	/*
	|--------------------------------------------------------------------------
	| Logging Configuration
	|--------------------------------------------------------------------------
	*/
	'logging' => [
		'channels' => [
			'default' => [
				'handlers' => ['stream', 'error_log', 'console'],
				'processors' => [],
			],
			'app' => [
				'handlers' => ['stream', 'console'],
				'processors' => [],
			],
			'database' => [
				'handlers' => ['stream', 'console'],
				'processors' => [],
			],
			'api' => [
				'handlers' => ['stream', 'error_log', 'console'],
				'processors' => [],
			],
			'sync' => [
				'handlers' => ['stream', 'error_log', 'console'],
				'processors' => [],
			],
			// Add CLI channel
			'cli' => [
				'handlers' => ['console'],
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
			// Add console handler
			'console' => [
				'class' => WPCLIHandler::class,
				'constructor' => [
					'level' => Level::Debug,
				],
				'formatter' => 'cli' , // Conditional formatter
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
			// Add CLI-specific formatter (cleaner output)
			'cli' => [
				'class' => LineFormatter::class,
				'constructor' => [
					'format' => "%level_name%: %message% %context% %extra%\n",  // Simpler format for CLI
					'dateFormat' => 'Y-m-d H:i:s',
					'allowInlineLineBreaks' => true,
					'ignoreEmptyContextAndExtra' => true, // This hides empty context/extra
				],
			],
		],
	],


	/*
	|--------------------------------------------------------------------------
	| Database Configuration
	|--------------------------------------------------------------------------
	|
	| Database related settings
	| version: The version of the database, changing this triggers a database
	| upgrade.
	|
	| tables: key => value associative array where the key is the table
	| name (without wp db prefix) and the value is an SQL create table statement.
	| Placeholders can be used in the SQL.
	| - {table_name} is replaced with the key/table name.
	| - {charset_collate} is replaced with the charset collate of the wp db.
	|
	|
	*/
	'database' => [
		'version' => '1.0.4',
		'delete_tables_on_uninstall' => true,
		'delete_options_on_uninstall' => true,
		'tables' => [
			DatabaseSyncService::get_table_name(false) => DatabaseSyncService::get_table_definition(),


/*			'user_activity_log' => "CREATE TABLE {table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            activity_type varchar(50) NOT NULL,
            activity_data longtext,
            ip_address varchar(45),
            user_agent text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_activity_type (activity_type),
            KEY idx_created_at (created_at)
        ) {charset_collate};",

			'plugin_settings' => "CREATE TABLE {table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            setting_key varchar(191) NOT NULL,
            setting_value longtext,
            setting_group varchar(100) DEFAULT 'general',
            is_autoload tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_setting_key_group (setting_key, setting_group),
            KEY idx_setting_group (setting_group),
            KEY idx_autoload (is_autoload)
        ) {charset_collate};"
*/
		]
	],


];