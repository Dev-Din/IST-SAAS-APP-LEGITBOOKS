<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'entry_number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'total_debits',
        'total_credits',
        'is_posted',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'total_debits' => 'decimal:2',
            'total_credits' => 'decimal:2',
            'is_posted' => 'boolean',
        ];
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debits - $this->total_credits) < 0.01;
    }

    public function calculateTotals(): void
    {
        $this->total_debits = $this->lines()->where('type', 'debit')->sum('amount');
        $this->total_credits = $this->lines()->where('type', 'credit')->sum('amount');
    }
}
