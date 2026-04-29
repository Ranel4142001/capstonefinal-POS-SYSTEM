<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AuditTrailService
{
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

    public function currentValuesForKeys(Model $model, array $keys): array
    {
        $values = [];

        foreach ($keys as $attribute) {
            $values[$attribute] = $this->normalizeValue($model->getAttribute($attribute));
        }

        return $values;
    }

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

    private function trackedAttributes(Model $model): array
    {
        if (!method_exists($model, 'getAuditTrackedAttributes')) {
            return [];
        }

        return array_values(array_unique($model->getAuditTrackedAttributes()));
    }

    private function auditTableExists(): bool
    {
        return Schema::hasTable('audit_logs');
    }

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

    private function resolveIpAddress(): ?string
    {
        if (function_exists('request') && app()->bound('request')) {
            return request()->ip();
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

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

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }

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
