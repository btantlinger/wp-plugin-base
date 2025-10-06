<?php

use WebMoves\PluginBase\Configuration\ConfigurationProviderFactory;
use WebMoves\PluginBase\Contracts\Plugin\PluginCore;
use WebMoves\PluginBase\Contracts\Plugin\PluginMetadata;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Contracts\Synchronizers\SyncService;
use WebMoves\PluginBase\Examples\Settings\ApiSettingsProvider;
use WebMoves\PluginBase\Examples\Settings\DemoSettingsProvider;
use WebMoves\PluginBase\Examples\Settings\MainPage;
use WebMoves\PluginBase\Examples\Settings\TestAbstractSettingsPage;
use WebMoves\PluginBase\Examples\Synchronizers\DummySynchronizer;
use WebMoves\PluginBase\Examples\Synchronizers\ProductSyncDummy;
use function DI\create;
use function DI\get;

/*
|--------------------------------------------------------------------------
| Base Plugin Configuration
|--------------------------------------------------------------------------
|
| This is the base configuration for the example plugin, containing only
| plugin-specific services and components. Framework features like sync
| are added using the ConfigurationProviderFactory.
|
*/
$baseConfig = [
	/*
	|--------------------------------------------------------------------------
	| Plugin Dependencies
	|--------------------------------------------------------------------------
	|
	| Define the list of plugins that must be installed and activated
	| for the plugin to function properly.
	|
	*/
	'dependencies' => [
		'required_plugins' => [
			// Example: 'woocommerce/woocommerce.php' => ['name' => 'WooCommerce', 'min_version' => '8.0.0'],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Services
	|--------------------------------------------------------------------------
	|
	| Plugin-specific services, utilities, data objects, API clients, etc.
	| These are registered in the container but don't implement Component.
	|
	*/
	'services' => [
		// Example plugin settings providers
		ApiSettingsProvider::class => create(ApiSettingsProvider::class)
			->constructor(get(SettingsManagerFactory::class), get(PluginMetadata::class)),
		DemoSettingsProvider::class => create(DemoSettingsProvider::class)
			->constructor(get(SettingsManagerFactory::class), get(PluginMetadata::class)),

		// Custom synchronizers for this example plugin
		DummySynchronizer::class => create(DummySynchronizer::class)
			->constructor(
				get(SyncService::class),
				get(PluginMetadata::class),
				get(SettingsManagerFactory::class),
			),
		ProductSyncDummy::class => create(ProductSyncDummy::class)
			->constructor(
				get(SyncService::class),
				get(PluginMetadata::class),
				get(SettingsManagerFactory::class),
			),

		// Base settings providers (sync feature will add more)
		'settings_providers' => [
			get(ApiSettingsProvider::class),
			get(DemoSettingsProvider::class)
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Components
	|--------------------------------------------------------------------------
	|
	| Plugin-specific components that implement Component and will be registered
	| with the DefaultComponentManager for lifecycle management.
	|
	*/
	'components' => [
		// Example plugin pages
		MainPage::class => create(MainPage::class)->constructor(get(PluginCore::class)),

		// Settings page that uses all settings providers
		TestAbstractSettingsPage::class => create(TestAbstractSettingsPage::class)
			->constructor(
				get(PluginCore::class),
				get('settings_providers'),
			),


	],

	/*
	|--------------------------------------------------------------------------
	| Database Configuration
	|--------------------------------------------------------------------------
	|
	| Plugin-specific database configuration. Framework features will add
	| their own tables through the configuration provider system.
	|
	*/
	'database' => [
		'version' => '1.0.4',
		'delete_tables_on_uninstall' => true,
		'delete_options_on_uninstall' => true,
		'tables' => [
			// Plugin-specific tables would go here
			// Sync tables will be added by the sync feature provider
		]
	],
];

/*
|--------------------------------------------------------------------------
| Enable Framework Features
|--------------------------------------------------------------------------
|
| Use the ConfigurationProviderFactory to enable framework features.
| Each feature provider handles its own configuration and merging logic.
|
*/
return ConfigurationProviderFactory::mergeFeatureConfigurations($baseConfig, ['sync'], [
	'sync' => [
		// Custom synchronizers for this plugin
		'synchronizers' => [
			get(DummySynchronizer::class),
			get(ProductSyncDummy::class),
		],
		// Sync page configuration
		'sync_page_slug' => 'test-plugin-sync',
		'sync_page_parent' => MainPage::PAGE_SLUG, // Top-level menu item
	]
]);