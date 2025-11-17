<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlatformCsvTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'entity',
        'columns',
        'example_path',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
        ];
    }
}
