<?php

namespace WebMoves\PluginBase\EventHandlers;

use WebMoves\PluginBase\Contracts\HandlerInterface;
use WebMoves\PluginBase\Contracts\HandlerManagerInterface;
use WebMoves\PluginBase\HookManager;

class HandlerManager implements HandlerManagerInterface
{
    private HookManager $hook_manager;
    private array $handlers = [];
    private bool $initialized = false;

    public function __construct(HookManager $hook_manager)
    {
        $this->hook_manager = $hook_manager;
    }

    /**
     * Register a handler
     *
     * @param HandlerInterface $handler
     * @return void
     */
    public function register(HandlerInterface $handler): void
    {
        $this->handlers[] = $handler;

        // If we're already initialized, register the handler immediately
        if ($this->initialized) {
            $this->register_single_handler($handler);
        }
    }

    /**
     * Initialize all registered handlers
     *
     * @return void
     */
    public function initialize_handlers(): void
    {
        if ($this->initialized) {
            return;
        }

        // Sort handlers by priority
        usort($this->handlers, function (HandlerInterface $a, HandlerInterface $b) {
            return $a->get_priority() <=> $b->get_priority();
        });

        // Register each handler
        foreach ($this->handlers as $handler) {
            $this->register_single_handler($handler);
        }

        $this->initialized = true;
    }

    /**
     * Register a single handler
     *
     * @param HandlerInterface $handler
     * @return void
     */
    private function register_single_handler(HandlerInterface $handler): void
    {
        if (!$handler->should_load()) {
            return;
        }

        try {
            $handler->register_hooks();
        } catch (\Exception $e) {
            error_log(sprintf(
                'Error registering handler %s: %s',
                get_class($handler),
                $e->getMessage()
            ));
        }
    }

    /**
     * Get all registered handlers
     *
     * @return HandlerInterface[]
     */
    public function get_handlers(): array
    {
        return $this->handlers;
    }

    /**
     * Get handlers by class name
     *
     * @param string $class_name
     * @return HandlerInterface[]
     */
    public function get_handlers_by_class(string $class_name): array
    {
        return array_filter($this->handlers, function (HandlerInterface $handler) use ($class_name) {
            return is_a($handler, $class_name);
        });
    }

    /**
     * Check if handler is registered
     *
     * @param string $class_name
     * @return bool
     */
    public function has_handler(string $class_name): bool
    {
        foreach ($this->handlers as $handler) {
            if (is_a($handler, $class_name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a handler by class name
     *
     * @param string $class_name
     * @return bool
     */
    public function remove_handler(string $class_name): bool
    {
        $removed = false;
        $this->handlers = array_filter($this->handlers, function (HandlerInterface $handler) use ($class_name, &$removed) {
            if (is_a($handler, $class_name)) {
                $removed = true;
                return false;
            }
            return true;
        });

        return $removed;
    }
}