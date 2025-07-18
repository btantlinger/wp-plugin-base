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
use WebMoves\PluginBase\Contracts\PluginCoreInterface;
use Psr\Log\LoggerInterface;
use function DI\create;
use function DI\get;
use function DI\factory;

return [
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
];