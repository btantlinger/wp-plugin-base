<?php

namespace WebMoves\PluginBase\Enums;

enum SyncStatus: string
{
    case NOT_STARTED = 'not_started';

    case COMPLETED = 'completed';

    case FAILED = 'failed';

    case CANCELLED = 'cancelled';
    case RUNNING = 'running';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Not Started',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::RUNNING => 'Running'
        };
    }

    /**
     * Determines if a sync operation has reached a terminal state.
     *
     * A sync is considered finished when it has completed its lifecycle and is no longer
     * actively processing. This includes syncs that have:
     * - Successfully completed (COMPLETED)
     * - Been manually cancelled (CANCELLED)
     * - Failed due to errors (FAILED)
     *
     * Syncs with status NOT_STARTED or RUNNING are not considered finished as they
     * are either pending execution or actively processing.
     *
     * @return bool True if the sync has finished (completed, cancelled, or failed), false otherwise
     *
     */
    public function is_finished(): bool
    {
        return $this === self::COMPLETED || $this === self::FAILED || $this === self::CANCELLED;
    }
}
