<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'tenant_hash',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function chartOfAccounts()
    {
        return $this->hasMany(ChartOfAccount::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getBrandingMode(): string
    {
        $override = $this->settings['branding_override'] ?? null;
        if ($override) {
            return $override;
        }
        return config('legitbooks.branding_mode', 'A');
    }

    public function getBrandSettings(): array
    {
        return $this->settings['brand'] ?? [
            'name' => $this->name,
            'logo_path' => null,
            'primary_color' => '#392a26',
            'text_color' => '#ffffff',
        ];
    }

    public static function generateTenantHash(): string
    {
        return base64_encode(Str::uuid()->toString());
    }
}
