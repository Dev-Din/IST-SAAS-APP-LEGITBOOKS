<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactModelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->createTestTenant();
    }

    public function test_contact_has_tenant_relationship(): void
    {
        $contact = $this->createTestContact();
        
        $this->assertInstanceOf(Tenant::class, $contact->tenant);
        $this->assertEquals($this->tenant->id, $contact->tenant->id);
    }

    public function test_contact_tax_rate_is_casted_to_decimal(): void
    {
        $contact = Contact::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'type' => 'customer',
            'tax_rate' => '16.00',
        ]);
        
        $this->assertIsFloat($contact->tax_rate);
        $this->assertEquals(16.00, $contact->tax_rate);
    }

    public function test_contact_can_be_customer_type(): void
    {
        $contact = $this->createTestContact(['type' => 'customer']);
        
        $this->assertEquals('customer', $contact->type);
    }

    public function test_contact_can_be_supplier_type(): void
    {
        $contact = $this->createTestContact(['type' => 'supplier']);
        
        $this->assertEquals('supplier', $contact->type);
    }

    protected function createTestTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'tenant_hash' => Tenant::generateTenantHash(),
            'status' => 'active',
            'settings' => [],
        ]);
    }

    protected function createTestContact(array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Contact',
            'email' => 'contact@example.com',
            'type' => 'customer',
            'tax_rate' => 0.00,
        ], $attributes));
    }
}
