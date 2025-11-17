<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CsvImportJob extends BaseTenantModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'report',
        'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'report' => 'array',
        ];
    }

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
