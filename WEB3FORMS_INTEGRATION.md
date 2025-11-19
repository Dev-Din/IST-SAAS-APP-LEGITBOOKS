# Web3Forms Contact Form Integration

## Overview

The LegitBooks contact form (`/contact`) is integrated with Web3Forms for form submission handling. All communication with Web3Forms and email sending is done server-side only. No IP addresses or user agent information are stored.

## Setup Instructions

### 1. Environment Variables

Add the following to your `.env` file:

```env
# Web3Forms Contact Form Integration
WEB3FORMS_API_KEY=ba71d21d-27e1-4cce-945d-b6be08729209
```

**Note:** The internal notification email will be sent to the address configured in `MAIL_FROM_ADDRESS` in your `.env` file.

### 2. Run Migration

The `contact_submissions` table migration has been created and should be run:

```bash
php artisan migrate
```

### 3. Verify Configuration

Ensure your mail configuration is set up correctly in `.env`:

```env
MAIL_MAILER=mailgun  # or your preferred mailer
MAIL_FROM_ADDRESS=noreply@legitbooks.com
MAIL_FROM_NAME="LegitBooks"
```

## Database Schema

The `contact_submissions` table stores all form submissions with the following columns:

- `id` - Primary key
- `name` - Required, string(255)
- `email` - Required, string(255)
- `phone` - Optional, string(255), nullable
- `company` - Optional, string(255), nullable
- `message` - Required, text
- `web3forms_status` - Status from Web3Forms API: 'success', 'failed', or null
- `mail_status` - Email sending status: 'sent', 'failed', or null
- `created_at` - Timestamp
- `updated_at` - Timestamp

**Important:** No IP addresses or user agent information are stored.

## Form Submission Flow

1. **Validation**: Server-side validation of all fields
2. **Database Storage**: Submission saved to `contact_submissions` table
3. **Web3Forms API**: Server-side POST to `https://api.web3forms.com/submit`
4. **Email Notification**: Internal notification email sent using banner template
5. **Status Tracking**: Both API and email statuses are stored
6. **User Response**: Redirect with success message or JSON response for AJAX

## Email Template

The notification email uses a banner-style HTML template located at:
- HTML: `resources/views/emails/contact/banner.blade.php`
- Plain Text: `resources/views/emails/contact/banner_plain.blade.php`

The email includes:
- Submission timestamp (DD/MM/YYYY HH:MM:SS format)
- All form fields (name, email, company, phone, message)
- CTA button linking to `http://localhost:8000/`

## API Integration Details

### Web3Forms Request

**Endpoint:** `https://api.web3forms.com/submit`  
**Method:** POST  
**Timeout:** 10 seconds

**Payload:**
```json
{
  "access_key": "ba71d21d-27e1-4cce-945d-b6be08729209",
  "name": "User Name",
  "email": "user@example.com",
  "company": "Company Name",
  "phone": "+1234567890",
  "message": "Message content",
  "subject": "Contact Form Submission from User Name",
  "from_name": "User Name"
}
```

### Error Handling

- If Web3Forms API fails, the submission is still saved with `web3forms_status = 'failed'`
- If email sending fails, the submission is still saved with `mail_status = 'failed'`
- User always receives a success message regardless of backend failures
- All errors are logged for debugging

## Testing

Run the test suite:

```bash
php artisan test --filter ContactFormTest
```

Tests cover:
- Form validation
- Database storage
- Web3Forms API integration (mocked)
- Email sending (Mail fake)
- Redirect with success message
- JSON response for AJAX requests
- Failure handling

## Files Created/Modified

### New Files
- `database/migrations/2025_11_19_161921_create_contact_submissions_table.php`
- `app/Models/ContactSubmission.php`
- `app/Mail/ContactNotification.php`
- `resources/views/emails/contact/banner.blade.php`
- `resources/views/emails/contact/banner_plain.blade.php`

### Modified Files
- `app/Http/Controllers/Marketing/ContactController.php`
- `resources/views/marketing/contact.blade.php` (added phone field)
- `tests/Feature/Marketing/ContactFormTest.php`
- `.env.example` (added new environment variables)

## Security Notes

- ✅ All form processing is server-side only
- ✅ No IP addresses or user agent stored
- ✅ API key stored in environment variables only
- ✅ Input validation on all fields
- ✅ SQL injection protection via Eloquent ORM
- ✅ CSRF protection via Laravel middleware

## Troubleshooting

### Web3Forms Not Working

1. Check `WEB3FORMS_API_KEY` is set in `.env`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify API key is valid at https://web3forms.com

### Email Not Sending

1. Check `MAIL_FROM_ADDRESS` is set in `.env` (this is used as the notification recipient)
2. Verify mail configuration in `config/mail.php`
3. Check mail logs: `storage/logs/laravel.log`
4. Test mail configuration: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('test@example.com')->subject('test'));`

### Database Issues

1. Ensure migration ran: `php artisan migrate:status`
2. Check table exists: `php artisan tinker` → `Schema::hasTable('contact_submissions')`
3. Verify model: `php artisan tinker` → `App\Models\ContactSubmission::count()`

## Support

For issues or questions, check:
- Laravel logs: `storage/logs/laravel.log`
- Web3Forms dashboard: https://web3forms.com
- Application database: Check `contact_submissions` table for submission records

