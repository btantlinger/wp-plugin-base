<?php

namespace WebMoves\PluginBase\Contacts\BackgroundTasks;

use WebMoves\PluginBase\Contracts\Components\Component;

interface BackgroundTask extends Component
{
    /**
     * Get the hook name for this background task
     */
    public function get_hook_name(): string;

    /**
     * Run the background task - schedules it and spawns cron immediately
     */
    public function run(...$args): bool;

    /**
     * Check if the background task is currently hooked/registered
     */
    public function is_hooked(): bool;

    /**
     * Check if there's work currently scheduled to run
     */
    public function is_scheduled(): bool;

    /**
     * Handle the scheduled work execution (called by cron)
     */
    public function handle_scheduled_work(...$args): void;
}