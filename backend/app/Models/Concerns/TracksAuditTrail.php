<?php

namespace App\Models\Concerns;

trait TracksAuditTrail
{
    protected array $auditSnapshot = [];

    public function getAuditTrackedAttributes(): array
    {
        return [];
    }

    public function getAuditType(): string
    {
        return class_basename($this);
    }

    public function getAuditDisplayName(): string
    {
        return $this->getAuditType() . ' #' . $this->getKey();
    }

    public function getAuditFieldLabels(): array
    {
        return [];
    }

    public function resolveAuditEventType(string $defaultEventType, array $oldValues, array $newValues): string
    {
        return $defaultEventType;
    }

    public function storeAuditSnapshot(array $snapshot): void
    {
        $this->auditSnapshot = $snapshot;
    }

    public function pullAuditSnapshot(): array
    {
        $snapshot = $this->auditSnapshot;
        $this->auditSnapshot = [];

        return $snapshot;
    }
}
