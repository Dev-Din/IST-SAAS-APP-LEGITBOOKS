<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class InvoiceCounter extends Model
{
    use HasFactory;

    protected $table = 'invoice_counters';

    protected $fillable = [
        'tenant_id',
        'sequence',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InvoiceCounter $model) {
            // If table still has 'year' column (before remove-year migrations), set current year
            if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'year')) {
                $model->setAttribute('year', (int) date('Y'));
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
