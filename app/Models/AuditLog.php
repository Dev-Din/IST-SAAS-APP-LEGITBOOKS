<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'model_type',
        'model_id',
        'performed_by',
        'action',
        'before',
        'after',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
