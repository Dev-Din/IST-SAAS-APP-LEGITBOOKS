<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecurringTemplate extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'contact_id',
        'frequency',
        'cron_expression',
        'start_date',
        'end_date',
        'next_run_at',
        'is_active',
        'invoice_template',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_run_at' => 'datetime',
            'is_active' => 'boolean',
            'invoice_template' => 'array',
        ];
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
