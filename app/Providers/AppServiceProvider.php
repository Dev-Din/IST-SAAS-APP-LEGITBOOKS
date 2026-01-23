<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Observers\AuditObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use App\Services\PlatformSettings;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\TenantContext::class);
        $this->app->singleton(PlatformSettings::class, fn () => new PlatformSettings);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
        Contact::observe(AuditObserver::class);
        Product::observe(AuditObserver::class);

        // Register Blade directives for permission checks
        Blade::if('perm', function (string $permission) {
            $user = auth()->user();

            return $user && $user->hasPermission($permission);
        });

        Blade::if('anyperm', function (array $permissions) {
            $user = auth()->user();

            return $user && $user->hasAnyPermission($permissions);
        });

        // Only try to load platform settings if database is available
        // This allows commands like config:clear and cache:clear to run without DB connection
        try {
            if (Schema::hasTable('platform_settings')) {
                $platformSettings = app(PlatformSettings::class);
                $brandingMode = $platformSettings->get('branding_mode', config('legitbooks.branding_mode'));
                config(['legitbooks.branding_mode' => $brandingMode]);
                config([
                    'legitbooks.mpesa.consumer_key' => $platformSettings->get('mpesa_consumer_key', env('MPESA_CONSUMER_KEY')),
                    'legitbooks.mpesa.consumer_secret' => $platformSettings->get('mpesa_consumer_secret', env('MPESA_CONSUMER_SECRET')),
                    'legitbooks.mpesa.shortcode' => $platformSettings->get('mpesa_shortcode', env('MPESA_SHORTCODE')),
                    'legitbooks.mpesa.passkey' => $platformSettings->get('mpesa_passkey', env('MPESA_PASSKEY')),
                    'legitbooks.mpesa.environment' => $platformSettings->get('mpesa_environment', env('MPESA_ENVIRONMENT', 'sandbox')),
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Database connection not available - skip platform settings loading
            // This is expected for commands like config:clear, cache:clear, etc.
        }
    }
}
