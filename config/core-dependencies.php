<?php

use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\Components\ComponentManagerInterface;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactoryInterface;
use WebMoves\PluginBase\DatabaseManager;
use WebMoves\PluginBase\Components\ComponentManager;
use WebMoves\PluginBase\Contracts\Templates\TemplateRendererInterface;
use WebMoves\PluginBase\Templates\TemplateRenderer;
use WebMoves\PluginBase\Settings\SettingsManagerFactory;
use WebMoves\PluginBase\Logging\LoggerFactory;
use Psr\Log\LoggerInterface;
use function DI\create;
use function DI\get;
use function DI\autowire;
use function DI\factory;

return [

	DatabaseManagerInterface::class => create(DatabaseManager::class)
        ->constructor(get('plugin.version'), get('plugin.name')),

	ComponentManagerInterface::class => autowire(ComponentManager::class),

	// Settings Manager Factory
	SettingsManagerFactoryInterface::class => autowire(SettingsManagerFactory::class),

	// Logger Factory
	LoggerFactory::class => create(LoggerFactory::class)
		->constructor(get('plugin.name')),

	// Default logger (for backward compatibility)
	LoggerInterface::class => factory(function ($container) {
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


	// Templates renderer with proper plugin-specific configuration
	TemplateRendererInterface::class => function ($container) {
	    $template_dir = $container->get('plugin.path') . 'templates';
	    $renderer = new TemplateRenderer($template_dir);

	    // Set up global data with plugin-specific values
	    $renderer->set_global_data([
		    'text_domain' => $container->get('plugin.text_domain'),
		    'plugin_url' => $container->get('plugin.url'),
		    'plugin_path' => $container->get('plugin.path'),
		    'plugin_version' => $container->get('plugin.version'),
		    'plugin_name' => $container->get('plugin.name'),
	    ]);
	    return $renderer;
    },
];