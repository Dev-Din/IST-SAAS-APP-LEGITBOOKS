<?php

namespace App\Services;

use App\Models\PlatformSetting;

class PlatformSettings
{
    protected array $cache = [];

    public function get(string $key, $default = null)
    {
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = PlatformSetting::getValue($key, $default);
        }

        return $this->cache[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        PlatformSetting::setValue($key, $value);
        $this->cache[$key] = $value;
    }
}
