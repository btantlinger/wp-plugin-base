<?php

namespace WebMoves\PluginBase\Contracts\Synchronizers;

use WebMoves\PluginBase\Contracts\Settings\SettingsProvider;
use WebMoves\PluginBase\Contracts\Synchronizers\SyncAlreadyRunningException;


interface Synchronizer extends SettingsProvider
{
    public function get_sync_type_label(): string;

    public function get_sync_type_key(): string;


    /**
     * Synchronizes the current state based on the triggered context.
     *
     * Implementations should throw SyncAlreadyRunningException when attempting to start
     * a sync while another sync of the same type is already running.
     *
     * @param string $triggeredBy Indicates the source or reason for triggering the synchronization.
     * @return void
     * @throws SyncAlreadyRunningException When a sync of the same type is already running
     */
    public function sync(string $triggeredBy): void;
}