<?php

use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\Hooks\ComponentManagerInterface;
use WebMoves\PluginBase\Contracts\Settings\SettingsManagerFactoryInterface;
use WebMoves\PluginBase\DatabaseManager;
use WebMoves\PluginBase\Hooks\ComponentManager;
use WebMoves\PluginBase\Contracts\Templates\TemplateRendererInterface;
use WebMoves\PluginBase\Templates\TemplateRenderer;
use WebMoves\PluginBase\Settings\SettingsManagerFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
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

	// Logger configuration
	LoggerInterface::class => factory(function ($container) {
		$logger = new Logger($container->get('plugin.name'));
		
		// In WordPress, log to wp-content/debug.log if WP_DEBUG_LOG is enabled
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			$log_file = WP_CONTENT_DIR . '/debug.log';
			$logger->pushHandler(new StreamHandler($log_file, Logger::DEBUG));
		}
		
		// Always log errors to PHP error log as fallback
		$logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::ERROR));
		
		return $logger;
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