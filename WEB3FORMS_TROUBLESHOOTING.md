# Web3Forms Troubleshooting Guide

## Issue: Emails Not Being Received

If you're not receiving emails from Web3Forms, follow these steps:

### 1. Verify API Key is Set

**Check your `.env` file:**
```bash
grep WEB3FORMS_API_KEY .env
```

Should show:
```
WEB3FORMS_API_KEY=ba71d21d-27e1-4cce-945d-b6be08729209
```

**If not set:**
1. Add `WEB3FORMS_API_KEY=ba71d21d-27e1-4cce-945d-b6be08729209` to your `.env` file
2. Clear config cache: `php artisan config:clear`
3. Restart your server

### 2. Check Laravel Logs

**View recent Web3Forms API responses:**
```bash
tail -n 100 storage/logs/laravel.log | grep -i "web3forms"
```

**Look for:**
- `Web3Forms API Response` - Shows the full API response
- `Web3Forms submission successful` - Confirms successful submission
- `Web3Forms API HTTP error` - Shows what went wrong
- `Web3Forms API key not configured` - API key missing

### 3. Verify Web3Forms Dashboard

1. Go to https://web3forms.com
2. Log in with your account
3. Check your access key: `ba71d21d-27e1-4cce-945d-b6be08729209`
4. Verify the email address is correctly configured
5. Check if there are any domain restrictions enabled

### 4. Check Email Configuration

**According to [Web3Forms documentation](https://docs.web3forms.com/getting-started/troubleshooting):**

- **Check Spam/Junk Folders**: Emails might be filtered
- **Email Suppression List**: If your email bounced previously, it might be suppressed. Contact Web3Forms support
- **Email Address**: Ensure the email in your Web3Forms dashboard is correct and active

### 5. Test the API Directly

**Test with curl:**
```bash
curl -X POST https://api.web3forms.com/submit \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "access_key=ba71d21d-27e1-4cce-945d-b6be08729209" \
  -d "name=Test User" \
  -d "email=test@example.com" \
  -d "message=Test message"
```

**Expected response:**
```json
{
  "success": true,
  "message": "Form submitted successfully",
  "message_id": "..."
}
```

### 6. Check Domain Restrictions

If you have domain restrictions enabled in Web3Forms:
- Ensure `localhost` is allowed for local development
- Ensure your production domain is whitelisted
- Contact Web3Forms support if needed

### 7. Review Recent Submissions

**Check database for submission status:**
```bash
php artisan tinker
```

```php
use App\Models\ContactSubmission;

// Check recent submissions
ContactSubmission::latest()->take(5)->get(['id', 'name', 'email', 'web3forms_status', 'mail_status', 'created_at']);

// Check failed submissions
ContactSubmission::where('web3forms_status', 'failed')->latest()->get();
```

### 8. Common Issues

**Issue: API key not found**
- **Solution**: Add `WEB3FORMS_API_KEY` to `.env` and run `php artisan config:clear`

**Issue: 403 Forbidden**
- **Solution**: Your IP might be blocked. Contact Web3Forms support

**Issue: Success but no email**
- **Solution**: 
  1. Check spam folder
  2. Verify email in Web3Forms dashboard
  3. Check if email is on suppression list
  4. Try a different email address

**Issue: Timeout errors**
- **Solution**: Increase timeout in controller (currently 15 seconds)

### 9. Enable Debug Logging

The controller now logs all Web3Forms API responses. Check:
```bash
tail -f storage/logs/laravel.log
```

Then submit a form and watch for:
- `Web3Forms API Response` - Full response details
- Status codes and error messages

### 10. Contact Web3Forms Support

If issues persist:
1. Visit https://web3forms.com
2. Check their [troubleshooting guide](https://docs.web3forms.com/getting-started/troubleshooting)
3. Contact their support with:
   - Your access key (first 8 chars): `ba71d21d`
   - Error messages from logs
   - Domain you're testing from

## Testing Checklist

- [ ] API key is set in `.env`
- [ ] Config cache cleared (`php artisan config:clear`)
- [ ] Server restarted
- [ ] Web3Forms dashboard shows correct email
- [ ] No domain restrictions blocking localhost
- [ ] Laravel logs show API responses
- [ ] Spam folder checked
- [ ] Test submission made and logged

## Additional Resources

- [Web3Forms Documentation](https://docs.web3forms.com/)
- [Web3Forms Troubleshooting](https://docs.web3forms.com/getting-started/troubleshooting)
- [Web3Forms API Reference](https://docs.web3forms.com/getting-started/api-reference)

