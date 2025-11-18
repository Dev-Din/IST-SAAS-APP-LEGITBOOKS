<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'last_number',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
