<?php

namespace App\Observers;

use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
    ) {
    }

    public function created(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        $newValues = $this->auditTrail->snapshotValues($model);

        if ($newValues === []) {
            return;
        }

        $this->auditTrail->logModelEvent($model, 'Create', [], $newValues);
    }

    public function updating(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        $model->storeAuditSnapshot($this->auditTrail->dirtyOriginalValues($model));
    }

    public function updated(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        $oldValues = $model->pullAuditSnapshot();

        if ($oldValues === []) {
            return;
        }

        $newValues = $this->auditTrail->currentValuesForKeys($model, array_keys($oldValues));

        $this->auditTrail->logModelEvent($model, 'Update', $oldValues, $newValues);
    }

    public function deleting(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        $model->storeAuditSnapshot($this->auditTrail->snapshotValues($model));
    }

    public function deleted(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        $oldValues = $model->pullAuditSnapshot();

        if ($oldValues === []) {
            return;
        }

        $this->auditTrail->logModelEvent($model, 'Delete', $oldValues, []);
    }

    private function supportsAuditTrail(Model $model): bool
    {
        return method_exists($model, 'getAuditTrackedAttributes')
            && method_exists($model, 'storeAuditSnapshot')
            && method_exists($model, 'pullAuditSnapshot');
    }
}
