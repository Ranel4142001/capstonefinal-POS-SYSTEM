<?php

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use TracksAuditTrail;

    protected $table = 'customers';

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'first_name',
        'last_name',
        'contact_number',
        'email',
        'address',
    ];

    public $timestamps = false;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function getAuditTrackedAttributes(): array
    {
        return [
            'first_name',
            'last_name',
            'contact_number',
            'email',
            'address',
        ];
    }

    public function getAuditType(): string
    {
        return 'Customer';
    }

    public function getAuditDisplayName(): string
    {
        $fullName = trim(implode(' ', array_filter([
            trim((string) $this->first_name),
            trim((string) $this->last_name),
        ])));

        return $fullName !== '' ? "Customer '{$fullName}'" : 'Customer #' . $this->getKey();
    }

    public function getAuditFieldLabels(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'contact_number' => 'contact number',
        ];
    }
}
