# LegitBooks - Complete Project Documentation

**Version:** 1.0  
**Last Updated:** 2026-01-21  
**Project:** Multi-Tenant SaaS Accounting & Invoicing Platform

---

## Table of Contents

1. [Theory & Overview](#theory--overview)
2. [Architecture & Design](#architecture--design)
3. [File-by-File Documentation](#file-by-file-documentation)
4. [Technical Requirements](#technical-requirements)
5. [Setup & Installation](#setup--installation)
6. [Running the Application](#running-the-application)
7. [Configuration Guide](#configuration-guide)
8. [Maintenance & Updates](#maintenance--updates)

---

# Theory & Overview

## What is LegitBooks?

LegitBooks is a comprehensive multi-tenant Software-as-a-Service (SaaS) platform designed for accounting, invoicing, and subscription management. It enables businesses to manage their financial operations including invoice creation, payment processing, double-entry bookkeeping, and subscription billing.

### Purpose

LegitBooks serves as a complete financial management solution for businesses, providing:

- **Invoice Management**: Create, send, and track professional invoices with PDF generation
- **Payment Processing**: Accept payments via M-Pesa, credit cards, and PayPal
- **Accounting**: Double-entry bookkeeping with automatic journal entry posting
- **Subscription Billing**: Automated subscription management with multiple plan tiers
- **Multi-Tenancy**: Complete data isolation for multiple organizations on a single platform
- **User Management**: Role-based access control with invitation system
- **Financial Reporting**: Comprehensive reports with export capabilities

### Target Audience

- **Small to Medium Businesses**: Need professional invoicing and payment collection
- **Service Providers**: Consultants, freelancers, agencies requiring recurring billing
- **Platform Operators**: Organizations managing multiple client accounts
- **Accounting Firms**: Managing multiple client books

### Business Model

The platform operates on a **subscription-based model**:

- **Platform Admins**: Manage all tenants, subscriptions, and platform settings
- **Tenants**: Individual businesses/organizations using the platform
- **Tenant Users**: Employees/staff members within each tenant organization
- **Subscription Plans**: Different tiers (Starter, Business, Enterprise) with varying features

## Core Concepts

### Multi-Tenancy

LegitBooks uses a **single-database, multi-tenant architecture** where:

- **Tenant Isolation**: Each tenant's data is completely isolated using `tenant_id` scoping
- **Tenant Access**: Tenants access their portal via hashed paths: `/app/{tenant_hash}/...`
- **Shared Infrastructure**: All tenants share the same database, but data is logically separated
- **Security**: Global scopes ensure tenants can only access their own data
- **Scalability**: Single database simplifies management while maintaining isolation

**Key Components:**
- `HasTenantScope` trait: Automatically filters queries by `tenant_id`
- `ResolveTenant` middleware: Resolves tenant from URL or session
- `TenantContext` service: Manages current tenant state
- `BaseTenantModel`: Base class for all tenant-scoped models

### Accounting Principles

LegitBooks implements **double-entry bookkeeping**:

- **Journal Entries**: Every financial transaction creates balanced journal entries
- **Chart of Accounts**: Standardized account structure (Assets, Liabilities, Equity, Revenue, Expenses)
- **Auto-Posting**: Invoices and payments automatically create journal entries
- **Balance Validation**: System ensures debits always equal credits
- **Account Types**: Asset, Liability, Equity, Revenue, Expense categories

**Invoice Posting Flow:**
- When invoice status changes to `sent`, `InvoicePostingService` creates journal entry
- Debit: Accounts Receivable (AR)
- Credit: Sales Revenue (per line item) + Tax Liability (if applicable)

**Payment Posting Flow:**
- When payment is created, `PaymentService` creates journal entry
- Debit: Bank/Cash Account
- Credit: Accounts Receivable

### Payment Processing

The platform supports multiple payment methods:

- **M-Pesa STK Push**: Mobile money payments via Safaricom's Daraja API
  - Real-time payment processing
  - Callback handling for payment confirmation
  - Receipt validation
- **Card Payments**: Credit/debit card processing (placeholder for future integration)
- **PayPal**: PayPal integration (placeholder for future integration)
- **Manual Payments**: Cash/check payments recorded manually

### Branding Modes

LegitBooks supports three branding configurations:

- **Mode A**: Tenant name with "via LegitBooks" or "Powered by LegitBooks"
  - Default mode for most use cases
  - Shows tenant name prominently with LegitBooks attribution
- **Mode B**: Full LegitBooks branding throughout
  - Complete LegitBooks branding
  - Suitable for direct LegitBooks customers
- **Mode C**: Complete white-label (tenant-specific branding only)
  - No LegitBooks references visible
  - Requires tenant branding settings (name, logo, colors)
  - Per-tenant override available

### Subscription Management

- **Plans**: Starter, Business, Enterprise tiers
- **Billing Cycles**: Monthly, quarterly, annual
- **Trial Periods**: Configurable trial periods
- **Upgrades/Downgrades**: Plan changes with prorated billing
- **Payment Methods**: Stored payment methods for recurring billing

## Business Logic Flows

### Tenant Lifecycle

```
1. Platform Admin creates tenant
   ↓
2. TenantProvisioningService provisions tenant
   - Creates invoice counter
   - Optionally seeds Chart of Accounts
   - Optionally creates tenant admin user
   ↓
3. Tenant registration/onboarding
   - User creates account
   - Selects subscription plan
   - Processes initial payment
   ↓
4. Tenant active and operational
   - Users can access tenant portal
   - Create invoices, process payments
   - Manage contacts, products, accounts
```

### Invoice Workflow

```
1. User creates invoice (status: draft)
   - Selects contact
   - Adds line items
   - Sets due date, tax
   ↓
2. InvoiceObserver assigns invoice number
   - Uses InvoiceNumberService
   - Format: INV-YYYY-XXXX
   - Transaction-safe generation
   ↓
3. User marks invoice as "sent"
   - InvoiceObserver triggers InvoicePostingService
   - Creates journal entry (Debit AR, Credit Revenue)
   - Generates PDF via InvoicePdfService
   ↓
4. Invoice sent to contact
   - SendInvoiceEmailJob queues email
   - PDF attached
   - Public payment link included
   ↓
5. Payment received
   - M-Pesa callback or manual entry
   - PaymentService processes payment
   - Creates journal entry (Debit Bank, Credit AR)
   - Updates invoice status to "paid"
```

### Payment Processing Flow

```
1. Payment initiated
   - M-Pesa STK Push
   - Manual payment entry
   - Card/PayPal (future)
   ↓
2. PaymentService processes payment
   - Validates payment data
   - Creates payment record
   - Allocates to invoices
   ↓
3. Journal entry created
   - Debit: Bank/Cash Account
   - Credit: Accounts Receivable
   - Validates balance (debits = credits)
   ↓
4. Invoice status updated
   - If fully paid: status = "paid"
   - If partially paid: status = "partially_paid"
   - Outstanding amount recalculated
```

### Subscription Management Flow

```
1. Tenant selects plan during registration
   ↓
2. Subscription created
   - Plan, billing cycle, trial period
   - Next billing date calculated
   ↓
3. Payment processed
   - M-Pesa or card payment
   - Subscription status = "active"
   ↓
4. Recurring billing
   - Scheduled job checks due subscriptions
   - Generates invoice for subscription
   - Processes payment
   - Updates next billing date
```

## Architecture Overview

### System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Public Web Layer                      │
│  (Marketing Pages, Public Invoice Payment Links)        │
└─────────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
┌───────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐
│ Admin Portal │  │ Tenant Portal│  │  API Layer  │
│  /admin/*    │  │  /app/*      │  │  /api/*     │
└───────┬──────┘  └──────┬──────┘  └──────┬──────┘
        │                 │                 │
        └─────────────────┼─────────────────┘
                          │
        ┌─────────────────▼─────────────────┐
        │      Application Layer            │
        │  (Controllers, Services, Jobs)    │
        └─────────────────┬─────────────────┘
                          │
        ┌─────────────────▼─────────────────┐
        │      Data Access Layer            │
        │  (Models, Eloquent ORM)           │
        └─────────────────┬─────────────────┘
                          │
        ┌─────────────────▼─────────────────┐
        │      Database (MySQL)             │
        │  (Single DB, tenant_id scoped)     │
        └──────────────────────────────────┘
```

### Data Flow

#### Tenant Resolution Flow

1. User accesses `/app/{tenant_hash}/...` or authenticated user has `tenant_id`
2. `ResolveTenant` middleware extracts tenant from:
   - Session (`tenant_id`)
   - Authenticated user's `tenant_id`
   - Route parameter `tenant_hash` (backward compatibility)
3. Middleware queries `tenants` table to find tenant
4. `TenantContext` service stores current tenant
5. All subsequent queries are automatically scoped to that tenant via `HasTenantScope`

#### Invoice Creation Flow

1. User creates invoice via `InvoiceController::store()`
2. Invoice saved with status `draft`
3. `InvoiceObserver::creating()` assigns invoice number via `InvoiceNumberService`
4. When status changes to `sent`, `InvoiceObserver::updating()` triggers `InvoicePostingService`
5. `InvoicePostingService` creates journal entry:
   - Debit: Accounts Receivable
   - Credit: Sales Revenue + Tax Liability
6. Invoice PDF generated via `InvoicePdfService`
7. Email sent via `SendInvoiceEmailJob` (queued)

#### Payment Processing Flow

1. Payment initiated (M-Pesa callback, manual entry, etc.)
2. `PaymentService::processPayment()` called
3. Payment allocated to invoices via `PaymentAllocation`
4. `PaymentService` creates journal entry:
   - Debit: Bank/Cash Account
   - Credit: Accounts Receivable
5. Invoice status updated to `paid` or `partially_paid`

### Security Architecture

#### Authentication

- **Dual Guard System**: Separate authentication for platform admins (`auth:admin`) and tenant users (`auth:web`)
- **Platform Admins**: Stored in `admins` table with separate guard
- **Tenant Users**: Stored in `users` table with `tenant_id` foreign key

#### Authorization

- **Role-Based Access Control (RBAC)**: Using Spatie Laravel Permission for admin roles
- **Custom Permission System**: Tenant users have JSON `permissions` column
- **Permission Checks**: Middleware validates user permissions before route access
- **Account Owners**: Users with `is_owner = true` have full access regardless of permissions

#### Data Protection

- **Tenant Scoping**: All tenant models use `HasTenantScope` trait
- **Global Scopes**: Automatically filter queries by `tenant_id`
- **Middleware Protection**: `ResolveTenant` and `EnsureTenantActive` enforce tenant context
- **Input Validation**: Form Requests validate all user input
- **SQL Injection Protection**: Eloquent ORM prevents SQL injection
- **XSS Protection**: Blade templates automatically escape output

---

# Architecture & Design

## Multi-Tenancy Implementation

### Tenant Isolation Strategy

LegitBooks uses **single-database, tenant-scoped architecture**:

- All tenant-scoped tables include `tenant_id` column
- Global scope automatically filters queries by current tenant
- Tenant context managed via `TenantContext` service
- Session-based tenant resolution for performance

### Key Components

**`HasTenantScope` Trait** (`app/Models/Traits/HasTenantScope.php`):
- Adds global scope to filter by `tenant_id`
- Auto-fills `tenant_id` on model creation
- Uses `TenantContext` to get current tenant

**`ResolveTenant` Middleware** (`app/Http/Middleware/ResolveTenant.php`):
- Resolves tenant from session, authenticated user, or route parameter
- Sets tenant in `TenantContext`
- Handles tenant not found/suspended scenarios

**`BaseTenantModel`** (`app/Models/BaseTenantModel.php`):
- Abstract base class for all tenant-scoped models
- Uses `HasTenantScope` trait
- Provides `tenant()` relationship

## Authentication & Authorization

### Dual Guard System

**Admin Guard** (`auth:admin`):
- Uses `admins` table
- Separate authentication for platform administrators
- Roles: `owner`, `subadmin` (via Spatie Permission)

**Web Guard** (`auth:web`):
- Uses `users` table
- Tenant-scoped users
- Custom permission system (JSON column)

### Permission System

**Platform Admins:**
- Spatie Laravel Permission package
- Roles: `owner`, `subadmin`
- Granular permissions for tenant management, admin management, etc.

**Tenant Users:**
- JSON `permissions` column in `users` table
- Custom permission checking via `hasPermission()` method
- Account owners (`is_owner = true`) have full access

## Service Layer Architecture

Services handle business logic, keeping controllers thin:

- **InvoiceNumberService**: Transaction-safe invoice number generation
- **InvoicePostingService**: Double-entry journal posting for invoices
- **PaymentService**: Payment processing and allocation
- **MpesaStkService**: M-Pesa STK Push integration
- **TenantProvisioningService**: Tenant setup and initialization
- **InvoiceSendService**: PDF generation and email sending
- **PHPMailerService**: Email delivery via SMTP

Pattern:
- Services handle business logic
- Controllers delegate to services
- Database transactions for critical operations
- Proper dependency injection

---

# File-by-File Documentation

## Root Directory Files

### Configuration Files

#### `composer.json`
PHP dependency management file.

**Key Dependencies:**
- `laravel/framework`: ^12.0 - Core Laravel framework
- `spatie/laravel-permission`: ^6.23 - Role and permission management
- `phpmailer/phpmailer`: ^7.0 - SMTP email sending
- `barryvdh/laravel-dompdf`: ^3.1 - PDF generation
- `maatwebsite/excel`: ^3.1 - Excel export functionality
- `guzzlehttp/guzzle`: ^7.10 - HTTP client for API calls

**Key Scripts:**
- `composer run setup`: Complete setup (install, .env, key, migrate, npm install, build)
- `composer run dev`: Start all services (server, queue, logs, vite) concurrently
- `composer run test`: Run PHPUnit tests

#### `package.json`
Node.js dependency management file.

**Key Dependencies:**
- `vite`: ^7.0.7 - Build tool for assets
- `tailwindcss`: ^4.0.0 - CSS framework
- `laravel-vite-plugin`: ^2.0.0 - Laravel integration
- `axios`: ^1.11.0 - HTTP client

**Scripts:**
- `npm run dev`: Start Vite dev server with hot reload
- `npm run build`: Build production assets

#### `vite.config.js`
Vite configuration for asset compilation.

**Entry Points:**
- `resources/css/app.css`: Main application CSS
- `resources/css/admin.css`: Admin portal CSS
- `resources/css/tenant.css`: Tenant portal CSS
- `resources/js/app.js`: Main JavaScript

**Plugins:**
- `laravel-vite-plugin`: Laravel integration
- `@tailwindcss/vite`: Tailwind CSS compilation

#### `phpunit.xml`
PHPUnit test configuration.

**Test Suites:**
- `Unit`: Unit tests in `tests/Unit/`
- `Feature`: Feature tests in `tests/Feature/`

**Test Environment:**
- Uses SQLite in-memory database
- Queue connection: `sync`
- Mail driver: `array`
- Cache store: `array`

#### `.env.example`
Template for environment variables with placeholder values.

**Key Variables:**
- Database configuration
- SMTP email settings
- M-Pesa API credentials
- Application settings (APP_KEY, APP_DEBUG, etc.)
- Branding mode configuration

### Shell Scripts

#### `serve.sh`
Development server script:
- Starts PHP built-in server on port 8000
- Avoids Chrome sandbox errors
- Usage: `./serve.sh [port] [host]`

#### `QUICK_START.sh`
Automated setup script:
- Checks for `.env` file
- Installs PHP dependencies if needed
- Installs Node.js dependencies if needed
- Generates application key
- Runs migrations
- Seeds database
- Builds assets

#### `cloudflared-tunnel.sh`
Cloudflare Tunnel script for M-Pesa callback testing:
- Creates tunnel to expose localhost to internet
- Required for M-Pesa webhook callbacks in development
- Usage: `./cloudflared-tunnel.sh`

---

## Bootstrap Directory

### `bootstrap/app.php`
Laravel application bootstrap file.

**Responsibilities:**
- Configures routing (web, api, console)
- Registers middleware aliases
- Handles exception rendering
- Sets up health check endpoint

**Middleware Aliases:**
- `tenant.resolve`: `ResolveTenant` - Resolves tenant from request
- `tenant.active`: `EnsureTenantActive` - Ensures tenant is active
- `user.active`: `EnsureUserIsActive` - Ensures user account is active
- `permission`: `EnsureUserHasPermission` - Checks user has specific permission
- `anypermission`: `EnsureUserHasAnyPermission` - Checks user has any of specified permissions

**Exception Handling:**
- Redirects to admin login for `/admin/*` routes
- Redirects to tenant login for `/app/*` routes
- Fallback to generic login route

---

## Config Directory

### `config/app.php`
Core application configuration:
- Application name, environment, debug mode
- Timezone, locale settings
- Service provider registration
- URL configuration

### `config/auth.php`
Authentication configuration:
- **Admin Guard**: Uses `admins` table/provider
- **Web Guard**: Uses `users` table/provider (tenant users)
- Password reset configuration
- Remember token settings

### `config/database.php`
Database configuration:
- Connection settings (MySQL, PostgreSQL, SQLite)
- Default connection
- Migration paths
- Query logging

### `config/mail.php` & `config/mailers.php`
Email configuration:
- Default mailer settings
- SMTP configuration for PHPMailer
- From address and name settings
- Mail driver options

**Key Settings:**
- `MAIL_SMTP_HOST`: SMTP server hostname
- `MAIL_SMTP_PORT`: SMTP port (587 for TLS)
- `MAIL_SMTP_USERNAME`: SMTP username
- `MAIL_SMTP_PASSWORD`: SMTP password
- `MAIL_SMTP_ENCRYPTION`: TLS or SSL

### `config/mpesa.php`
M-Pesa API configuration:
- Environment (sandbox/production)
- Consumer key/secret
- Shortcode and passkey
- Callback URL configuration
- Base URL for API requests

### `config/permission.php`
Spatie Permission package configuration:
- Cache settings
- Model settings
- Guard names
- Table names

### `config/queue.php`
Queue configuration:
- Default queue connection (database)
- Queue worker settings
- Failed job handling
- Queue name configuration

### `config/legitbooks.php`
LegitBooks-specific configuration:
- Branding mode settings
- Platform name
- Feature flags
- M-Pesa defaults

### `config/tenant_permissions.php`
Tenant user permission definitions:
- Available permissions list
- Permission descriptions
- Permission categories

---

## Routes Directory

### `routes/web.php`
Public and marketing routes.

**Marketing Routes:**
- `/`: Home page
- `/features`: Features page
- `/pricing`: Pricing page
- `/about`: About page
- `/contact`: Contact form
- `/faq`: FAQ page
- `/legal/privacy`: Privacy policy

**Route Groups:**
- `/admin/*`: Admin routes (loaded from `routes/admin.php`)
- `/app/*`: Tenant routes (loaded from `routes/tenant.php`)
- `/pay/*`: Public invoice payment routes
- `/webhooks/*`: Webhook endpoints (M-Pesa, PayPal)

**Public Routes:**
- `/invitation/accept/{token}`: Invitation acceptance

### `routes/admin.php`
Platform admin routes (requires `auth:admin`).

**Key Routes:**
- `GET /admin`: Dashboard
- `GET /admin/tenants`: Tenant list
- `POST /admin/tenants`: Create tenant
- `GET /admin/tenants/{id}`: Tenant details
- `PATCH /admin/tenants/{id}/suspend`: Suspend tenant
- `PATCH /admin/tenants/{id}/branding`: Update tenant branding
- `GET /admin/admins`: Admin user list
- `POST /admin/admins`: Create admin user
- `GET /admin/settings`: Platform settings
- `POST /admin/settings`: Update platform settings
- `GET /admin/reports`: Reports
- `POST /admin/reports/export`: Export reports

### `routes/tenant.php`
Tenant-scoped routes (requires tenant context and `auth:web`).

**Authentication Routes:**
- `GET /app/auth/login`: Login form
- `POST /app/auth/login`: Login submission
- `GET /app/auth/register`: Registration form
- `POST /app/auth/register`: Registration submission
- `GET /app/auth/billing`: Billing/plan selection
- `POST /app/auth/billing`: Process billing

**Protected Routes (require authentication + tenant context):**
- `GET /app/`: Dashboard
- `GET /app/invoices`: Invoice list
- `POST /app/invoices`: Create invoice
- `GET /app/invoices/{id}`: Invoice details
- `GET /app/payments`: Payment list
- `GET /app/contacts`: Contact list
- `GET /app/products`: Product list
- `GET /app/chart-of-accounts`: Chart of Accounts
- `GET /app/reports`: Reports
- `GET /app/billing`: Subscription/billing page

**Permission-Protected Routes:**
- Routes with `permission:` middleware check specific permissions
- Routes with `anypermission:` middleware check any of specified permissions

### `routes/api.php`
API endpoints:
- `POST /api/payments/mpesa/callback`: M-Pesa STK Push callback
- Other API endpoints for webhooks

### `routes/console.php`
Console/Artisan command route definitions (if any).

---

## App Directory

### Models (`app/Models/`)

#### Platform Models

**`Admin.php`**
Platform administrator model.

**Fields:**
- `id`: Primary key
- `name`: Admin name
- `email`: Email address (unique)
- `password`: Hashed password
- `role`: Role enum (owner, subadmin)
- `is_active`: Active status
- `timestamps`: created_at, updated_at

**Relationships:**
- `roles()`: Spatie Permission roles

**Guard:** `auth:admin`

**`Tenant.php`**
Tenant organization model.

**Fields:**
- `id`: Primary key
- `name`: Tenant name
- `email`: Tenant email
- `tenant_hash`: Unique hash for URL access (UUID-base64)
- `status`: Status enum (active, suspended, cancelled)
- `settings`: JSON column for tenant settings (branding, etc.)
- `timestamps`: created_at, updated_at

**Relationships:**
- `users()`: Has many tenant users
- `subscription()`: Has one subscription
- `invoices()`: Has many invoices
- `payments()`: Has many payments
- `contacts()`: Has many contacts
- `products()`: Has many products

**Key Methods:**
- `generateTenantHash()`: Generates unique tenant hash
- Branding helper methods

**`Subscription.php`**
Tenant subscription model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `plan`: Plan name (starter, business, enterprise)
- `payment_gateway`: Payment gateway used
- `started_at`: Subscription start date
- `ends_at`: Subscription end date
- `trial_ends_at`: Trial period end date
- `next_billing_at`: Next billing date
- `status`: Status enum (active, cancelled, expired)
- `vat_applied`: VAT applied flag
- `settings`: JSON column for subscription settings
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `payments()`: Has many payments

**`PlatformSetting.php`**
Global platform settings model.

**Fields:**
- `id`: Primary key
- `key`: Setting key
- `value`: Setting value (JSON)
- `timestamps`: created_at, updated_at

**Key Settings:**
- `branding_mode`: Global branding mode (A, B, C)
- `mpesa_consumer_key`: M-Pesa consumer key
- `mpesa_consumer_secret`: M-Pesa consumer secret
- `mpesa_shortcode`: M-Pesa shortcode
- `mpesa_passkey`: M-Pesa passkey

**`PlatformAuditLog.php`**
Platform-level audit trail model.

**Fields:**
- `id`: Primary key
- `admin_id`: Foreign key to admins
- `action`: Action performed
- `target_type`: Target model type
- `target_id`: Target model ID
- `details`: JSON details
- `timestamps`: created_at, updated_at

#### Tenant-Scoped Models (Extend `BaseTenantModel`)

**`User.php`**
Tenant user model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `name`: User name
- `first_name`: First name
- `last_name`: Last name
- `email`: Email address
- `password`: Hashed password
- `role_id`: Foreign key to roles (Spatie)
- `role_name`: Role name (string)
- `permissions`: JSON array of permissions
- `is_active`: Active status
- `is_owner`: Account owner flag
- `phone_country_code`: Phone country code
- `phone_number`: Phone number
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `role()`: Belongs to Spatie role
- `invitations()`: Has many user invitations sent

**Key Methods:**
- `hasPermission($permission)`: Check if user has permission
- `hasAnyPermission($permissions)`: Check if user has any permission
- Account owners always return true for permission checks

**`Invoice.php`**
Invoice model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `invoice_number`: Unique invoice number (INV-YYYY-XXXX)
- `contact_id`: Foreign key to contacts
- `invoice_date`: Invoice date
- `due_date`: Due date
- `status`: Status enum (draft, sent, paid, partially_paid, overdue, cancelled)
- `subtotal`: Subtotal amount
- `tax_amount`: Tax amount
- `total`: Total amount
- `notes`: Invoice notes
- `sent_at`: When invoice was sent
- `pdf_path`: Path to generated PDF
- `payment_token`: Token for public payment link
- `payment_status`: Payment status
- `mail_status`: Email sending status
- `mail_message_id`: Email message ID
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `contact()`: Belongs to contact
- `lineItems()`: Has many invoice line items
- `paymentAllocations()`: Has many payment allocations
- `journalEntry()`: Morph one journal entry

**Key Methods:**
- `isPaid()`: Check if invoice is fully paid
- `getOutstandingAmount()`: Calculate outstanding amount

**`InvoiceLineItem.php`**
Invoice line item model.

**Fields:**
- `id`: Primary key
- `invoice_id`: Foreign key to invoices
- `description`: Line item description
- `quantity`: Quantity
- `unit_price`: Unit price
- `total`: Total amount
- `sales_account_id`: Foreign key to chart of accounts
- `timestamps`: created_at, updated_at

**Relationships:**
- `invoice()`: Belongs to invoice
- `salesAccount()`: Belongs to chart of account

**`Payment.php`**
Payment transaction model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `user_id`: Foreign key to users (who created payment)
- `invoice_id`: Foreign key to invoices (optional)
- `subscription_id`: Foreign key to subscriptions (optional)
- `payment_number`: Payment number
- `payment_date`: Payment date
- `account_id`: Foreign key to accounts (bank/cash)
- `contact_id`: Foreign key to contacts
- `amount`: Payment amount
- `currency`: Currency code
- `payment_method`: Payment method (mpesa, card, paypal, cash, check)
- `reference`: Payment reference
- `notes`: Payment notes
- `mpesa_metadata`: JSON M-Pesa metadata
- `phone`: Phone number (for M-Pesa)
- `mpesa_receipt`: M-Pesa receipt number
- `transaction_status`: Transaction status
- `raw_callback`: JSON raw callback data
- `checkout_request_id`: M-Pesa checkout request ID
- `merchant_request_id`: M-Pesa merchant request ID
- `client_token`: Unique client token (UUID)
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `account()`: Belongs to account
- `contact()`: Belongs to contact
- `allocations()`: Has many payment allocations
- `invoices()`: Belongs to many invoices (via allocations)
- `journalEntry()`: Morph one journal entry

**`PaymentAllocation.php`**
Payment-to-invoice allocation model.

**Fields:**
- `id`: Primary key
- `payment_id`: Foreign key to payments
- `invoice_id`: Foreign key to invoices
- `amount`: Allocated amount
- `timestamps`: created_at, updated_at

**Relationships:**
- `payment()`: Belongs to payment
- `invoice()`: Belongs to invoice

**`Contact.php`**
Customer/vendor contact model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `name`: Contact name
- `email`: Email address
- `phone`: Phone number
- `type`: Type enum (customer, vendor)
- `address`: Address
- `tax_id`: Tax ID
- `tax_rate`: Tax rate
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `invoices()`: Has many invoices
- `bills()`: Has many bills
- `payments()`: Has many payments
- `recurringTemplates()`: Has many recurring templates

**`Product.php`**
Product/service catalog model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `name`: Product name
- `sku`: SKU code
- `description`: Product description
- `price`: Unit price
- `sales_account_id`: Foreign key to chart of accounts
- `is_active`: Active status
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `salesAccount()`: Belongs to chart of account
- `invoiceLineItems()`: Has many invoice line items

**`ChartOfAccount.php`**
Chart of accounts model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `code`: Account code
- `name`: Account name
- `type`: Account type (asset, liability, equity, revenue, expense)
- `category`: Account category
- `parent_id`: Foreign key to parent account
- `is_active`: Active status
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `parent()`: Belongs to parent account
- `children()`: Has many child accounts
- `accounts()`: Has many bank/cash accounts
- `journalLines()`: Has many journal lines

**`JournalEntry.php`**
Double-entry journal entry model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `entry_number`: Journal entry number
- `entry_date`: Entry date
- `reference_type`: Reference model type (polymorphic)
- `reference_id`: Reference model ID
- `description`: Entry description
- `total_debits`: Total debit amount
- `total_credits`: Total credit amount
- `is_posted`: Posted flag
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `lines()`: Has many journal lines
- `reference()`: Morph to (Invoice, Payment, etc.)

**Key Methods:**
- `isBalanced()`: Check if debits equal credits
- `calculateTotals()`: Calculate total debits and credits

**`JournalLine.php`**
Journal entry line model.

**Fields:**
- `id`: Primary key
- `journal_entry_id`: Foreign key to journal entries
- `chart_of_account_id`: Foreign key to chart of accounts
- `type`: Line type (debit, credit)
- `amount`: Line amount
- `description`: Line description
- `timestamps`: created_at, updated_at

**Relationships:**
- `journalEntry()`: Belongs to journal entry
- `chartOfAccount()`: Belongs to chart of account

**`Account.php`**
Bank/cash accounts model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `name`: Account name
- `account_number`: Account number
- `chart_of_account_id`: Foreign key to chart of accounts
- `balance`: Account balance
- `timestamps`: created_at, updated_at

**Relationships:**
- `tenant()`: Belongs to tenant
- `chartOfAccount()`: Belongs to chart of account
- `payments()`: Has many payments

**`InvoiceCounter.php`**
Per-tenant invoice numbering counter.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `year`: Year for counter
- `sequence`: Current sequence number
- `timestamps`: created_at, updated_at

**Unique Constraint:** `tenant_id` + `year` (one counter per tenant per year)

**Purpose:** Transaction-safe invoice number generation

**`Bill.php`**, **`BillLineItem.php`**, **`BillCounter.php`**
Bill (accounts payable) models similar to invoice models.

**`RecurringTemplate.php`**
Recurring invoice template model.

**Fields:**
- `id`: Primary key
- `tenant_id`: Foreign key to tenants
- `contact_id`: Foreign key to contacts
- `frequency`: Frequency enum (daily, weekly, monthly, quarterly, yearly)
- `next_run_at`: Next run date
- `template_data`: JSON template data
- `is_active`: Active status
- `timestamps`: created_at, updated_at

**`FixedAsset.php`**
Fixed asset tracking model.

**`Attachment.php`**
File attachment model.

**`AuditLog.php`**
Tenant-level audit log model.

**`CsvImportJob.php`**
CSV import job tracking model.

**`ContactSubmission.php`**
Marketing contact form submission model.

**`UserInvitation.php`**
User invitation model.

**`AdminInvitation.php`**
Admin invitation model.

**`PaymentMethod.php`**
Stored payment method model.

**`PlatformCsvTemplate.php`**
Platform CSV template model.

#### Base Classes and Traits

**`BaseTenantModel.php`**
Abstract base class for all tenant-scoped models.

**Features:**
- Uses `HasTenantScope` trait
- Provides `tenant()` relationship
- All tenant models extend this class

**`HasTenantScope.php`** (`app/Models/Traits/HasTenantScope.php`)
Global scope trait for tenant isolation.

**Features:**
- Automatically filters queries by `tenant_id`
- Auto-fills `tenant_id` on model creation
- Uses `TenantContext` service to get current tenant

---

### Controllers (`app/Http/Controllers/`)

#### Admin Controllers (`app/Http/Controllers/Admin/`)

**`AdminAuthController.php`**
Platform admin authentication controller.

**Methods:**
- `showLoginForm()`: Display login form
- `login()`: Process login
- `logout()`: Process logout

**Routes:**
- `GET /admin/login`
- `POST /admin/login`
- `POST /admin/logout`

**`TenantController.php`**
Tenant management controller.

**Methods:**
- `index()`: List all tenants
- `create()`: Show create form
- `store()`: Create new tenant (triggers provisioning)
- `show()`: Show tenant details
- `edit()`: Show edit form
- `update()`: Update tenant
- `destroy()`: Delete tenant
- `suspend()`: Suspend tenant
- `updateBranding()`: Update tenant branding

**Routes:**
- `GET /admin/tenants`
- `POST /admin/tenants`
- `GET /admin/tenants/{id}`
- `PATCH /admin/tenants/{id}/suspend`
- `PATCH /admin/tenants/{id}/branding`

**Uses:** `TenantProvisioningService`

**`AdminUserController.php`**
Platform admin user management controller.

**Methods:**
- `index()`: List all admins
- `create()`: Show create form
- `store()`: Create admin user
- `edit()`: Show edit form
- `update()`: Update admin user
- `destroy()`: Delete admin user

**`PlatformSettingsController.php`**
Global platform settings controller.

**Methods:**
- `index()`: Show settings form
- `update()`: Update platform settings

**Settings:**
- Branding mode
- M-Pesa defaults
- Email settings

**`ReportsController.php`**
Report generation and export controller.

**Methods:**
- `index()`: Show reports page
- `export()`: Export reports (CSV, Excel, PDF)

**Report Types:**
- Tenant overview
- Revenue summary
- Subscription metrics
- Payment collection

**`TenantDetailsController.php`**
Tenant details view controller.

**`TenantUserAdminController.php`**
Tenant user management from admin panel.

**`TenantInvoiceAdminController.php`**
Tenant invoice management from admin panel.

**`AdminInvitationController.php`**
Admin invitation management controller.

**`AdminProfileController.php`**
Admin profile management controller.

#### Tenant Controllers (`app/Http/Controllers/Tenant/`)

**`TenantAuthController.php`**
Tenant user authentication controller.

**Methods:**
- `showLoginForm()`: Display login form
- `login()`: Process login
- `logout()`: Process logout

**Routes:**
- `GET /app/auth/login`
- `POST /app/auth/login`
- `POST /app/auth/logout`

**`TenantRegistrationController.php`**
Tenant self-registration controller.

**Methods:**
- `showRegistrationForm()`: Show registration form
- `register()`: Process registration
- `showBillingForm()`: Show billing/plan selection
- `processBilling()`: Process billing and payment

**`InvoiceController.php`**
Invoice management controller.

**Methods:**
- `index()`: List invoices
- `create()`: Show create form
- `store()`: Create invoice
- `show()`: Show invoice details
- `edit()`: Show edit form
- `update()`: Update invoice
- `destroy()`: Delete invoice
- `send()`: Send invoice via email
- `downloadPdf()`: Download invoice PDF

**Routes:**
- `GET /app/invoices`
- `POST /app/invoices`
- `GET /app/invoices/{id}`
- `PATCH /app/invoices/{id}`
- `DELETE /app/invoices/{id}`
- `POST /app/invoices/{id}/send`
- `GET /app/invoices/{id}/pdf`

**`PaymentController.php`**
Payment management controller.

**Methods:**
- `index()`: List payments
- `show()`: Show payment details
- `store()`: Create manual payment
- `allocate()`: Allocate payment to invoices

**`DashboardController.php`**
Tenant dashboard controller.

**Methods:**
- `index()`: Show dashboard with charts and statistics

**`ContactController.php`**
Contact management controller.

**Methods:**
- `index()`: List contacts
- `create()`: Show create form
- `store()`: Create contact
- `show()`: Show contact details
- `edit()`: Show edit form
- `update()`: Update contact
- `destroy()`: Delete contact

**`ProductController.php`**
Product management controller.

**Methods:**
- `index()`: List products
- `create()`: Show create form
- `store()`: Create product
- `edit()`: Show edit form
- `update()`: Update product
- `destroy()`: Delete product

**`ChartOfAccountController.php`**
Chart of Accounts management controller.

**Methods:**
- `index()`: List accounts
- `create()`: Show create form
- `store()`: Create account
- `show()`: Show account details
- `edit()`: Show edit form
- `update()`: Update account

**`BillingController.php`**
Subscription billing controller.

**Methods:**
- `index()`: Show billing page
- `processPayment()`: Process subscription payment

**`MpesaController.php`**
M-Pesa payment controller.

**Methods:**
- `callback()`: Handle M-Pesa callback

**`ReportsController.php`**
Tenant reports controller.

**Methods:**
- `index()`: Show reports page
- `export()`: Export reports

**`ProfileController.php`**
User profile controller.

**Methods:**
- `index()`: Show profile
- `update()`: Update profile

**`TenantUserController.php`**
Tenant user management controller.

**Methods:**
- `index()`: List users
- `invite()`: Show invite form
- `storeInvite()`: Send invitation
- `edit()`: Show edit form
- `update()`: Update user
- `destroy()`: Delete user

**`BillController.php`**
Bill (accounts payable) management controller.

**`CheckoutController.php`**
Payment checkout controller.

**`PaymentJsonController.php`**
Payment JSON API controller.

**`PaymentReceiptController.php`**
Payment receipt controller.

#### Marketing Controllers (`app/Http/Controllers/Marketing/`)

**`HomeController.php`**
Home page controller.

**`FeaturesController.php`**
Features page controller.

**`PricingController.php`**
Pricing page controller.

**`AboutController.php`**
About page controller.

**`ContactController.php`**
Contact form controller.

**Methods:**
- `showForm()`: Show contact form
- `submitForm()`: Process contact form submission

**Uses:** `PHPMailerService` to send notification emails

**`SolutionsController.php`**
Solutions page controller.

**`FaqController.php`**
FAQ page controller.

**`LegalController.php`**
Legal pages controller (privacy, terms).

#### API Controllers (`app/Http/Controllers/Api/`)

**`MpesaStkController.php`**
M-Pesa STK Push API controller.

**Methods:**
- `stkPush()`: Initiate STK Push
- `callback()`: Handle STK Push callback

#### Other Controllers

**`InvoicePaymentController.php`**
Public invoice payment controller (no auth required).

**Methods:**
- `show()`: Show payment page
- `processMpesa()`: Process M-Pesa payment
- `processCard()`: Process card payment
- `checkPaymentStatus()`: Check payment status
- `success()`: Payment success page

**Routes:**
- `GET /pay/{invoice}/{token}`
- `POST /pay/{invoice}/{token}/mpesa`
- `GET /pay/{invoice}/{token}/success`

**`InvitationController.php`**
Invitation acceptance controller.

**Methods:**
- `show()`: Show invitation acceptance form
- `accept()`: Process invitation acceptance

---

### Services (`app/Services/`)

**`TenantContext.php`**
Service for managing current tenant context.

**Methods:**
- `setTenant(Tenant $tenant)`: Set current tenant
- `getTenant()`: Get current tenant
- `clear()`: Clear tenant context

**Usage:** Used by middleware and models to get current tenant

**`TenantProvisioningService.php`**
Handles tenant setup when created.

**Methods:**
- `provision(Tenant $tenant, array $options)`: Provision tenant

**Actions:**
- Creates invoice counter
- Optionally seeds Chart of Accounts
- Optionally creates tenant admin user
- Optionally seeds demo data

**`InvoiceNumberService.php`**
Transaction-safe invoice number generation.

**Methods:**
- `generate(int $tenantId)`: Generate unique invoice number

**Format:** `INV-YYYY-XXXX` (year + sequence)

**Features:**
- Uses database locks (`lockForUpdate`) for concurrency safety
- Year-based counters (one counter per tenant per year)
- Handles race conditions

**`InvoicePostingService.php`**
Creates journal entries when invoice is sent.

**Methods:**
- `postInvoice(Invoice $invoice)`: Post invoice to journal

**Journal Entry:**
- Debit: Accounts Receivable
- Credit: Sales Revenue (per line item)
- Credit: Tax Liability (if applicable)

**Validates:** Journal entry balance (debits = credits)

**`PaymentService.php`**
Processes payments and creates journal entries.

**Methods:**
- `processPayment(Payment $payment, array $allocations)`: Process payment

**Journal Entry:**
- Debit: Bank/Cash Account
- Credit: Accounts Receivable

**Actions:**
- Creates payment allocations
- Updates invoice payment status
- Validates journal entry balance

**`InvoicePdfService.php`**
Generates PDF invoices using DomPDF.

**Methods:**
- `generate(Invoice $invoice)`: Generate invoice PDF

**Features:**
- Renders Blade template to PDF
- Includes tenant branding based on mode
- Supports custom styling

**`InvoiceSendService.php`**
Handles invoice email sending.

**Methods:**
- `send(Invoice $invoice)`: Send invoice via email

**Actions:**
- Generates PDF
- Sends email via PHPMailerService
- Attaches PDF
- Includes payment link

**`MpesaStkService.php`**
M-Pesa STK Push integration service.

**Methods:**
- `initiateStkPush(array $data)`: Initiate STK Push request
- `handleCallback(array $data)`: Handle callback response

**Features:**
- OAuth token generation
- STK Push request formatting
- Callback validation
- Receipt processing

**`MpesaService.php`**
General M-Pesa API service.

**Methods:**
- `getAccessToken()`: Get OAuth access token
- `validateReceipt(string $receipt)`: Validate M-Pesa receipt

**`MpesaReceiptValidationService.php`**
M-Pesa receipt validation service.

**`PHPMailerService.php`** (`app/Services/Mail/PHPMailerService.php`)
Email sending service using PHPMailer.

**Methods:**
- `send(array $data)`: Send email

**Parameters:**
- `to`: Recipient email
- `subject`: Email subject
- `html`: HTML email body
- `text`: Plain text body (optional)
- `reply_to`: Reply-to email (optional)
- `from_name`: From name (optional)
- `attachments`: Array of file paths (optional)

**Features:**
- SMTP configuration
- HTML email support
- Attachment support
- Error logging

**`EmailService.php`**
Email service wrapper.

**`MailService.php`**
Mail service wrapper.

**`PlatformSettings.php`**
Platform settings service.

**Methods:**
- `get(string $key, $default)`: Get setting value
- `set(string $key, $value)`: Set setting value

**`BillNumberService.php`**
Bill number generation service (similar to InvoiceNumberService).

---

### Middleware (`app/Http/Middleware/`)

**`ResolveTenant.php`**
Resolves tenant from request.

**Logic:**
1. Try to get tenant from session (`tenant_id`)
2. If not in session, try authenticated user's `tenant_id`
3. If still no tenant, try route parameter `tenant_hash`
4. Set tenant in `TenantContext`
5. Store tenant ID in session for future requests

**Handles:**
- Tenant not found: Returns 404
- Tenant suspended: Redirects with error message

**`EnsureTenantActive.php`**
Validates tenant is active.

**Checks:**
- Tenant status is `active`
- Redirects if tenant is suspended or cancelled

**`EnsureUserIsActive.php`**
Validates user account is active.

**Checks:**
- User `is_active` flag is true
- Redirects if user is inactive

**`EnsureUserHasPermission.php`**
Permission check middleware.

**Parameters:**
- Permission name to check

**Logic:**
- Checks if user has specific permission
- Account owners always pass
- Redirects if permission denied

**`EnsureUserHasAnyPermission.php`**
Multiple permission check middleware.

**Parameters:**
- Array of permission names

**Logic:**
- Checks if user has any of specified permissions
- Account owners always pass
- Redirects if no permissions match

---

### Jobs (`app/Jobs/`)

**`SendInvoiceEmailJob.php`**
Queue job for sending invoice emails.

**Actions:**
- Generates PDF via InvoicePdfService
- Sends email via PHPMailerService
- Updates invoice `mail_status`
- Handles failures

**`GenerateRecurringInvoicesJob.php`**
Generates invoices from recurring templates.

**Actions:**
- Checks templates for due dates (`next_run_at`)
- Creates invoices from templates
- Calls InvoicePostingService
- Updates template `next_run_at`

**`ProcessMpesaCallbackJob.php`**
Processes M-Pesa payment callbacks.

**Actions:**
- Validates callback data
- Creates payment records
- Updates invoice status
- Processes journal entries

**`ProcessCsvImportJob.php`**
Processes CSV imports.

**Actions:**
- Parses CSV files
- Validates data
- Creates records (contacts, products, COA, etc.)
- Generates import reports
- Writes to `csv_import_jobs` table

---

### Observers (`app/Observers/`)

**`InvoiceObserver.php`**
Invoice model observer.

**Events:**
- `creating`: Assigns invoice number via InvoiceNumberService
- `updating`: When status changes to `sent`, triggers InvoicePostingService

**`PaymentObserver.php`**
Payment model observer.

**Events:**
- `created`: Triggers PaymentService to process payment

**`AuditObserver.php`**
General audit logging observer.

**Events:**
- `created`, `updated`, `deleted`: Records model changes

**Actions:**
- Stores before/after states
- Tracks user who made changes
- Writes to `audit_logs` table

---

### Commands (`app/Console/Commands/`)

**`LegitBooksTenantCreate.php`**
Artisan command: `php artisan legitbooks:tenant:create`

**Usage:**
```bash
php artisan legitbooks:tenant:create "Company Name" company@example.com
```

**Actions:**
- Creates tenant
- Runs provisioning
- Optionally creates admin user

**`GenerateDatabaseSql.php`**
Artisan command: `php artisan db:generate-sql`

**Options:**
- `--all`: Generate schema and seed data
- `--schema-only`: Generate schema only
- `--seeds-only`: Generate seed data only

**Output:**
- `database/schema.sql`: Schema only
- `database/seed_data.sql`: Seed data only
- `database/setup.sql`: Combined file

**`GenerateScheduledReports.php`**
Artisan command: `php artisan reports:generate-scheduled`

**Options:**
- `--frequency`: daily, weekly, monthly
- `--report`: tenant_overview, revenue, subscription, payment, all
- `--format`: csv, excel, pdf
- `--email`: Email address to send reports

**`MpesaSimulate.php`**
Artisan command: `php artisan mpesa:simulate`

**Usage:**
```bash
php artisan mpesa:simulate {tenant_hash} {phone} {amount}
```

**Purpose:** Simulate M-Pesa payment for testing

**`SyncPendingMpesaPayments.php`**
Artisan command: `php artisan mpesa:sync-pending`

**Purpose:** Sync pending M-Pesa payments

**`TenantBackup.php`**
Artisan command: `php artisan tenant:backup`

**Usage:**
```bash
php artisan tenant:backup {tenant_hash}
```

**Purpose:** Creates SQL backup of tenant data

**Output:** `storage/backups/{tenant_hash}/backup.sql`

---

### Providers (`app/Providers/`)

**`AppServiceProvider.php`**
Main service provider.

**Register Method:**
- Registers `TenantContext` as singleton
- Registers `PlatformSettings` as singleton

**Boot Method:**
- Registers observers (InvoiceObserver, PaymentObserver, AuditObserver)
- Registers Blade directives:
  - `@perm($permission)`: Check if user has permission
  - `@anyperm($permissions)`: Check if user has any permission
- Loads platform settings from database (if available)
- Handles database connection errors gracefully

---

### Helpers (`app/Helpers/`)

**`AuditLog.php`**
Audit logging helper functions.

**`PaymentHelper.php`**
Payment processing helper functions.

---

### Exports (`app/Exports/`)

**`InvoiceExport.php`**
Invoice export class (for Excel/CSV).

**`ReportExport.php`**
Report export class (for Excel/CSV).

---

### Mail (`app/Mail/`)

**`ContactNotification.php`**
Contact form notification email class.

---

## Database Directory

### Migrations (`database/migrations/`)

55+ migration files creating all database tables.

**Key Migrations:**
- `create_admins_table`: Platform administrators
- `create_tenants_table`: Tenant organizations
- `create_users_table`: Tenant users
- `create_invoices_table`: Invoice records
- `create_payments_table`: Payment transactions
- `create_contacts_table`: Customer/vendor contacts
- `create_products_table`: Product catalog
- `create_chart_of_accounts_table`: Chart of accounts
- `create_journal_entries_table`: Journal entries
- `create_journal_lines_table`: Journal entry lines
- `create_subscriptions_table`: Tenant subscriptions
- `create_roles_table`: Spatie Permission roles
- `create_permissions_table`: Spatie Permission permissions
- And 40+ more migrations

### Seeders (`database/seeders/`)

**`DatabaseSeeder.php`**
Main seeder that calls other seeders.

**`SuperAdminSeeder.php`**
Creates platform super admin user:
- Email: `admin@legitbooks.com`
- Password: `password` (change immediately!)
- Role: `owner`

**`PlatformSettingsSeeder.php`**
Seeds default platform settings:
- Branding mode: `A`
- M-Pesa environment: `sandbox`

**`COASeeder.php`**
Seeds default Chart of Accounts for tenants (optional).

**`DemoTenantSeeder.php`**
Creates demo tenant with test data (optional).

**`BackfillDefaultSubscriptionsSeeder.php`**
Backfills default subscriptions for existing tenants.

### Factories (`database/factories/`)

**`AdminFactory.php`**
Factory for creating test admin users.

**`UserFactory.php`**
Factory for creating test tenant users.

### `setup.sql`
Complete database setup file:
- All table schemas (42 tables)
- Foreign key constraints
- Initial seed data
- Ready for XAMPP/MySQL import

**Usage:**
```bash
mysql -u root -p legitbooks < database/setup.sql
```

---

## Resources Directory

### Views (`resources/views/`)

#### Admin Views (`resources/views/admin/`)

**Layouts:**
- `layouts/admin.blade.php`: Admin portal layout

**Pages:**
- `dashboard.blade.php`: Admin dashboard
- `tenants/`: Tenant management views (index, create, show)
- `admins/`: Admin user management views
- `settings/`: Platform settings views
- `reports/`: Report views
- `auth/`: Admin login page
- `profile/`: Admin profile page
- `invitations/`: Invitation acceptance pages

#### Tenant Views (`resources/views/tenant/`)

**Layouts:**
- `layouts/tenant.blade.php`: Tenant portal layout

**Pages:**
- `dashboard.blade.php`: Tenant dashboard with charts
- `invoices/`: Invoice management views (index, create, edit, show, pdf)
- `payments/`: Payment views (index, receipts)
- `contacts/`: Contact management views
- `products/`: Product management views
- `chart-of-accounts/`: Chart of Accounts views
- `billing/`: Subscription/billing views
- `reports/`: Report views
- `auth/`: Login and registration pages
- `profile/`: User profile page
- `users/`: User management views
- `bills/`: Bill management views
- `checkout/`: Payment checkout pages

#### Marketing Views (`resources/views/marketing/`)

**Pages:**
- `home.blade.php`: Landing page
- `features.blade.php`: Features page
- `pricing.blade.php`: Pricing page
- `about.blade.php`: About page
- `contact.blade.php`: Contact form
- `faq.blade.php`: FAQ page
- `solutions.blade.php`: Solutions page
- `legal/privacy.blade.php`: Privacy policy

**Components:**
- `components/navbar.blade.php`: Navigation bar
- `components/footer.blade.php`: Footer
- `components/cta.blade.php`: Call-to-action sections

**Layout:**
- `layouts/marketing.blade.php`: Marketing site layout

#### Email Views (`resources/views/emails/`)

- `contact/internal.blade.php`: Contact form notification email
- `invoice/send.blade.php`: Invoice email template
- `invoice/receipt.blade.php`: Invoice receipt email
- `admin/invite.blade.php`: Admin invitation email
- `users/invitation.blade.php`: User invitation email

#### Other Views

- `layouts/`: Layout templates
- `components/`: Reusable Blade components
- `invitations/`: Invitation acceptance pages
- `invoice/payment/`: Public invoice payment pages

### CSS (`resources/css/`)

- `app.css`: Main application CSS (Tailwind)
- `admin.css`: Admin-specific styles
- `tenant.css`: Tenant-specific styles

**Compiled to:** `public/build/assets/*.css`

### JS (`resources/js/`)

- `app.js`: Main JavaScript entry point
- `bootstrap.js`: Bootstrap configuration

**Compiled to:** `public/build/assets/*.js`

### CSV Templates (`resources/csv_templates/`)

CSV import templates for:
- Contacts
- Products
- Chart of Accounts
- Opening balances

---

## Public Directory

### `public/index.php`
Laravel entry point:
- Bootstraps application
- Handles requests
- Requires `vendor/autoload.php`

### `public/.htaccess`
Apache configuration for URL rewriting.

### `public/build/`
Compiled assets (CSS/JS) generated by Vite.

### `public/storage/`
Symbolic link to `storage/app/public` for serving uploaded files.

---

## Storage Directory

### `storage/app/`
File storage:
- `public/`: Publicly accessible files (linked to `public/storage`)

### `storage/logs/`
Application logs:
- `laravel.log`: Main application log file

### `storage/framework/`
Framework files:
- `cache/`: Cache files
- `sessions/`: Session files
- `views/`: Compiled Blade views

### `storage/backups/`
Tenant backup files (created by `tenant:backup` command).

---

## Tests Directory

### Feature Tests (`tests/Feature/`)

**Admin Tests:**
- `Admin/AdminInviteTest.php`: Admin invitation flow
- `Admin/TenantDetailsTest.php`: Tenant details viewing
- `Admin/TenantUserManagementTest.php`: Tenant user management
- `Admin/TenantInvoicesAdminTest.php`: Tenant invoice management from admin
- `Admin/ExportInvoicesTest.php`: Invoice export functionality

**Integration Tests:**
- `Integration/TenantCreationFlowTest.php`: Complete tenant creation flow
- `Integration/InvoicePaymentFlowTest.php`: Invoice to payment flow

**Marketing Tests:**
- `Marketing/ContactFormTest.php`: Contact form submission
- `Marketing/MarketingRoutesTest.php`: Marketing page routes

**M-Pesa Tests:**
- `MpesaCheckoutFlowTest.php`: M-Pesa checkout flow
- `MpesaStkFlowTest.php`: M-Pesa STK Push flow

**Tenant Tests:**
- `Tenant/InvoiceControllerTest.php`: Invoice controller tests
- `Tenant/PaymentControllerTest.php`: Payment controller tests
- `Tenant/MpesaBillingWorkflowTest.php`: M-Pesa billing workflow

**Other Tests:**
- `InvoiceSendWorkflowTest.php`: Invoice sending workflow
- `InvoiceSequenceConcurrencyTest.php`: Invoice number concurrency
- `SubscriptionFlowTest.php`: Subscription management flow

### Unit Tests (`tests/Unit/`)

**Service Tests:**
- `InvoiceNumberServiceTest.php`: Invoice number generation
- `InvoicePostingServiceTest.php`: Journal entry creation and balancing
- `PaymentServiceTest.php`: Payment processing and allocation
- `TenantProvisioningServiceTest.php`: Tenant setup validation

**Model Tests:**
- `InvoiceModelTest.php`: Invoice model relationships and methods
- `JournalEntryModelTest.php`: Journal entry balancing validation
- `TenantModelTest.php`: Tenant methods and branding
- `UserModelTest.php`: User permission checks
- `ContactModelTest.php`: Contact relationships
- `ProductModelTest.php`: Product relationships

### Test Helpers

**`run_tests.sh`**
Script to run test suite with dependency checks.

**`TestCase.php`**
Base test case class.

---

# Technical Requirements

## Server Requirements

### PHP
- **Version**: PHP 8.2 or higher
- **Extensions Required**:
  - BCMath
  - Ctype
  - cURL
  - DOM
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PCRE
  - PDO
  - Tokenizer
  - XML
  - **GD** (for Excel/PDF generation)

### Database
- **MySQL**: 8.0+ (Recommended)
- **PostgreSQL**: 13+ (Alternative)
- **SQLite**: 3.x (Development only)

### Web Server
- **Apache**: 2.4+ with mod_rewrite
- **Nginx**: 1.18+ (Alternative)
- **PHP Built-in Server**: For development

### Node.js
- **Version**: Node.js 18+ and npm

## Software Dependencies

### PHP Packages (via Composer)
- Laravel Framework ^12.0
- Spatie Laravel Permission ^6.23
- PHPMailer ^7.0
- DomPDF ^3.1
- Maatwebsite Excel ^3.1
- Guzzle HTTP ^7.10

### Node.js Packages (via npm)
- Vite ^7.0.7
- Tailwind CSS ^4.0.0
- Laravel Vite Plugin ^2.0.0
- Axios ^1.11.0

## External Services

### Email (SMTP)
- Gmail SMTP (configured)
- Or any SMTP server (Mailgun, SendGrid, etc.)

### Payment Gateway
- M-Pesa Daraja API (Safaricom)
- Sandbox credentials for testing
- Production credentials for live environment

### Optional Services
- Cloudflare Tunnel (for local webhook testing)
- Redis (for caching/queues, optional)

---

# Setup & Installation

## Step 1: Clone Repository

```bash
git clone <repository-url> LegitBooks
cd LegitBooks
```

## Step 2: Install PHP Dependencies

```bash
composer install
```

This installs all PHP packages defined in `composer.json`.

## Step 3: Install Node.js Dependencies

```bash
npm install
```

This installs all frontend packages defined in `package.json`.

## Step 4: Environment Configuration

```bash
# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate
```

## Step 5: Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=legitbooks
DB_USERNAME=root
DB_PASSWORD=
```

## Step 6: Database Setup

**Option 1: Import SQL File (Recommended)**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE legitbooks;"

# Import setup SQL
mysql -u root -p legitbooks < database/setup.sql
```

**Option 2: Run Migrations**
```bash
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder
```

## Step 7: Configure Email

Edit `.env` file:

```env
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME=your-email@gmail.com
MAIL_SMTP_PASSWORD=your-app-password
MAIL_SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=LegitBooks
CONTACT_SUPPORT_EMAIL=your-email@gmail.com
```

**Note**: For Gmail, use an App Password, not your regular password.

## Step 8: Configure M-Pesa (Optional)

Edit `.env` file:

```env
MPESA_ENVIRONMENT=sandbox
MPESA_CONSUMER_KEY=your-consumer-key
MPESA_CONSUMER_SECRET=your-consumer-secret
MPESA_SHORTCODE=your-shortcode
MPESA_PASSKEY=your-passkey
MPESA_BASE_URL=https://sandbox.safaricom.co.ke
```

## Step 9: Build Frontend Assets

```bash
# Production build
npm run build

# OR development with hot reload (run in separate terminal)
npm run dev
```

## Step 10: Create Storage Link

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public` for serving uploaded files.

## Step 11: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

# Running the Application

## Development Mode

### Option 1: All-in-One (Recommended)

```bash
composer run dev
```

This starts:
- Laravel server (port 8000)
- Queue worker
- Laravel Pail (logs)
- Vite dev server (hot reload)

### Option 2: Separate Terminals

**Terminal 1 - Laravel Server:**
```bash
./serve.sh
# OR
php artisan serve
```

**Terminal 2 - Vite Dev Server (optional):**
```bash
npm run dev
```

**Terminal 3 - Queue Worker (if using queues):**
```bash
php artisan queue:work
```

**Terminal 4 - Cloudflare Tunnel (for M-Pesa callbacks):**
```bash
./cloudflared-tunnel.sh
```

## Access Points

After starting the server:

- **Marketing Site**: `http://localhost:8000/`
- **Admin Panel**: `http://localhost:8000/admin/login`
- **Tenant Portal**: `http://localhost:8000/app/auth/login`

## Default Credentials

**Platform Admin:**
- Email: `admin@legitbooks.com`
- Password: `password`

**Important**: Change the default password immediately after first login.

## Production Mode

### 1. Set Environment

```env
APP_ENV=production
APP_DEBUG=false
```

### 2. Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Build Assets

```bash
npm run build
```

### 4. Start Server

Use a process manager (Supervisor, systemd) or configure a web server (Nginx/Apache) to point to `public/` directory.

### 5. Start Queue Workers

```bash
php artisan queue:work --daemon
```

---

# Configuration Guide

## Branding Modes

### Mode A: Tenant Name + LegitBooks
- Displays tenant name with "via LegitBooks" or "Powered by LegitBooks"
- Default mode

**Configuration:**
```env
BRANDING_MODE=A
```

### Mode B: Full LegitBooks Branding
- Complete LegitBooks branding throughout
- Suitable for direct LegitBooks customers

**Configuration:**
```env
BRANDING_MODE=B
```

### Mode C: White Label
- Complete tenant-specific branding
- No LegitBooks references visible
- Requires tenant branding settings

**Configuration:**
```env
BRANDING_MODE=C
```

**Per-Tenant Override:**
Tenants can override global branding mode via admin panel at `/admin/tenants/{tenant}/branding`.

## Database Configuration

### MySQL (Recommended)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=legitbooks
DB_USERNAME=root
DB_PASSWORD=your-password
```

### PostgreSQL

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=legitbooks
DB_USERNAME=postgres
DB_PASSWORD=your-password
```

### SQLite (Development)

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

## Email Configuration

### Gmail SMTP

```env
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME=your-email@gmail.com
MAIL_SMTP_PASSWORD=your-app-password
MAIL_SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME=LegitBooks
CONTACT_SUPPORT_EMAIL=your-email@gmail.com
```

### Other SMTP Providers

**Mailtrap (Testing):**
```env
MAIL_SMTP_HOST=smtp.mailtrap.io
MAIL_SMTP_PORT=2525
MAIL_SMTP_USERNAME=your-mailtrap-username
MAIL_SMTP_PASSWORD=your-mailtrap-password
```

**Outlook/Office 365:**
```env
MAIL_SMTP_HOST=smtp.office365.com
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME=your-email@outlook.com
MAIL_SMTP_PASSWORD=your-password
```

## M-Pesa Configuration

### Sandbox (Testing)

```env
MPESA_ENVIRONMENT=sandbox
MPESA_CONSUMER_KEY=your-sandbox-consumer-key
MPESA_CONSUMER_SECRET=your-sandbox-consumer-secret
MPESA_SHORTCODE=174379
MPESA_PASSKEY=your-sandbox-passkey
MPESA_BASE_URL=https://sandbox.safaricom.co.ke
MPESA_CALLBACK_BASE=https://your-tunnel-url.trycloudflare.com
MPESA_CALLBACK_URL=${MPESA_CALLBACK_BASE}/api/payments/mpesa/callback
```

### Production

```env
MPESA_ENVIRONMENT=production
MPESA_CONSUMER_KEY=your-production-consumer-key
MPESA_CONSUMER_SECRET=your-production-consumer-secret
MPESA_SHORTCODE=your-production-shortcode
MPESA_PASSKEY=your-production-passkey
MPESA_BASE_URL=https://api.safaricom.co.ke
MPESA_CALLBACK_URL=https://your-domain.com/api/payments/mpesa/callback
```

---

# Maintenance & Updates

## Updating This Documentation

This documentation should be updated after every significant change to the codebase. When updating:

1. **Add new files**: Document new files in the File-by-File section
2. **Update architecture**: If architecture changes, update the Architecture section
3. **Update configuration**: If new environment variables are added, document them
4. **Update setup steps**: If installation process changes, update Setup section
5. **Update version**: Increment version number and update date at top of file

## Version History

- **v1.0** (2026-01-21): Initial comprehensive documentation

## Common Maintenance Tasks

### Clear All Caches

```bash
php artisan optimize:clear
```

### Regenerate Database SQL

```bash
php artisan db:generate-sql --all
```

### Run Tests

```bash
php artisan test
```

### Update Dependencies

```bash
# PHP dependencies
composer update

# Node.js dependencies
npm update
```

### Backup Tenant Data

```bash
php artisan tenant:backup {tenant_hash}
```

### Generate Reports

```bash
php artisan reports:generate-scheduled --frequency=monthly --report=all
```

## Troubleshooting

### Common Issues

**Issue**: "Class not found" errors
- **Solution**: Run `composer dump-autoload`

**Issue**: "Vite manifest not found"
- **Solution**: Run `npm run build` or `npm run dev`

**Issue**: "Database connection failed"
- **Solution**: Check `.env` database credentials and ensure MySQL is running

**Issue**: "Email not sending"
- **Solution**: Check SMTP credentials in `.env` and verify `php artisan config:clear` was run

**Issue**: "500 Server Error"
- **Solution**: Check `storage/logs/laravel.log` for detailed error messages

**Issue**: "Permission denied" on storage
- **Solution**: Run `chmod -R 775 storage bootstrap/cache`

---

## Support & Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Spatie Permission**: https://spatie.be/docs/laravel-permission
- **M-Pesa Daraja API**: https://developer.safaricom.co.ke/
- **Tailwind CSS**: https://tailwindcss.com/docs

---

**End of Documentation**

*This document is maintained as a living document and should be updated after every significant change to the codebase.*
