<?php

namespace WebMoves\PluginBase\Synchronizers;

use WebMoves\PluginBase\Contracts\Synchronizers\Sync;
use WebMoves\PluginBase\Enums\SyncStatus;

class BasicSync implements Sync
{
    private int $id;
    private string $syncType;
    private SyncStatus $status;
    private int $total;
    private int $synced;
    private int $failed;
    private ?\DateTime $startedAt;
    private ?\DateTime $completedAt;
    private ?\DateTime $updatedAt;
    private array $details;

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->syncType = $data['sync_type'] ?? '';
        $this->status = SyncStatus::tryFrom($data['status'] ?? '') ?? SyncStatus::NOT_STARTED;
        $this->total = (int) ($data['total_items'] ?? 0);
        $this->synced = (int) ($data['synced_items'] ?? 0);
        $this->failed = (int) ($data['failed_items'] ?? 0);
        
        $this->startedAt = $this->parseDateTime($data['started_at'] ?? null);
        $this->completedAt = $this->parseDateTime($data['completed_at'] ?? null);
        $this->updatedAt = $this->parseDateTime($data['updated_at'] ?? null);
        
        $this->details = $this->parseDetails($data['sync_details'] ?? null);
    }

    public function get_id(): int
    {
        return $this->id;
    }

    public function get_sync_type(): string
    {
        return $this->syncType;
    }

    public function get_status(): SyncStatus
    {
        return $this->status;
    }

    public function get_duration(): int
    {
        if (!$this->startedAt) {
            return 0;
        }
        
        $endTime = $this->completedAt ?? new \DateTime();
        return $endTime->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function get_total(): int
    {
        return $this->total;
    }

    public function get_synced(): int
    {
        return $this->synced;
    }

    public function get_failed(): int
    {
        return $this->failed;
    }

    public function get_percent_complete(): int
    {
        if ($this->total === 0) {
            return 0;
        }
        
        return (int) round(($this->synced / $this->total) * 100);
    }

    public function get_started_at(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function get_completed_at(): ?\DateTime
    {
        return $this->completedAt;
    }

    public function get_updated_at(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function get_details(): array
    {
        return $this->details;
    }

    /**
     * Parse datetime string into DateTime object
     */
    private function parseDateTime(?string $datetime): ?\DateTime
    {
        if (empty($datetime)) {
            return null;
        }
        
        try {
            return new \DateTime($datetime);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse details JSON string into array
     */
    private function parseDetails(?string $details): array
    {
        if (empty($details)) {
            return [];
        }
        
        try {
            $decoded = json_decode($details, true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Exception $e) {
            return [];
        }
    }
}