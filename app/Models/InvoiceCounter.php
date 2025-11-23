<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCounter extends Model
{
    use HasFactory;

    protected $table = 'invoice_counters';

    protected $fillable = [
        'tenant_id',
        'year',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'sequence' => 'integer',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
