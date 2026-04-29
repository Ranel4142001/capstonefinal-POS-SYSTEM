<?php

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use TracksAuditTrail;

    protected $table = 'sales';

    protected $fillable = [
        'sale_date',
        'total_amount',
        'user_id',
        'discount_amount',
        'tax_amount',
        'payment_method',
        'cash_received',
        'change_due',
        'cashier_id',
        'customer_id',
        'status',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'sale_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'change_due' => 'decimal:2',
        ];
    }

    public function getAuditTrackedAttributes(): array
    {
        return [
            'total_amount',
            'discount_amount',
            'tax_amount',
            'payment_method',
            'cash_received',
            'change_due',
            'cashier_id',
            'customer_id',
            'status',
        ];
    }

    public function getAuditType(): string
    {
        return 'Transaction';
    }

    public function getAuditDisplayName(): string
    {
        return 'Transaction #' . $this->getKey();
    }

    public function getAuditFieldLabels(): array
    {
        return [
            'total_amount' => 'total amount',
            'discount_amount' => 'discount amount',
            'tax_amount' => 'tax amount',
            'payment_method' => 'payment method',
            'cash_received' => 'cash received',
            'change_due' => 'change due',
            'cashier_id' => 'cashier',
            'customer_id' => 'customer',
        ];
    }

    public function resolveAuditEventType(string $defaultEventType, array $oldValues, array $newValues): string
    {
        if (($oldValues['status'] ?? null) !== 'refunded' && ($newValues['status'] ?? null) === 'refunded') {
            return 'Refund';
        }

        return $defaultEventType;
    }
}
