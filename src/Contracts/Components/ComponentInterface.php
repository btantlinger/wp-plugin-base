<?php

namespace WebMoves\PluginBase\Contracts\Components;

interface ComponentInterface
{
    /**
     * Register WordPress hooks for this component
     *
     * @return void
     */
    public function register(): void;

    /**
     * Get the priority for this component
     *
     * @return int
     */
    public function get_priority(): int;

    /**
     * Check if this component should be loaded
     *
     * @return bool
     */
    public function can_register(): bool;
}