# Web3Forms Dashboard Configuration Checklist

Based on your Web3Forms dashboard showing the API key `ba71d21d-27e1-4cce-945d-b6be08729209` for LegitBooks:

## ✅ What's Confirmed
- API Key: `ba71d21d-27e1-4cce-945d-b6be08729209` ✓
- Status: Active ✓
- Created: Nov 19, 2025 ✓

## 🔍 Critical Settings to Verify

### 1. Click "Manage" and Check:

**Email Configuration:**
- [ ] Verify the recipient email address is set correctly
- [ ] Ensure the email is active and can receive emails
- [ ] Check if email is on suppression list (if emails bounced previously)

**Domain Restrictions:**
- [ ] If "Restrict to Domain" is enabled, ensure:
  - `localhost` is allowed for local development
  - Your production domain is whitelisted
  - No restrictions blocking your current domain

**Form Settings:**
- [ ] Check if "Auto-responder" is enabled (optional)
- [ ] Verify "Spam Protection" settings
- [ ] Check rate limiting settings

### 2. Why Submissions Show "0"

The "0" count means:
- Either no submissions have been received yet
- OR submissions are failing before reaching Web3Forms

**To diagnose:**
1. Submit a test form from your contact page
2. Check Laravel logs: `tail -f storage/logs/laravel.log | grep -i web3forms`
3. Look for:
   - `Web3Forms API Response` - Shows if API call was made
   - `Web3Forms submission successful` - Confirms success
   - Any error messages

### 3. Test the Integration

**Step 1: Verify API Key in Laravel**
```bash
php artisan tinker
```
```php
echo env('WEB3FORMS_API_KEY');
// Should output: ba71d21d-27e1-4cce-945d-b6be08729209
```

**Step 2: Submit Test Form**
1. Go to `/contact` on your site
2. Fill out and submit the form
3. Check Laravel logs immediately

**Step 3: Check Web3Forms Dashboard**
1. Refresh the dashboard
2. The count should increment if submission was successful
3. Check the email inbox (and spam folder)

### 4. Common Issues & Solutions

**Issue: Submissions not reaching Web3Forms**
- **Check**: Laravel logs for API errors
- **Solution**: Verify API key in `.env` matches dashboard

**Issue: API returns success but no email**
- **Check**: Email address in Web3Forms dashboard "Manage" section
- **Check**: Spam folder
- **Solution**: Verify email is correct and not suppressed

**Issue: 403 Forbidden errors**
- **Check**: Domain restrictions in dashboard
- **Check**: IP blocking
- **Solution**: Whitelist your domain/IP in dashboard

**Issue: Timeout errors**
- **Check**: Network connectivity
- **Solution**: API timeout is set to 15 seconds (should be sufficient)

### 5. Next Steps

1. **Click "Manage"** in your Web3Forms dashboard
2. **Verify email address** is set correctly
3. **Check domain restrictions** - ensure localhost/production domain is allowed
4. **Submit a test form** from `/contact`
5. **Monitor Laravel logs** for API responses
6. **Check email inbox** (including spam)

### 6. Debugging Commands

**Check recent submissions in database:**
```bash
php artisan tinker
```
```php
use App\Models\ContactSubmission;
ContactSubmission::latest()->take(5)->get(['id', 'name', 'email', 'web3forms_status', 'created_at']);
```

**View Web3Forms API logs:**
```bash
tail -n 100 storage/logs/laravel.log | grep -A 5 "Web3Forms"
```

**Test API key directly:**
```bash
curl -X POST https://api.web3forms.com/submit \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "access_key=ba71d21d-27e1-4cce-945d-b6be08729209" \
  -d "name=Test User" \
  -d "email=your-email@example.com" \
  -d "message=Test message"
```

## 📝 Important Notes

- The API key in your dashboard matches what we configured: ✓
- Status is "Active" which is correct: ✓
- The "0" submissions count will increment once a successful submission is made
- All API calls are logged in Laravel for debugging

If submissions still show "0" after testing, check the Laravel logs to see what Web3Forms API is returning.

