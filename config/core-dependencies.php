<?php

use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\Hooks\HookHandlerManagerInterface;
use WebMoves\PluginBase\Contracts\HookManagerInterface;
use WebMoves\PluginBase\DatabaseManager;
use WebMoves\PluginBase\Hooks\HookHandlerManager;
use WebMoves\PluginBase\HookManager;
use WebMoves\PluginBase\Contracts\Templates\TemplateRendererInterface;
use WebMoves\PluginBase\Templates\TemplateRenderer;
use function DI\create;
use function DI\get;

return [
	HookManagerInterface::class => create(HookManager::class),

	DatabaseManagerInterface::class => create(DatabaseManager::class)
        ->constructor(get('plugin.version'), get('plugin.name')),

	HookHandlerManagerInterface::class => create(HookHandlerManager::class)
        ->constructor(get(HookManagerInterface::class)),

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