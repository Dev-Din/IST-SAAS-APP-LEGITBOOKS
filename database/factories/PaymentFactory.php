<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = now()->toDateString();

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'invoice_id' => null,
            'subscription_id' => null,
            'payment_number' => 'PAY-'.str_replace('-', '', $date).'-'.fake()->unique()->numerify('########'),
            'payment_date' => $date,
            'account_id' => null,
            'contact_id' => null,
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => 'KES',
            'payment_method' => 'mpesa',
            'reference' => null,
            'notes' => null,
            'mpesa_metadata' => null,
            'phone' => null,
            'mpesa_receipt' => null,
            'transaction_status' => 'completed',
            'raw_callback' => null,
            'checkout_request_id' => null,
            'merchant_request_id' => null,
            'client_token' => (string) \Illuminate\Support\Str::uuid(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_status' => 'pending',
        ]);
    }
}
