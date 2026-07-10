<?php

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use TracksAuditTrail;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
    ];

    public $timestamps = false;

    public function getAuditTrackedAttributes(): array
    {
        return [
            'name',
            'description',
        ];
    }

    public function getAuditType(): string
    {
        return 'Category';
    }

    public function getAuditDisplayName(): string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? "Category '{$name}'" : 'Category #' . $this->getKey();
    }
}
