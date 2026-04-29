<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'price_at_sale',
        'subtotal',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'price_at_sale' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }
}
