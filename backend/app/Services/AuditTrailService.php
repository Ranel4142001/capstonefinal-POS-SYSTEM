<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Class AuditTrailService
 *
 * Core service layer responsible for generating detailed change snapshots
 * and logging audit records for model state modifications (Create, Update, Delete).
 *
 * Supports hybrid environments by resolving user identity from either the
 * active Laravel context or legacy PHP sessions.
 *
 * @package App\Services
 */
class AuditTrailService
{
    /**
     * Snapshot the current values of all tracked attributes of the model.
     * Only stores attributes that have non-null values.
     *
     * @param Model $model
     * @return array Map of attribute names to normalized values
     */
    public function snapshotValues(Model $model): array
    {
        $values = [];

        foreach ($this->trackedAttributes($model) as $attribute) {
            $value = $model->getAttribute($attribute);

            if ($value === null) {
                continue;
            }

            $values[$attribute] = $this->normalizeValue($value);
        }

        return $values;
    }

    /**
     * Capture the original (pre-modified) values of attributes that have changed.
     * Called during the model "updating" event.
     *
     * @param Model $model
     * @return array Map of dirty attribute names to original normalized values
     */
    public function dirtyOriginalValues(Model $model): array
    {
        $values = [];

        foreach ($this->trackedAttributes($model) as $attribute) {
            if (!$model->isDirty($attribute)) {
                continue;
            }

            $values[$attribute] = $this->normalizeValue($model->getOriginal($attribute));
        }

        return $values;
    }

    /**
     * Retrieve the current normalized database values for a specific set of attribute keys.
     *
     * @param Model $model
     * @param array $keys List of attribute names to retrieve
     * @return array Map of requested attributes to current normalized values
     */
    public function currentValuesForKeys(Model $model, array $keys): array
    {
        $values = [];

        foreach ($keys as $attribute) {
            $values[$attribute] = $this->normalizeValue($model->getAttribute($attribute));
        }

        return $values;
    }

    /**
     * Create an AuditLog entry in the database.
     *
     * @param Model $model The observed model instance
     * @param string $defaultEventType Event type (e.g. Create, Update, Delete)
     * @param array $oldValues Attributes before the change
     * @param array $newValues Attributes after the change
     * @return void
     */
    public function logModelEvent(Model $model, string $defaultEventType, array $oldValues, array $newValues): void
    {
        if (!$this->auditTableExists()) {
            return;
        }

        if ($oldValues === [] && $newValues === []) {
            return;
        }

        $eventType = method_exists($model, 'resolveAuditEventType')
            ? $model->resolveAuditEventType($defaultEventType, $oldValues, $newValues)
            : $defaultEventType;

        $userId = $this->resolveUserId();

        AuditLog::query()->create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'auditable_type' => method_exists($model, 'getAuditType') ? $model->getAuditType() : class_basename($model),
            'auditable_id' => $model->getKey(),
            'ip_address' => $this->resolveIpAddress(),
            'message' => $this->buildMessage($model, $eventType, $oldValues, $newValues, $userId),
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
        ]);
    }

    /**
     * Get the unique attributes set up to be audited on the model.
     *
     * @param Model $model
     * @return array
     */
    private function trackedAttributes(Model $model): array
    {
        if (!method_exists($model, 'getAuditTrackedAttributes')) {
            return [];
        }

        return array_values(array_unique($model->getAuditTrackedAttributes()));
    }

    /**
     * Verify if the audit_logs table is created in the database.
     *
     * @return bool
     */
    private function auditTableExists(): bool
    {
        return Schema::hasTable('audit_logs');
    }

    /**
     * Resolve the active user ID.
     * Supports both Laravel Authenticated contexts and legacy frontend PHP sessions.
     *
     * @return int|null
     */
    private function resolveUserId(): ?int
    {
        if (function_exists('auth')) {
            $authUserId = auth()->id();

            if (is_numeric($authUserId)) {
                return (int) $authUserId;
            }
        }

        $sessionUserId = $_SESSION['user_id'] ?? null;

        return is_numeric($sessionUserId) ? (int) $sessionUserId : null;
    }

    /**
     * Resolve the requester's IP address.
     * Supports Laravel's Request facade and PHP fallback globals.
     *
     * @return string|null
     */
    private function resolveIpAddress(): ?string
    {
        if (function_exists('request') && app()->bound('request')) {
            return request()->ip();
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Build a human-friendly audit description message.
     * (e.g. "User ID 1 Updated Product #42: price from 100 to 120").
     *
     * @param Model $model
     * @param string $eventType
     * @param array $oldValues
     * @param array $newValues
     * @param int|null $userId
     * @return string
     */
    private function buildMessage(Model $model, string $eventType, array $oldValues, array $newValues, ?int $userId): string
    {
        $actor = $userId !== null ? "User ID {$userId}" : 'System';
        $action = match ($eventType) {
            'Create' => 'Created',
            'Update' => 'Updated',
            'Delete' => 'Deleted',
            'Refund' => 'Refunded',
            default => $eventType,
        };
        $subject = method_exists($model, 'getAuditDisplayName')
            ? $model->getAuditDisplayName()
            : class_basename($model) . ' #' . $model->getKey();
        $details = $this->buildDetails($model, $eventType, $oldValues, $newValues);

        return trim($actor . ' ' . $action . ' ' . $subject . ($details !== '' ? ' ' . $details : ''));
    }

    /**
     * Build specific change details for the message description.
     *
     * @param Model $model
     * @param string $eventType
     * @param array $oldValues
     * @param array $newValues
     * @return string
     */
    private function buildDetails(Model $model, string $eventType, array $oldValues, array $newValues): string
    {
        if (in_array($eventType, ['Update', 'Refund'], true)) {
            return $this->buildChangeSummary($model, $oldValues, $newValues);
        }

        if ($eventType === 'Create' && isset($newValues['total_amount'])) {
            return 'with total amount ' . $this->stringify($newValues['total_amount']);
        }

        return '';
    }

    /**
     * Compare old and new values to format a summary of changes.
     *
     * @param Model $model
     * @param array $oldValues
     * @param array $newValues
     * @return string (e.g., "price from 10 to 15, stock quantity from 50 to 45")
     */
    private function buildChangeSummary(Model $model, array $oldValues, array $newValues): string
    {
        $labels = method_exists($model, 'getAuditFieldLabels') ? $model->getAuditFieldLabels() : [];
        $parts = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $label = $labels[$field] ?? str_replace('_', ' ', $field);
            $parts[] = sprintf(
                '%s from %s to %s',
                $label,
                $this->stringify($oldValue),
                $this->stringify($newValue),
            );
        }

        return implode(', ', $parts);
    }

    /**
     * Normalize a value for database storage.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }

    /**
     * Stringify a value for message serialization.
     *
     * @param mixed $value
     * @return string
     */
    private function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
        }

        return (string) $value;
    }
}

