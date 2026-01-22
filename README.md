# LegitBooks

A comprehensive multi-tenant SaaS platform for accounting, invoicing, and subscription management built with Laravel 12.

## 📋 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [Testing](#testing)
- [License](#license)
- [Credits](#credits)

## 🎯 Overview

LegitBooks is a modern, multi-tenant accounting and invoicing platform designed for businesses of all sizes. It provides a complete solution for managing invoices, payments, subscriptions, and financial records with robust tenant isolation and flexible branding options.

### Key Capabilities

- **Multi-Tenant Architecture**: Complete data isolation per tenant with secure tenant scoping
- **Invoice Management**: Create, send, and track invoices with PDF generation
- **Payment Processing**: Integrated M-Pesa STK Push, card payments, and PayPal support
- **Subscription Billing**: Automated subscription management with multiple plan tiers
- **User Management**: Role-based access control with invitation system
- **Financial Reporting**: Comprehensive reporting and analytics with export capabilities
- **Branding Modes**: Flexible white-label options for tenant customization

## ✨ Key Features

### Platform Administration
- **Tenant Management**: Create, manage, and monitor tenant accounts
- **Admin User Management**: Invite and manage platform administrators with granular permissions
- **Platform Settings**: Configure global settings, branding modes, and payment gateways
- **Analytics & Reports**: Generate detailed reports on tenants, revenue, subscriptions, and payments
- **Export Capabilities**: Export reports in CSV, Excel, and PDF formats

### Tenant Features
- **Invoice Creation**: Generate professional invoices with line items and tax calculations
- **Payment Collection**: Accept payments via M-Pesa, cards, or PayPal
- **Contact Management**: Maintain customer and vendor contact databases
- **Subscription Management**: Manage subscription plans, billing cycles, and upgrades
- **Chart of Accounts**: Double-entry bookkeeping with journal entries
- **User Collaboration**: Invite team members with role-based permissions
- **Dashboard Analytics**: Real-time insights into revenue, invoices, and payments

### Security & Access Control
- **Multi-Guard Authentication**: Separate authentication for platform admins and tenant users
- **Role-Based Permissions**: Fine-grained permission system using Spatie Laravel Permission
- **Invitation System**: Secure token-based invitations for users and admins
- **Tenant Isolation**: Complete data segregation using tenant_id scoping

### Branding & Customization
- **Mode A**: Tenant name with LegitBooks branding
- **Mode B**: Full LegitBooks branding
- **Mode C**: Complete white-label (tenant-specific branding only)
- **Per-Tenant Override**: Individual tenants can override global branding mode

## 🛠 Technology Stack

### Backend
- **PHP**: 8.2+
- **Laravel**: 12.x
- **Database**: MySQL/PostgreSQL/SQLite
- **Queue**: Database queue driver
- **Cache**: File cache (configurable)

### Frontend
- **Blade Templates**: Server-side rendering
- **Tailwind CSS**: Utility-first CSS framework
- **Vite**: Modern build tool for assets
- **Alpine.js**: Lightweight JavaScript framework

### Key Packages
- **Spatie Laravel Permission**: Role and permission management
- **PHPMailer**: SMTP email delivery
- **DomPDF**: PDF generation for invoices and reports
- **Maatwebsite Excel**: Excel export functionality
- **Guzzle HTTP**: HTTP client for API integrations

## 📦 Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL/PostgreSQL (or SQLite for development)
- SMTP server credentials (for email)

### Step 1: Clone the Repository

```bash
git clone <repository-url> LegitBooks
cd LegitBooks
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### Step 3: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Configure Environment Variables

Edit `.env` file with your configuration:

```env
APP_NAME=LegitBooks
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=legitbooks
DB_USERNAME=root
DB_PASSWORD=

# Branding Mode (A, B, or C)
BRANDING_MODE=A

# Email Configuration (PHPMailer)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@legitbooks.com
MAIL_FROM_NAME="${APP_NAME}"

# M-Pesa Configuration (Sandbox)
MPESA_CONSUMER_KEY=your-consumer-key
MPESA_CONSUMER_SECRET=your-consumer-secret
MPESA_SHORTCODE=your-shortcode
MPESA_PASSKEY=your-passkey
MPESA_ENVIRONMENT=sandbox
```

### Step 5: Database Setup

**Option 1: Using SQL File (Recommended for Quick Setup)**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE legitbooks;"

# Import complete setup SQL
mysql -u root -p legitbooks < database/setup.sql
```

**Option 2: Using Laravel Migrations**
```bash
# Run migrations
php artisan migrate

# Seed initial data (creates admin user and roles)
php artisan db:seed --class=SuperAdminSeeder
```

The `database/setup.sql` file contains both schema and seed data, making it ideal for fresh installations or XAMPP users.

**Default Admin Credentials:**
- Email: `admin@legitbooks.com`
- Password: `password`

> ⚠️ **Important**: Change the default password immediately after first login.

### Step 6: Build Frontend Assets

```bash
# Development build
npm run dev

# Production build
npm run build
```

### Step 7: Start Development Server

**Option 1: Direct PHP Server (Recommended - No Chrome Errors)**
```bash
php -S 127.0.0.1:8000 -t public
```

Or use the convenience script:
```bash
./serve.sh
```

**Option 2: Laravel Serve Command**
```bash
php artisan serve
```
> **Note**: If you see Chrome sandbox errors, they're harmless but annoying. Use Option 1 to avoid them.

Visit `http://localhost:8000` in your browser.

## ⚙️ Configuration

### Branding Modes

LegitBooks supports three branding modes configured via `BRANDING_MODE` environment variable:

#### Mode A: Tenant Name + LegitBooks
- Displays tenant name with "via LegitBooks" or "Powered by LegitBooks"
- Default mode for most use cases

#### Mode B: Full LegitBooks Branding
- Complete LegitBooks branding throughout
- Suitable for direct LegitBooks customers

#### Mode C: White Label
- Complete tenant-specific branding
- No LegitBooks references visible
- Requires tenant branding settings (name, logo, colors)

**Per-Tenant Override:**
Tenants can override the global branding mode through the admin panel at `/admin/tenants/{tenant}/branding`.

### M-Pesa Integration

For M-Pesa STK Push integration:

1. **Sandbox Setup:**
   - Register at [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
   - Create an app and obtain consumer key/secret
   - Get your shortcode and passkey

2. **Production Setup:**
   - Complete M-Pesa API onboarding
   - Update `MPESA_ENVIRONMENT=production` in `.env`
   - Use production credentials

3. **Webhook Testing:**
   - Use Cloudflare Tunnel for local development
   - Configure callback URL in M-Pesa settings

### Email Configuration

PHPMailer is configured for SMTP delivery. Example Gmail configuration:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password  # Use App Password, not regular password
MAIL_ENCRYPTION=tls
```

> **Note**: For Gmail, you must enable "Less secure app access" or use an App Password.

## 🚀 Usage

### Creating a Tenant

#### Via Admin Panel
1. Log in as platform admin
2. Navigate to `/admin/tenants`
3. Click "Create New Tenant"
4. Fill in tenant details (name, email)
5. Optionally create initial admin user

#### Via Artisan Command
```bash
php artisan legitbooks:tenant:create "Company Name" company@example.com
```

### Managing Users

#### Inviting Tenant Users
1. Navigate to tenant's user management
2. Click "Invite User"
3. Fill in user details and assign role/permissions
4. User receives invitation email with temporary password

#### Inviting Platform Admins
1. Navigate to `/admin/admins`
2. Click "Invite Admin"
3. Assign role (Owner or Sub-admin) and permissions
4. Admin receives invitation email

### Generating Reports

#### Via Admin Panel
1. Navigate to `/admin/reports`
2. Select date range and comparison period (optional)
3. View reports: Tenant Overview, Revenue Summary, Subscription Metrics, Payment Collection
4. Export in CSV, Excel, or PDF format

#### Via Artisan Command
```bash
# Generate monthly reports
php artisan reports:generate-scheduled --frequency=monthly --report=all --format=pdf --email=admin@example.com

# Available options:
# --frequency: daily, weekly, monthly
# --report: tenant_overview, revenue, subscription, payment, all
# --format: csv, excel, pdf
# --email: (optional) Email address to send reports
```

### Processing Payments

#### M-Pesa STK Push
1. Tenant initiates payment from invoice page
2. Enter phone number
3. Confirm payment on M-Pesa prompt
4. System processes callback and updates invoice status

#### Manual Payment Allocation
1. Navigate to Payments section
2. Create new payment record
3. Allocate payment to specific invoices
4. System updates invoice status automatically

### Invoice Management

#### Creating an Invoice
1. Navigate to Invoices section
2. Click "Create Invoice"
3. Select contact, add line items
4. Set due date and tax (if applicable)
5. Save and send invoice

#### Sending Invoices
- **Email**: Click "Send Invoice" to email PDF to contact
- **Public Link**: Share payment link for online payment
- **PDF Download**: Download invoice PDF manually

## 🏗 Architecture

### Multi-Tenancy

LegitBooks uses a single-database, multi-tenant architecture:

- **Tenant Isolation**: All tenant-scoped models include `tenant_id` column
- **Tenant Resolution**: Tenants accessed via hashed path `/app/{tenant_hash}/...`
- **Global Scoping**: Automatic tenant scoping via `HasTenantScope` trait
- **Admin Access**: Platform admins can access any tenant via admin panel

### Authentication

- **Platform Admins**: Separate `admins` table with `auth:admin` guard
- **Tenant Users**: `users` table with `tenant_id` and `auth:web` guard
- **Role Management**: Spatie Laravel Permission for roles and permissions

### Database Structure

Key tables:
- `tenants` - Tenant accounts
- `users` - Tenant users
- `admins` - Platform administrators
- `subscriptions` - Tenant subscription plans
- `invoices` - Invoice records
- `payments` - Payment transactions
- `contacts` - Customer/vendor contacts
- `chart_of_accounts` - Accounting chart
- `journal_entries` - Double-entry bookkeeping

### File Structure

```
LegitBooks/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/         # Platform admin controllers
│   │   │   ├── Tenant/         # Tenant-scoped controllers
│   │   │   └── Api/            # API endpoints (webhooks)
│   │   ├── Middleware/         # Custom middleware
│   │   └── Requests/           # Form validation
│   ├── Models/                 # Eloquent models
│   ├── Services/                # Business logic services
│   └── Jobs/                    # Queue jobs
├── database/
│   ├── migrations/              # Database migrations
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories
├── resources/
│   ├── views/
│   │   ├── admin/              # Admin portal views
│   │   ├── tenant/              # Tenant portal views
│   │   └── marketing/          # Public marketing pages
│   ├── css/                     # Tailwind CSS files
│   └── js/                      # JavaScript files
├── routes/
│   ├── admin.php                # Admin routes
│   ├── tenant.php               # Tenant routes
│   └── web.php                  # Public routes
└── tests/                       # PHPUnit tests
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=TenantDetailsTest
php artisan test --filter=TenantUserManagementTest
php artisan test --filter=TenantInvoicesAdminTest
php artisan test --filter=ExportInvoicesTest

# Run unit tests only
php artisan test --testsuite=Unit

# Run feature tests only
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Coverage

The test suite includes comprehensive coverage for:
- **Services:** InvoiceNumberService, InvoicePostingService, PaymentService, TenantProvisioningService
- **Models:** Invoice, Payment, JournalEntry, Tenant, User, Contact, Product
- **Controllers:** InvoiceController, PaymentController, BillingController
- **Integration:** Complete workflows (tenant creation, invoice payment, subscription)

### New Tests Created

- `InvoicePostingServiceTest` - Journal entry creation and balancing
- `PaymentServiceTest` - Payment processing and allocation
- `TenantProvisioningServiceTest` - Tenant setup validation
- `InvoiceModelTest` - Model relationships and methods
- `JournalEntryModelTest` - Balancing validation
- `TenantModelTest` - Tenant methods and branding
- `UserModelTest` - Permission checks
- `ContactModelTest` - Contact relationships
- `ProductModelTest` - Product relationships

### Test Structure

- **Feature Tests**: HTTP request testing for controllers
- **Unit Tests**: Model and service testing
- **Integration Tests**: Complete workflow testing
- **Factories**: Model factories for test data generation

### Common Test Scenarios

- Tenant creation and management
- User invitation and acceptance
- Invoice creation and payment processing
- Subscription management
- Report generation and export
- Permission and authorization checks
- Journal entry balancing
- Payment allocation logic

## 📝 Examples

### Creating a Tenant Programmatically

```php
use App\Models\Tenant;
use App\Services\TenantProvisioningService;

$tenant = Tenant::create([
    'name' => 'Acme Corporation',
    'email' => 'admin@acme.com',
    'tenant_hash' => Tenant::generateTenantHash(),
    'status' => 'active',
]);

$provisioningService = app(TenantProvisioningService::class);
$provisioningService->provision($tenant, [
    'create_admin' => true,
    'admin_email' => 'admin@acme.com',
    'admin_password' => 'secure-password',
]);
```

### Generating an Invoice

```php
use App\Models\Invoice;
use App\Models\Contact;

$tenant = auth()->user()->tenant;
$contact = Contact::where('tenant_id', $tenant->id)->first();

$invoice = Invoice::create([
    'tenant_id' => $tenant->id,
    'contact_id' => $contact->id,
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'subtotal' => 1000.00,
    'tax_amount' => 160.00,
    'total' => 1160.00,
    'status' => 'draft',
]);

// Add line items
$invoice->lineItems()->create([
    'description' => 'Consulting Services',
    'quantity' => 10,
    'unit_price' => 100.00,
    'total' => 1000.00,
]);
```

### Processing M-Pesa Payment

```php
use App\Services\MpesaService;

$mpesaService = app(MpesaService::class);

$response = $mpesaService->stkPush([
    'phone_number' => '254712345678',
    'amount' => 1160.00,
    'account_reference' => $invoice->invoice_number,
    'transaction_desc' => 'Invoice Payment',
]);
```

## 📄 License

This project is proprietary software. All rights reserved.

## 🙏 Credits

### Technologies & Libraries

- [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- [Tailwind CSS](https://tailwindcss.com) - A utility-first CSS framework
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) - Role and permission management
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Email library
- [DomPDF](https://github.com/dompdf/dompdf) - PDF generation
- [Maatwebsite Excel](https://github.com/Maatwebsite/Laravel-Excel) - Excel exports

### Payment Integration

- [M-Pesa Daraja API](https://developer.safaricom.co.ke/) - Mobile money payment gateway

## 📞 Support

For support, documentation, or feature requests, please contact the development team.

---

**LegitBooks** - Modern Accounting & Invoicing Platform

Built with ❤️ using Laravel

