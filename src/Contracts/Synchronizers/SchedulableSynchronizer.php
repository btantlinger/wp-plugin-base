<?php

namespace WebMoves\PluginBase\Contracts\Synchronizers;

interface SchedulableSynchronizer extends Synchronizer
{
    // Schedule option keys as constants
    const SCHEDULE_ENABLED = 'schedule_enabled';

    const SCHEDULE_INTERVAL = 'schedule_interval';


    /**
     * Retrieves an associative array of available schedule interval options.
     *
     * These should be in the form expected by wordpress cron schedules, e.g
     *
     * [
     * 'every_5_minutes' => ['interval' => (5 * MINUTE_IN_SECONDS), 'display' => "Every 5 Minutes"],
     * 'every_10_minutes' => ['interval' => (10 * MINUTE_IN_SECONDS), 'display' => "Every 10 Minutes"]
     * ]
     *
     * @return array An array of available schedule intervals.
     */
    public function get_available_schedule_intervals(): array;

    // Convenience methods
    public function is_schedule_enabled(): bool;

    public function get_schedule_interval(): string;


}