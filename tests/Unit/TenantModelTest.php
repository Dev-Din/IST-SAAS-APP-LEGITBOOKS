<?php

namespace Tests\Unit;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_tenant_hash_returns_base64_encoded_uuid(): void
    {
        $hash = Tenant::generateTenantHash();
        
        $this->assertNotEmpty($hash);
        $this->assertIsString($hash);
        // Base64 encoded UUID should be 24 characters
        $this->assertGreaterThanOrEqual(20, strlen($hash));
    }

    public function test_generate_tenant_hash_returns_unique_values(): void
    {
        $hash1 = Tenant::generateTenantHash();
        $hash2 = Tenant::generateTenantHash();
        
        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_is_active_returns_true_for_active_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
        
        $this->assertTrue($tenant->isActive());
    }

    public function test_is_active_returns_false_for_suspended_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'suspended',
            'settings' => [],
        ]);
        
        $this->assertFalse($tenant->isActive());
    }

    public function test_get_branding_mode_returns_override_when_set(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [
                'branding_override' => 'C',
            ],
        ]);
        
        $this->assertEquals('C', $tenant->getBrandingMode());
    }

    public function test_get_branding_mode_returns_config_default_when_no_override(): void
    {
        config(['legitbooks.branding_mode' => 'B']);
        
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
        
        $this->assertEquals('B', $tenant->getBrandingMode());
    }

    public function test_get_brand_settings_returns_custom_settings_when_set(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [
                'brand' => [
                    'name' => 'Custom Brand',
                    'logo_path' => '/path/to/logo.png',
                    'primary_color' => '#ff0000',
                    'text_color' => '#ffffff',
                ],
            ],
        ]);
        
        $brandSettings = $tenant->getBrandSettings();
        
        $this->assertEquals('Custom Brand', $brandSettings['name']);
        $this->assertEquals('/path/to/logo.png', $brandSettings['logo_path']);
        $this->assertEquals('#ff0000', $brandSettings['primary_color']);
    }

    public function test_get_brand_settings_returns_defaults_when_not_set(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
        
        $brandSettings = $tenant->getBrandSettings();
        
        $this->assertEquals('Test Tenant', $brandSettings['name']);
        $this->assertNull($brandSettings['logo_path']);
        $this->assertEquals('#392a26', $brandSettings['primary_color']);
    }
}
