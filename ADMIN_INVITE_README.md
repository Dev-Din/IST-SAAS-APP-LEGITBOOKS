# Admin Invite System - Implementation Guide

## Overview

This implementation provides a complete admin invitation system with role/permission matrix, email notifications via PHPMailer, and comprehensive audit logging.

## Features

- **Admin Invitation Flow**: Create, send, and accept admin invitations
- **Permission Matrix**: Visual UI for assigning granular permissions per resource
- **Email Notifications**: HTML and plain-text invite emails with temporary passwords
- **Audit Logging**: Complete audit trail of all invitation actions
- **Security**: Token-based invitations with 14-day expiry, duplicate prevention
- **PHPUnit Tests**: Comprehensive test coverage

## Installation & Setup

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the `admin_invitations` table.

### 2. Seed Roles (if using Spatie Permission)

If you haven't already, ensure the `superadmin` role exists:

```bash
php artisan tinker
```

```php
\Spatie\Permission\Models\Role::firstOrCreate([
    'name' => 'superadmin',
    'guard_name' => 'admin',
]);
```

### 3. Configure Email (PHPMailer)

Ensure your `.env` has SMTP settings configured:

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

### 4. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Usage

### Creating an Admin Invitation

1. **Navigate to Admin Invite Page**
   - URL: `/admin/admins/create`
   - Requires: Superadmin role

2. **Fill in Basic Information**
   - First Name, Last Name, Email (required)
   - Role Name (optional, e.g., "Support Admin")

3. **Select Permissions**
   - Use the permission matrix to select View/Create/Update/Delete for each resource
   - Resources include: Tenants, Admins, Users, Invoices, Payments, Billing, Reports, Settings

4. **Submit**
   - System generates secure token (60 chars) and temporary password (16 chars)
   - Email is sent via PHPMailer with invite link and temp password
   - Invitation expires in 14 days

### Accepting an Invitation

1. **Click Invite Link** (from email)
   - URL format: `/admin/invite/accept/{token}`
   - Public route (no auth required)

2. **Set Password**
   - Enter new password (minimum 8 characters)
   - Confirm password

3. **Account Created**
   - Admin account is created with assigned permissions
   - User is redirected to login
   - Password reset flag is set for first login

### Resending Invitations

- View pending invitations on the create page
- Click "Resend Invite" for any pending invitation
- System regenerates token (if expired) and sends new email

## Testing

### Run All Admin Invite Tests

```bash
php artisan test --filter=AdminInviteTest
```

### Individual Test Methods

```bash
# Test invite creation and email sending
php artisan test --filter=test_superadmin_can_create_invite_and_email_sent

# Test invite acceptance flow
php artisan test --filter=test_invite_accept_creates_admin_and_forces_password_reset

# Test duplicate prevention
php artisan test --filter=test_duplicate_invite_is_prevented

# Test resend functionality
php artisan test --filter=test_resend_invite_regenerates_token_and_sends_email

# Test expired invitation handling
php artisan test --filter=test_expired_invitation_cannot_be_accepted

# Test authorization
php artisan test --filter=test_non_superadmin_cannot_create_invite
```

## Simulating Invite Flow

### 1. Create Invitation via Tinker

```bash
php artisan tinker
```

```php
use App\Models\Admin;
use App\Models\AdminInvitation;
use Illuminate\Support\Facades\Hash;

$superadmin = Admin::where('role', 'superadmin')->first();
$invitation = AdminInvitation::create([
    'inviter_admin_id' => $superadmin->id,
    'first_name' => 'Test',
    'last_name' => 'Admin',
    'email' => 'test@example.com',
    'role_name' => 'Test Admin',
    'permissions' => ['tenants.view', 'tenants.create'],
    'token' => AdminInvitation::generateToken(),
    'temp_password_hash' => Hash::make('temp-pass-123'),
    'expires_at' => now()->addDays(14),
    'status' => 'pending',
]);

echo "Invite URL: " . route('admin.invite.accept', $invitation->token) . "\n";
echo "Token: {$invitation->token}\n";
```

### 2. Accept Invitation

Visit the generated URL or use curl:

```bash
curl -X POST http://localhost:8000/admin/invite/accept/{token} \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "password=newpassword123&password_confirmation=newpassword123"
```

## Inspecting Audit Logs

### View Audit Logs via Tinker

```bash
php artisan tinker
```

```php
use App\Models\PlatformAuditLog;

// View all admin invite audit logs
$logs = PlatformAuditLog::where('action', 'like', 'admin.invite.%')
    ->orderByDesc('created_at')
    ->get();

foreach ($logs as $log) {
    echo "Action: {$log->action}\n";
    echo "Admin ID: {$log->admin_id}\n";
    echo "Details: " . json_encode($log->details, JSON_PRETTY_PRINT) . "\n";
    echo "---\n";
}
```

### View via Database

```sql
SELECT 
    id,
    admin_id,
    action,
    target_type,
    target_id,
    details,
    created_at
FROM platform_audit_logs
WHERE action LIKE 'admin.invite.%'
ORDER BY created_at DESC;
```

## Permission Format

Permissions follow the pattern: `{resource}.{action}`

Examples:
- `tenants.view` - View tenants
- `tenants.create` - Create tenants
- `tenants.update` - Update tenants
- `tenants.delete` - Delete tenants
- `invoices.view` - View invoices
- `users.create` - Create users

## Security Features

1. **Token Security**: 60-character cryptographically random tokens
2. **Password Security**: Temporary passwords are hashed before storage
3. **Expiry**: Invitations expire after 14 days
4. **Duplicate Prevention**: Active invitations for same email are prevented
5. **Authorization**: Only superadmins can create invitations
6. **CSRF Protection**: All forms include CSRF tokens
7. **Validation**: Server-side validation on all inputs

## File Structure

```
app/
├── Helpers/
│   └── AuditLog.php                    # Audit logging helper
├── Http/
│   ├── Controllers/Admin/
│   │   ├── AdminInvitationController.php
│   │   └── AdminUserController.php     # Updated with invite methods
│   └── Requests/Admin/
│       └── StoreAdminInvitationRequest.php
├── Models/
│   ├── Admin.php                        # Updated with permission helpers
│   └── AdminInvitation.php             # New model
└── Services/
    └── MailService.php                 # Email service wrapper

database/
└── migrations/
    └── 2025_11_30_223522_create_admin_invitations_table.php

resources/
└── views/
    ├── admin/
    │   ├── admins/
    │   │   └── create.blade.php        # Updated with permission matrix
    │   └── invitations/
    │       ├── accept.blade.php
    │       └── expired.blade.php
    ├── components/
    │   └── permissions/
    │       └── matrix.blade.php        # Permission matrix component
    └── emails/
        └── admin/
            ├── invite.blade.php         # HTML email
            └── invite-text.blade.php    # Plain text email

tests/
└── Feature/Admin/
    └── AdminInviteTest.php             # Comprehensive tests
```

## Troubleshooting

### Email Not Sending

1. Check SMTP configuration in `.env`
2. Verify PHPMailer credentials
3. Check application logs: `storage/logs/laravel.log`
4. Test PHPMailer connection:
   ```bash
   php artisan tinker
   ```
   ```php
   $mailer = app(\App\Services\Mail\PHPMailerService::class);
   $result = $mailer->send([
       'to' => 'test@example.com',
       'subject' => 'Test',
       'html' => '<p>Test email</p>',
   ]);
   ```

### Invitation Token Not Working

1. Check if invitation is expired: `expires_at < now()`
2. Verify token matches database
3. Check invitation status is 'pending'

### Permissions Not Assigned

1. Ensure Spatie Permission package is installed
2. Run permission migrations: `php artisan migrate`
3. Check admin has `hasPermission()` method working
4. Verify permissions array format: `['resource.action']`

## API Routes

```
GET  /admin/admins/create              - Show invite form (auth:admin, superadmin)
POST /admin/admins                     - Create invitation (auth:admin, superadmin)
POST /admin/admins/{id}/resend-invite  - Resend invitation (auth:admin, superadmin)
GET  /admin/invite/accept/{token}      - Show accept form (public)
POST /admin/invite/accept/{token}     - Accept invitation (public)
```

## Notes

- Temporary passwords are shown only in email (never stored in plain text)
- Invitations can be resent, which regenerates token if expired
- Only one pending invitation per email address is allowed
- All actions are logged in `platform_audit_logs` table
- Permission strings are stored as JSON array in `admin_invitations.permissions`

