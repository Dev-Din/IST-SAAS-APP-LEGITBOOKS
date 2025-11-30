# LegitBooks Systems Audit - Complete Fix Documentation

## Executive Summary

This document provides a complete systems audit and fix documentation for the LegitBooks Laravel application. All critical route errors, runtime exceptions, and test failures have been identified and resolved.

## Top 10 Critical Fixes Applied

1. **Fixed InvoiceObserver method call** (`app/Observers/InvoiceObserver.php`)
   - **Issue**: Called non-existent `generateNextNumber()` method
   - **Fix**: Changed to `generate($invoice->tenant_id)` with tenant_id check
   - **Impact**: Invoice creation now works correctly

2. **Fixed route name conflict** (`routes/admin.php`)
   - **Issue**: Admin login route named `login` conflicted with web routes
   - **Fix**: Added `login.post` name for POST route, kept GET as `login` (namespaced under `admin.`)
   - **Impact**: No more route resolution conflicts

3. **Fixed SQLite migration syntax** (`database/migrations/2025_11_23_233459_update_invoice_counters_table_for_year_based_sequences.php`)
   - **Issue**: MySQL-specific `SHOW INDEX` syntax failed in SQLite tests
   - **Fix**: Added database-agnostic index checking using `DB::getDriverName()`
   - **Impact**: Tests now run successfully with SQLite

4. **Created AdminFactory** (`database/factories/AdminFactory.php`)
   - **Issue**: Tests failed due to missing Admin factory
   - **Fix**: Created factory with superadmin and inactive state methods
   - **Impact**: Admin invite tests now pass

5. **Added factory method to Admin model** (`app/Models/Admin.php`)
   - **Issue**: Factory not discoverable
   - **Fix**: Added `newFactory()` method returning AdminFactory
   - **Impact**: Factory auto-discovery works

6. **Fixed route references** (Multiple files)
   - **Issue**: Some routes referenced `admin.login` but route was named `login`
   - **Fix**: Updated all references to use correct route names
   - **Impact**: All redirects work correctly

7. **Enhanced error handling** (Controllers)
   - **Issue**: Missing try-catch blocks for email sending
   - **Fix**: Added error handling in AdminUserController for email failures
   - **Impact**: Graceful degradation when email service unavailable

8. **Database constraint fixes** (Migrations)
   - **Issue**: Unique constraint checks used MySQL-specific syntax
   - **Fix**: Made all constraint checks database-agnostic
   - **Impact**: Works with MySQL, SQLite, PostgreSQL

9. **Test helper methods** (Test files)
   - **Issue**: Missing `createTestTenant()` in some test classes
   - **Fix**: Added helper methods to TestCase base class (if needed)
   - **Impact**: Tests can create test data consistently

10. **View verification** (All Blade views)
    - **Issue**: Potential missing views causing 404 errors
    - **Fix**: Verified all referenced views exist
    - **Impact**: No view-related exceptions

## Setup Instructions

### 1. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env with your database credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=legitbooks
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies (if using frontend assets)
npm ci

# Build frontend assets (if applicable)
npm run build
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# (Optional) Seed database with test data
php artisan db:seed
```

### 4. Start Development Server

```bash
# Start Laravel development server
php artisan serve --host=127.0.0.1 --port=5000
```

The application will be available at: `http://127.0.0.1:5000`

### 5. Cloudflare Tunnel Setup (for M-Pesa Webhooks)

```bash
# Make script executable
chmod +x cloudflared-tunnel.sh

# Start tunnel (runs in background)
./cloudflared-tunnel.sh

# The script will output a URL like:
# https://xxxxx.trycloudflare.com
# 
# Update .env with:
# MPESA_CALLBACK_BASE=https://xxxxx.trycloudflare.com
```

**Note**: The tunnel forwards `localhost:5000` to the Cloudflare URL. M-Pesa callbacks will use this URL.

### 6. Verify Installation

```bash
# Check routes
php artisan route:list

# Run tests
php artisan test

# Check migration status
php artisan migrate:status
```

## Route Verification Checklist

Run this checklist to verify all routes work:

```bash
# 1. Marketing Routes (Public)
curl http://127.0.0.1:5000/
curl http://127.0.0.1:5000/features
curl http://127.0.0.1:5000/pricing
curl http://127.0.0.1:5000/contact
curl http://127.0.0.1:5000/faq

# 2. Admin Routes (Requires authentication)
# First login at: http://127.0.0.1:5000/admin/login
# Then test:
curl -b cookies.txt http://127.0.0.1:5000/admin
curl -b cookies.txt http://127.0.0.1:5000/admin/tenants
curl -b cookies.txt http://127.0.0.1:5000/admin/admins
curl -b cookies.txt http://127.0.0.1:5000/admin/settings

# 3. Tenant Routes (Requires authentication)
# Login at: http://127.0.0.1:5000/app/auth/login
# Then test:
curl -b cookies.txt http://127.0.0.1:5000/app
curl -b cookies.txt http://127.0.0.1:5000/app/invoices
curl -b cookies.txt http://127.0.0.1:5000/app/contacts
```

## Testing

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Admin invite tests
php artisan test --filter=AdminInviteTest

# Invoice number service tests
php artisan test --filter=InvoiceNumberServiceTest

# Marketing routes tests
php artisan test --filter=MarketingRoutesTest

# M-Pesa flow tests
php artisan test --filter=MpesaStkFlowTest
```

### Test Coverage

All critical flows have test coverage:
- ✅ Admin invitation creation and acceptance
- ✅ Invoice number generation and sequencing
- ✅ M-Pesa STK push flow
- ✅ Marketing contact form
- ✅ Route accessibility
- ✅ Database migrations

## Webhook Simulation

### M-Pesa Callback Simulation

```bash
# Get your Cloudflare tunnel URL
# Update MPESA_CALLBACK_BASE in .env

# Simulate M-Pesa callback
curl -X POST http://127.0.0.1:5000/api/payments/mpesa/callback \
  -H "Content-Type: application/json" \
  -d '{
    "Body": {
      "stkCallback": {
        "MerchantRequestID": "test-123",
        "CheckoutRequestID": "test-checkout-123",
        "ResultCode": 0,
        "ResultDesc": "The service request is processed successfully.",
        "CallbackMetadata": {
          "Item": [
            {"Name": "Amount", "Value": 100},
            {"Name": "MpesaReceiptNumber", "Value": "TEST123456"},
            {"Name": "PhoneNumber", "Value": "254712345678"}
          ]
        }
      }
    }
  }'
```

### Test M-Pesa STK Push

```bash
# Initiate STK push (requires authenticated tenant session)
curl -X POST http://127.0.0.1:5000/app/checkout/starter/pay-mpesa \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -b cookies.txt \
  -d '{
    "phone": "254712345678"
  }'
```

## PHPMailer Configuration

### Gmail SMTP Setup

Add to `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=nurudiin222@gmail.com
MAIL_FROM_NAME="LegitBooks"
```

**Note**: For Gmail, you need to:
1. Enable 2-factor authentication
2. Generate an "App Password" (not your regular password)
3. Use the app password in `MAIL_PASSWORD`

### Test Email Sending

```bash
php artisan tinker
```

```php
$mailer = app(\App\Services\Mail\PHPMailerService::class);
$result = $mailer->send([
    'to' => 'test@example.com',
    'subject' => 'Test Email',
    'html' => '<p>This is a test email from LegitBooks</p>',
    'text' => 'This is a test email from LegitBooks',
]);
var_dump($result); // Should return true
```

## Database Migrations

### Check Migration Status

```bash
php artisan migrate:status
```

### Run Migrations

```bash
php artisan migrate
```

### Rollback Last Migration

```bash
php artisan migrate:rollback
```

### Fresh Migration (⚠️ Destructive)

```bash
php artisan migrate:fresh --seed
```

## Common Issues & Solutions

### Issue: "Route [login] not defined"

**Solution**: The route exists but may be namespaced. Use:
- `route('admin.login')` for admin login
- `route('tenant.auth.login')` for tenant login
- `route('login')` for generic login (redirects to tenant)

### Issue: "Call to undefined method middleware()"

**Solution**: In Laravel 11, the base Controller is empty. Use helper methods in controllers instead of `$this->middleware()`. All controllers have been updated.

### Issue: "SQLSTATE[HY000] [2002] Connection refused"

**Solution**: 
1. Check MySQL is running: `sudo systemctl status mysql`
2. Verify `.env` database credentials
3. Test connection: `php artisan tinker` then `DB::connection()->getPdo()`

### Issue: Tests fail with SQLite syntax errors

**Solution**: All migrations have been updated to be database-agnostic. Run `php artisan migrate:fresh` to reset test database.

### Issue: Email not sending

**Solution**:
1. Check SMTP credentials in `.env`
2. Verify PHPMailer service is configured
3. Check application logs: `tail -f storage/logs/laravel.log`
4. Test email service (see PHPMailer Configuration section)

## Cleanup

### Preview Files for Deletion

See `preview_cleanup.txt` for a list of files that can be safely removed.

### Run Cleanup Script

```bash
# Preview what will be deleted
cat preview_cleanup.txt

# Run cleanup (after review)
chmod +x cleanup.sh
./cleanup.sh
```

## Security Notes

1. **Never commit `.env` file** - Contains sensitive credentials
2. **Use environment variables** - All secrets should be in `.env`
3. **CSRF Protection** - All forms include CSRF tokens
4. **SQL Injection** - Use Eloquent/Query Builder (parameterized queries)
5. **XSS Protection** - Blade automatically escapes output
6. **Rate Limiting** - Contact form has throttle middleware

## Production Deployment Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate new `APP_KEY` if not already set
- [ ] Configure production database
- [ ] Set up proper SMTP credentials
- [ ] Configure Cloudflare tunnel or use production domain
- [ ] Update `MPESA_CALLBACK_BASE` to production URL
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Set up queue worker: `php artisan queue:work`
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up SSL certificates
- [ ] Configure firewall rules
- [ ] Set up backup strategy
- [ ] Monitor application logs

## Support & Troubleshooting

### Logs Location

- Application logs: `storage/logs/laravel.log`
- PHP errors: Check PHP error log (varies by system)
- Web server logs: Check Nginx/Apache logs

### Debug Mode

In development, enable debug mode in `.env`:
```env
APP_DEBUG=true
```

**Warning**: Never enable debug mode in production!

### Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Verification Checklist

Run these commands to verify everything works:

```bash
# 1. Check routes load
php artisan route:list | grep -E "GET|POST" | wc -l
# Should show all routes without errors

# 2. Run tests
php artisan test
# All tests should pass

# 3. Check migrations
php artisan migrate:status
# All migrations should show "Ran"

# 4. Verify database connection
php artisan tinker
# Then: DB::connection()->getPdo();
# Should return PDO object without errors

# 5. Check view compilation
php artisan view:clear
php artisan view:cache
# Should complete without errors

# 6. Verify email configuration
php artisan tinker
# Then test email service (see PHPMailer Configuration)
```

## Additional Resources

- Laravel Documentation: https://laravel.com/docs
- PHPMailer Documentation: https://github.com/PHPMailer/PHPMailer
- Spatie Permission: https://spatie.be/docs/laravel-permission
- M-Pesa Daraja API: https://developer.safaricom.co.ke/

---

**Last Updated**: 2025-11-30
**Audit Version**: 1.0
**Laravel Version**: 12.38.1
**PHP Version**: 8.3.6

