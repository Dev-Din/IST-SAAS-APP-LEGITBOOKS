<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'branding_mode' => config('legitbooks.branding_mode', 'A'),
            'mpesa_environment' => config('legitbooks.mpesa.environment', 'sandbox'),
        ];

        foreach ($defaults as $key => $value) {
            PlatformSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
