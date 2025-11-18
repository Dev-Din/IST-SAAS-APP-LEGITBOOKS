# LegitBooks - Multi-Tenant Accounting SaaS

LegitBooks is a comprehensive Laravel-based multi-tenant accounting SaaS application with distinct Admin and Tenant areas, plus a public marketing website. The platform supports tenant scoping via session-based tenant resolution, double-entry bookkeeping, M-Pesa integration, invoice generation, and configurable branding modes.

## Features

- **Public Marketing Website**: Professional marketing pages showcasing product, pricing, and features
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
- `contacts` - Customers/suppliers
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

## Application Structure

LegitBooks consists of three main areas:

### 1. Marketing Website (Public)
The public-facing marketing website is accessible to all visitors without authentication:

- **Homepage**: `http://localhost:8000/` - Landing page with product overview
- **Features**: `http://localhost:8000/features` - Detailed feature list
- **Pricing**: `http://localhost:8000/pricing` - Subscription plans and pricing
- **Solutions**: `http://localhost:8000/solutions` - Solutions by business type
- **About**: `http://localhost:8000/about` - Company information
- **Contact**: `http://localhost:8000/contact` - Contact form
- **FAQ**: `http://localhost:8000/faq` - Frequently asked questions
- **Legal**: 
  - Terms: `http://localhost:8000/legal/terms`
  - Privacy: `http://localhost:8000/legal/privacy`

The marketing site includes:
- Responsive design with Tailwind CSS
- Clear CTAs linking to tenant signup/login
- Contact form with email integration
- SEO-friendly structure

### 2. Tenant Portal (Authenticated)
Tenants access their accounting application after login:

- **Login**: `http://localhost:8000/app/auth/login`
- **Dashboard**: `http://localhost:8000/app` (after login)
- **Invoices**: `http://localhost:8000/app/invoices`
- **Contacts**: `http://localhost:8000/app/contacts`
- **Products**: `http://localhost:8000/app/products`
- **Payments**: `http://localhost:8000/app/payments`
- **Chart of Accounts**: `http://localhost:8000/app/chart-of-accounts`

### 3. Admin Portal (Authenticated)
Platform administrators manage tenants and system settings:

- **Login**: `http://localhost:8000/admin/login`
- **Dashboard**: `http://localhost:8000/admin` (after login)
- **Tenants**: `http://localhost:8000/admin/tenants`
- **Settings**: `http://localhost:8000/admin/settings`
- **Admins**: `http://localhost:8000/admin/admins`

## Usage

### User Journey: Marketing Site → Tenant App

1. **Visitor lands on marketing site**: `http://localhost:8000/`
2. **Explores features/pricing**: Navigates through marketing pages
3. **Clicks "Start free trial"**: Redirected to `http://localhost:8000/app/auth/login`
4. **Signs in or requests access**: Uses tenant credentials (tenants are created by admins)
5. **Accesses tenant dashboard**: `http://localhost:8000/app` after authentication

**Note**: Tenant accounts are created by platform administrators. Visitors can sign in if they have credentials, or contact support to request access.

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
/app/auth/login
```

After login, tenants are automatically routed to their dashboard at `/app`. The tenant is resolved from the authenticated user's session, eliminating the need for tenant_hash in URLs.

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
- `MarketingRoutesTest` - Marketing page accessibility
- `ContactFormTest` - Contact form validation and submission

## Project Structure

```
app/
├── Console/Commands/          # Artisan commands
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # Admin controllers
│   │   ├── Tenant/             # Tenant controllers
│   │   └── Marketing/          # Marketing website controllers
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
└── web.php                     # Main routes (includes marketing)

resources/
├── views/
│   ├── admin/                  # Admin Blade views
│   ├── tenant/                 # Tenant Blade views
│   ├── marketing/              # Marketing website views
│   │   ├── components/          # Reusable components (navbar, footer, CTA)
│   │   └── legal/               # Legal pages (terms, privacy)
│   └── layouts/               # Layout templates
│       ├── admin.blade.php
│       ├── tenant.blade.php
│       └── marketing.blade.php # Marketing site layout
└── csv_templates/              # CSV import templates

tests/
└── Feature/
    └── Marketing/              # Marketing route tests

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
