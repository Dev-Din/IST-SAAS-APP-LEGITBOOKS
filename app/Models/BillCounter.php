<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillCounter extends Model
{
    use HasFactory;

    protected $table = 'bill_counters';

    protected $fillable = [
        'tenant_id',
        'counter',
        'prefix',
        'format',
    ];

    protected function casts(): array
    {
        return [
            'counter' => 'integer',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
