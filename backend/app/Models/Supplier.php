<?php

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use TracksAuditTrail;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
    ];

    public $timestamps = false;

    public function getAuditTrackedAttributes(): array
    {
        return [
            'name',
            'contact_person',
            'phone',
            'email',
            'address',
        ];
    }

    public function getAuditType(): string
    {
        return 'Supplier';
    }

    public function getAuditDisplayName(): string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? "Supplier '{$name}'" : 'Supplier #' . $this->getKey();
    }

    public function getAuditFieldLabels(): array
    {
        return [
            'contact_person' => 'contact person',
        ];
    }
}
