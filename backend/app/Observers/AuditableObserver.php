<?php

namespace App\Observers;

use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AuditableObserver
 *
 * Observes Eloquent model lifecycle events to capture state changes
 * and dispatch them to the AuditTrailService.
 *
 * Registered in AppServiceProvider to automatically listen to models
 * that implement the TracksAuditTrail trait.
 *
 * @package App\Observers
 */
class AuditableObserver
{
    /**
     * Create a new observer instance.
     *
     * @param AuditTrailService $auditTrail The core logging service
     */
    public function __construct(
        private readonly AuditTrailService $auditTrail,
    ) {
    }

    /**
     * Handle the Model "created" event.
     * Captures the initial state of the newly created model.
     *
     * @param Model $model
     * @return void
     */
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

    /**
     * Handle the Model "updating" event.
     * Fired BEFORE the changes are saved to the database.
     * This is the only phase where we can safely capture the original values of modified fields.
     *
     * @param Model $model
     * @return void
     */
    public function updating(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        // Store a snapshot of the dirty attributes' original values before they are overwritten
        $model->storeAuditSnapshot($this->auditTrail->dirtyOriginalValues($model));
    }

    /**
     * Handle the Model "updated" event.
     * Fired AFTER the changes are committed to the database.
     * Compares the original attributes captured in 'updating' with their new saved values.
     *
     * @param Model $model
     * @return void
     */
    public function updated(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        // Pull the original snapshot we saved in the "updating" hook
        $oldValues = $model->pullAuditSnapshot();

        if ($oldValues === []) {
            return;
        }

        // Get the current (new) values for the same set of changed fields
        $newValues = $this->auditTrail->currentValuesForKeys($model, array_keys($oldValues));

        $this->auditTrail->logModelEvent($model, 'Update', $oldValues, $newValues);
    }

    /**
     * Handle the Model "deleting" event.
     * Fired BEFORE the record is deleted.
     * Captures a full snapshot of the tracked attributes before the record is gone.
     *
     * @param Model $model
     * @return void
     */
    public function deleting(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        // Take a snapshot of the current state right before deletion
        $model->storeAuditSnapshot($this->auditTrail->snapshotValues($model));
    }

    /**
     * Handle the Model "deleted" event.
     * Fired AFTER the database record is removed.
     * Logs the delete event along with the final snapshot of the deleted values.
     *
     * @param Model $model
     * @return void
     */
    public function deleted(Model $model): void
    {
        if (!$this->supportsAuditTrail($model)) {
            return;
        }

        // Pull the deletion snapshot we saved in the "deleting" hook
        $oldValues = $model->pullAuditSnapshot();

        if ($oldValues === []) {
            return;
        }

        $this->auditTrail->logModelEvent($model, 'Delete', $oldValues, []);
    }

    /**
     * Check if the given Eloquent model implements the required audit trail capabilities.
     *
     * @param Model $model
     * @return bool
     */
    private function supportsAuditTrail(Model $model): bool
    {
        return method_exists($model, 'getAuditTrackedAttributes')
            && method_exists($model, 'storeAuditSnapshot')
            && method_exists($model, 'pullAuditSnapshot');
    }
}

