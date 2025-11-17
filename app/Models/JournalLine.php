<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class JournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_entry_id',
        'chart_of_account_id',
        'type',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
