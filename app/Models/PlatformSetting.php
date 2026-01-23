<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public static function getValue(string $key, $default = null)
    {
        return optional(static::where('key', $key)->first())->value ?? $default;
    }

    public static function setValue(string $key, $value, ?array $meta = null): void
    {
        static::updateOrCreate(['key' => $key], [
            'value' => $value,
            'meta' => $meta,
        ]);
    }
}
