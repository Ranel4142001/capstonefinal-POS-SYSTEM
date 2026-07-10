<?php

namespace App\Models\Concerns;

/**
 * Trait TracksAuditTrail
 *
 * Provides a standardized contract and state tracking for Eloquent models
 * that require audit logging. This trait facilitates capturing change snapshots
 * during model updates and deletions, as well as customizing log representations.
 *
 * @package App\Models\Concerns
 */
trait TracksAuditTrail
{
    /**
     * Stores a temporary snapshot of model attribute values during update/delete lifecycle hooks.
     *
     * @var array
     */
    protected array $auditSnapshot = [];

    /**
     * Get the list of database attributes that should be tracked for changes.
     * Override this in the model class to specify which fields to audit.
     *
     * @return array List of attribute keys (e.g., ['name', 'price', 'stock_quantity'])
     */
    public function getAuditTrackedAttributes(): array
    {
        return [];
    }

    /**
     * Get the display name/type for this entity in the audit logs.
     * Defaults to the short class name of the model.
     *
     * @return string
     */
    public function getAuditType(): string
    {
        return class_basename($this);
    }

    /**
     * Get a human-readable display string representing this specific record.
     * (e.g., "Product #42" or "Supplier #12").
     *
     * @return string
     */
    public function getAuditDisplayName(): string
    {
        return $this->getAuditType() . ' #' . $this->getKey();
    }

    /**
     * Get human-friendly label mappings for the tracked fields.
     * (e.g., ['cost_price' => 'Cost Price', 'stock_quantity' => 'Stock Quantity']).
     *
     * @return array Map of field name to display label
     */
    public function getAuditFieldLabels(): array
    {
        return [];
    }

    /**
     * Allows custom logic to resolve the event type name dynamically.
     * (e.g., changing 'Update' to 'Refund' under certain state transitions).
     *
     * @param string $defaultEventType The default resolved event (Create, Update, Delete)
     * @param array $oldValues Attributes before change
     * @param array $newValues Attributes after change
     * @return string Resolves to the final event type logged
     */
    public function resolveAuditEventType(string $defaultEventType, array $oldValues, array $newValues): string
    {
        return $defaultEventType;
    }

    /**
     * Store a snapshot of attributes temporarily during model events (e.g., updating, deleting).
     *
     * @param array $snapshot
     * @return void
     */
    public function storeAuditSnapshot(array $snapshot): void
    {
        $this->auditSnapshot = $snapshot;
    }

    /**
     * Retrieve the temporarily stored snapshot and clear the internal state.
     *
     * @return array
     */
    public function pullAuditSnapshot(): array
    {
        $snapshot = $this->auditSnapshot;
        $this->auditSnapshot = [];

        return $snapshot;
    }
}

