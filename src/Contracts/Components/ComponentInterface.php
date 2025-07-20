<?php

namespace WebMoves\PluginBase\Contracts\Components;

use WebMoves\PluginBase\Enums\Lifecycle;

interface ComponentInterface
{
	public function register_on(): Lifecycle;


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