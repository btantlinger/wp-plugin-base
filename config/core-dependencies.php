<?php

use WebMoves\PluginBase\Contracts\DatabaseManagerInterface;
use WebMoves\PluginBase\Contracts\HandlerManagerInterface;
use WebMoves\PluginBase\Contracts\HookManagerInterface;
use WebMoves\PluginBase\DatabaseManager;
use WebMoves\PluginBase\EventHandlers\HandlerManager;
use WebMoves\PluginBase\HookManager;
use function DI\create;
use function DI\get;

return [
    HookManagerInterface::class => create(HookManager::class),
    
    DatabaseManagerInterface::class => create(DatabaseManager::class)
        ->constructor(get('plugin.version'), get('plugin.name')),
    
    HandlerManagerInterface::class => create(HandlerManager::class)
        ->constructor(get(HookManagerInterface::class)),
];