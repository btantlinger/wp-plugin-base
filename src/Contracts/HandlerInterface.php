<?php

namespace WebMoves\PluginBase\Contracts;

interface HandlerInterface
{
    /**
     * Register WordPress hooks for this handler
     *
     * @return void
     */
    public function register_hooks(): void;

    /**
     * Get the priority for this handler
     *
     * @return int
     */
    public function get_priority(): int;

    /**
     * Check if this handler should be loaded
     *
     * @return bool
     */
    public function should_load(): bool;
}