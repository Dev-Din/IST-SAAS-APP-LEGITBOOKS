<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = $this->createTestTenant();
    }

    public function test_product_has_tenant_relationship(): void
    {
        $product = $this->createTestProduct();

        $this->assertInstanceOf(Tenant::class, $product->tenant);
        $this->assertEquals($this->tenant->id, $product->tenant->id);
    }

    public function test_product_has_sales_account_relationship(): void
    {
        $coa = $this->createTestCOA();
        $product = $this->createTestProduct(['sales_account_id' => $coa->id]);

        $this->assertInstanceOf(ChartOfAccount::class, $product->salesAccount);
        $this->assertEquals($coa->id, $product->salesAccount->id);
    }

    public function test_product_price_is_casted_to_decimal(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'price' => '1000.50',
            'is_active' => true,
        ]);

        // Laravel decimal cast may return string in PHP
        $this->assertIsNumeric($product->price);
        $this->assertEquals(1000.50, (float) $product->price);
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

    protected function createTestCOA(): ChartOfAccount
    {
        return ChartOfAccount::create([
            'tenant_id' => $this->tenant->id,
            'code' => '4100',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'category' => 'revenue',
            'is_active' => true,
        ]);
    }

    protected function createTestProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'price' => 1000.00,
            'is_active' => true,
        ], $attributes));
    }
}
