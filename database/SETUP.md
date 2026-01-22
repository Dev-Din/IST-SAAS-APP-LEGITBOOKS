# Database Setup Guide

This guide provides instructions for setting up the LegitBooks database using the generated SQL file or Laravel migrations.

## Quick Setup (SQL Import)

The fastest way to set up the database is to import the complete `setup.sql` file:

### For MySQL/MariaDB:

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE legitbooks;"

# Import SQL file
mysql -u root -p legitbooks < database/setup.sql
```

### For XAMPP Users:

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `legitbooks`
3. Click on the database
4. Go to "Import" tab
5. Choose file: `database/setup.sql`
6. Click "Go"

### For MySQL Workbench:

1. Open MySQL Workbench
2. Connect to your MySQL server
3. Create a new schema named `legitbooks`
4. Right-click on the schema → "Table Data Import Wizard"
5. Select `database/setup.sql`
6. Follow the import wizard

## Alternative Setup (Laravel Migrations)

If you prefer to use Laravel migrations:

```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

## What's Included in setup.sql

### Schema Section

The SQL file includes all database tables:

**Laravel Core Tables:**
- `users`, `password_reset_tokens`, `sessions`
- `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`

**Spatie Permission Tables:**
- `permissions`, `roles`
- `model_has_permissions`, `model_has_roles`, `role_has_permissions`

**Platform Tables:**
- `admins` - Platform administrators
- `tenants` - Tenant organizations
- `platform_settings` - Global settings
- `platform_audit_logs` - Platform audit trail
- `platform_csv_templates` - CSV import templates
- `admin_invitations` - Admin invitation system

**Tenant-Scoped Tables:**
- `subscriptions` - Tenant subscription plans
- `users` - Tenant users (with tenant_id)
- `contacts` - Customers and suppliers
- `chart_of_accounts` - Accounting chart
- `accounts` - Bank, cash, M-Pesa accounts
- `products` - Products/services
- `invoices` - Customer invoices
- `invoice_line_items` - Invoice line items
- `invoice_counters` - Year-based invoice numbering
- `payments` - Payment records
- `payment_allocations` - Payment-to-invoice allocations
- `journal_entries` - Double-entry journal entries
- `journal_lines` - Journal entry lines
- `bills` - Supplier bills
- `bill_line_items` - Bill line items
- `bill_counters` - Year-based bill numbering
- `payment_methods` - Stored payment methods
- `user_invitations` - User invitation system
- `recurring_templates` - Recurring invoice templates
- `fixed_assets` - Fixed asset tracking
- `attachments` - File attachments
- `audit_logs` - Tenant audit trail
- `csv_import_jobs` - CSV import tracking
- `contact_submissions` - Marketing contact form

### Seed Data Section

**Platform Settings:**
- `branding_mode` = 'A'
- `mpesa_environment` = 'sandbox'

**Spatie Roles:**
- `owner` (admin guard)
- `subadmin` (admin guard)

**Super Admin:**
- Email: `admin@legitbooks.com`
- Password: `password` (CHANGE IMMEDIATELY!)
- Role: `owner`

**Demo Tenant (Optional):**
- Commented out in SQL file
- Uncomment to include demo tenant with test data

## Default Credentials

After importing the SQL file:

**Platform Admin:**
- Email: `admin@legitbooks.com`
- Password: `password`
- ⚠️ **IMPORTANT:** Change this password immediately after first login!

## Verifying Setup

After importing, verify the setup:

```sql
-- Check tables
SHOW TABLES;

-- Check platform settings
SELECT * FROM platform_settings;

-- Check admin user
SELECT id, name, email, role FROM admins;

-- Check Spatie roles
SELECT * FROM roles WHERE guard_name = 'admin';
```

## Regenerating SQL File

If you need to regenerate the SQL file:

```bash
php artisan db:generate-sql --all
```

This will create:
- `database/schema.sql` - Schema only
- `database/seed_data.sql` - Seed data only
- `database/setup.sql` - Combined file

## Database Structure Overview

### Key Relationships

**Tenant (Central Entity):**
- Has many: users, invoices, payments, contacts, products, chart_of_accounts, journal_entries, bills
- Has one: subscription

**Invoice Flow:**
- Invoice → InvoiceLineItems (1:N)
- Invoice → PaymentAllocations (1:N)
- Invoice → JournalEntry (1:1, polymorphic)

**Payment Flow:**
- Payment → PaymentAllocations (1:N)
- Payment → JournalEntry (1:1, polymorphic)

**Accounting Flow:**
- JournalEntry → JournalLines (1:N)
- ChartOfAccount → Accounts (1:N)
- ChartOfAccount → JournalLines (1:N)

## Troubleshooting

### Foreign Key Errors

If you encounter foreign key errors during import:

```sql
SET FOREIGN_KEY_CHECKS=0;
-- Import SQL
SET FOREIGN_KEY_CHECKS=1;
```

The `setup.sql` file already includes these statements.

### Table Already Exists

If tables already exist:

```bash
# Drop and recreate
mysql -u root -p -e "DROP DATABASE IF EXISTS legitbooks; CREATE DATABASE legitbooks;"
mysql -u root -p legitbooks < database/setup.sql
```

### Missing Tables After Import

If some tables are missing:

1. Check MySQL error log
2. Verify SQL file is complete
3. Try importing schema.sql and seed_data.sql separately

### Migration Conflicts

If you've imported SQL and then run migrations:

```bash
# Mark migrations as run
php artisan migrate:mark-run
```

This tells Laravel that migrations have already been applied.

## Next Steps

After database setup:

1. Update `.env` with database credentials
2. Run `php artisan config:clear`
3. Test login with default admin credentials
4. Change default password
5. Create your first tenant

---

**Last Updated:** 2026-01-18
