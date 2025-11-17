<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChartOfAccount extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'type',
        'category',
        'parent_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }
}
