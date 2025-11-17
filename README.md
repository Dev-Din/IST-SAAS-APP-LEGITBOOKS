# LegitBooks - Multi-Tenant Accounting SaaS

LegitBooks is a comprehensive Laravel-based multi-tenant accounting SaaS application with distinct Admin and Tenant areas. The platform supports tenant scoping via `tenant_hash` in URLs, double-entry bookkeeping, M-Pesa integration, invoice generation, and configurable branding modes.

## Features

- **Multi-Tenant Architecture**: Single database with tenant scoping via `tenant_id`
- **Admin Platform**: Manage tenants, subscriptions, platform settings
- **Tenant Accounting**: Invoices, payments, chart of accounts, journal entries
- **M-Pesa Integration**: Sandbox support for payment processing
- **Invoice Management**: PDF generation, email sending via Mailgun
- **CSV Import**: Import contacts, products, chart of accounts
- **Branding Modes**: Configurable branding (A/B/C) with tenant-level overrides
- **Double-Entry Bookkeeping**: Automatic journal entry posting
- **Recurring Invoices**: Automated recurring invoice generation

## Architecture

### Multi-Tenancy

- **Single Database**: All tenants share one database
- **Tenant Scoping**: Global scope via `HasTenantScope` trait
- **Tenant Hash**: Opaque UUID-base64 hash in URLs (`/app/{tenant_hash}/...`)
- **TenantContext**: Service container singleton for current tenant

### Branding Modes

- **Mode A**: Tenant name only, no "LegitBooks" branding
- **Mode B**: "LegitBooks" prominently displayed
- **Mode C**: Fully white-labeled (tenant name/logo only)
- **Tenant Override**: Per-tenant `branding_override` setting

### Database Structure

**Platform Tables:**
- `admins` - Platform administrators
- `tenants` - Tenant records
- `subscriptions` - Tenant subscriptions
- `platform_audit_logs` - Platform-level audit trail
- `platform_csv_templates` - CSV import templates

**Tenant-Scoped Tables:**
- `users` - Tenant users
- `contacts` - Customers/vendors
- `products` - Products/services
- `chart_of_accounts` - Chart of accounts
- `accounts` - Bank/cash accounts
- `invoices` & `invoice_line_items` - Invoices
- `payments` & `payment_allocations` - Payments
- `journal_entries` & `journal_lines` - Double-entry journals
- `fixed_assets` - Fixed asset management
- `audit_logs` - Tenant audit trail
- `attachments` - File attachments
- `invoice_counters` - Per-tenant invoice numbering
- `recurring_templates` - Recurring invoice templates
- `csv_import_jobs` - CSV import tracking

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js & npm

### Setup Steps

1. **Clone and Install Dependencies**
```bash
cd LegitBooks
composer install
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with:
```env
BRANDING_MODE=A
LEGITBOOKS_NAME=LegitBooks

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=legitbooks
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your-domain.mailgun.org
MAILGUN_SECRET=your-mailgun-secret
MAILGUN_ENDPOINT=api.mailgun.net

MPESA_CONSUMER_KEY=your-consumer-key
MPESA_CONSUMER_SECRET=your-consumer-secret
MPESA_SHORTCODE=your-shortcode
MPESA_PASSKEY=your-passkey
MPESA_ENVIRONMENT=sandbox
```

3. **Run Migrations**
```bash
php artisan migrate
```

4. **Seed Database**
```bash
php artisan db:seed
```

5. **Build Assets**
```bash
npm run build
```

6. **Start Development Server**
```bash
php artisan serve
npm run dev
```

## Usage

### Creating a Tenant

**Via Artisan:**
```bash
php artisan legitbooks:tenant:create "Tenant Name" tenant@example.com
```

**Via Admin UI:**
Navigate to `/admin/tenants/create`

### Tenant Access

Tenants access their application via:
```
/app/{tenant_hash}/dashboard
```

The `tenant_hash` is generated automatically when a tenant is created.

### M-Pesa Simulation

For development/testing:
```bash
php artisan mpesa:simulate {tenant_hash} 254712345678 1000
```

### Tenant Backup

Backup tenant data:
```bash
php artisan tenant:backup {tenant_hash}
```

## Branding Configuration

### Environment-Level
Set `BRANDING_MODE` in `.env`:
- `A` - Tenant name only
- `B` - LegitBooks branding
- `C` - White-labeled

### Tenant-Level Override
Update tenant settings via admin UI or database:
```php
$tenant->settings = [
    'branding_override' => 'B', // or 'C'
    'brand' => [
        'name' => 'Custom Name',
        'logo_path' => '/path/to/logo.png',
        'primary_color' => '#392a26',
        'text_color' => '#ffffff',
    ]
];
```

## Testing

Run PHPUnit tests:
```bash
php artisan test
```

Key test suites:
- `JournalEntryTest` - Double-entry balancing
- `InvoiceNumberServiceTest` - Per-tenant invoice numbering
- `InvoicePaymentFlowTest` - Invoice → Payment → Journal flow
- `AdminProvisioningTest` - Tenant provisioning

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # Admin controllers
│   │   └── Tenant/             # Tenant controllers
│   └── Middleware/             # ResolveTenant, EnsureTenantActive
├── Models/                     # Eloquent models
│   └── Traits/                 # HasTenantScope trait
└── Services/                   # Business logic services
    ├── TenantContext.php
    ├── TenantProvisioningService.php
    ├── InvoicePostingService.php
    ├── PaymentService.php
    ├── MpesaService.php
    └── InvoiceNumberService.php

routes/
├── admin.php                   # Admin routes
├── tenant.php                  # Tenant routes
└── web.php                     # Main routes

resources/
├── views/
│   ├── admin/                  # Admin Blade views
│   ├── tenant/                 # Tenant Blade views
│   └── layouts/               # Layout templates
└── csv_templates/              # CSV import templates

database/
├── migrations/                 # Database migrations
└── seeders/                    # Database seeders
```

## Key Services

### TenantProvisioningService
Handles tenant creation and initial setup:
- Generates `tenant_hash`
- Seeds default chart of accounts
- Creates invoice counter
- Optionally creates tenant admin user
- Seeds demo data

### InvoicePostingService
Automatically creates journal entries when invoices are marked as "sent":
- Debits Accounts Receivable
- Credits Sales Revenue (per line item)
- Credits Tax Liability (if applicable)

### PaymentService
Processes payments and creates journal entries:
- Debits Bank/Cash account
- Credits Accounts Receivable
- Handles partial payments and overpayments

### MpesaService
Handles M-Pesa payment callbacks:
- Validates callback payload
- Creates payment records
- Processes payment allocations

## Date & Time Format

- **Date**: DD/MM/YYYY
- **Time**: HH:MM:SS

## License

MIT

## Support

For issues and questions, please refer to the project documentation or create an issue in the repository.
