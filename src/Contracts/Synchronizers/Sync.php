<?php

namespace WebMoves\PluginBase\Contracts\Synchronizers;

use WebMoves\PluginBase\Enums\SyncStatus;

interface Sync
{
    public function get_id(): int;

    public function get_sync_type(): string;

    public function get_status(): SyncStatus;

    public function get_duration(): int;

    public function get_total(): int;

    public function get_synced(): int;

    public function get_failed(): int;

    public function get_percent_complete(): int;

    public function get_started_at(): ?\DateTime;

    public function get_completed_at(): ?\DateTime;

    public function get_updated_at(): ?\DateTime;

    public function get_details(): array;
}