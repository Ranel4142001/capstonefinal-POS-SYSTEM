<?php

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use TracksAuditTrail;

    protected $table = 'products';

    protected $fillable = [
        'barcode',
        'name',
        'description',
        'price',
        'cost_price',
        'stock_quantity',
        'category_id',
        'supplier_id',
        'brand',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuditTrackedAttributes(): array
    {
        return [
            'barcode',
            'name',
            'price',
            'cost_price',
            'stock_quantity',
            'category_id',
            'supplier_id',
            'brand',
            'is_active',
        ];
    }

    public function getAuditType(): string
    {
        return 'Product';
    }

    public function getAuditDisplayName(): string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? "Product '{$name}'" : 'Product #' . $this->getKey();
    }

    public function getAuditFieldLabels(): array
    {
        return [
            'cost_price' => 'cost price',
            'stock_quantity' => 'stock quantity',
            'category_id' => 'category',
            'supplier_id' => 'supplier',
            'is_active' => 'active status',
        ];
    }
}
