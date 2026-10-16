# OTP Verification System Setup Guide
## Complete PHP/InfinityFree Implementation with PHPMailer + Mailtrap SMTP

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Database Setup](#database-setup)
3. [PHPMailer Installation](#phpmailer-installation)
4. [Mailtrap Configuration](#mailtrap-configuration)
5. [File Structure](#file-structure)
6. [Configuration](#configuration)
7. [Testing](#testing)
8. [Production Deployment](#production-deployment)
9. [Troubleshooting](#troubleshooting)

---

## System Overview

This OTP verification system provides:
- **Email Verification**: Verify user email after registration
- **Password Reset**: Secure password reset via OTP
- **Contact Form**: Send contact messages to admin
- **10-minute OTP expiry**: Codes expire after 10 minutes
- **Rate limiting**: Delete old unused OTPs before creating new ones
- **InfinityFree compatible**: No cron jobs, handles execution time limits

**Key Features:**
- 6-digit numeric OTP codes
- HTML email templates
- Countdown timer on frontend
- Resend OTP functionality
- Works without login for verification
- PHPMailer for reliable email delivery

---

## Database Setup

### 1. Create OTP Table

Run this SQL in your database:

```sql
-- OTP Table for email verification and password reset
CREATE TABLE IF NOT EXISTS otps (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_type ENUM('email_verification', 'password_reset', 'contact_verification') NOT NULL,
    user_id INT NULL,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_otp_code (otp_code),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Note:** This table is already included in your `db.sql` file.

### 2. Clean Up Expired OTPs (Optional)

For InfinityFree (no cron), you can add this to your login.php or other frequently accessed pages:

```php
// Clean up expired OTPs (run on page load)
require_once 'includes/otp_model.inc.php';
cleanupExpiredOTPs($pdo);
```

---

## PHPMailer Installation

### Option 1: Using Composer (Recommended)

1. Install Composer on your local machine
2. Navigate to your project directory:
   ```bash
   cd c:\Users\zwane\OneDrive\Documents\htdocs\Phambili-Ma-Africa
   ```
3. Run:
   ```bash
   composer require phpmailer/phpmailer
   ```
4. Upload the `vendor/` folder to InfinityFree via FTP

### Option 2: Manual Download

1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract the zip file
3. Upload the `PHPMailer/src/` folder to:
   ```
   vendor/phpmailer/phpmailer/src/
   ```
4. Required files:
   - `PHPMailer.php`
   - `SMTP.php`
   - `Exception.php`

### Option 3: Using InfinityFree File Manager

1. Go to InfinityFree File Manager
2. Navigate to your project folder
3. Create folder structure: `vendor/phpmailer/phpmailer/src/`
4. Download PHPMailer files locally
5. Upload the three required files to the `src/` folder

---

## Mailtrap Configuration

### 1. Sign Up for Mailtrap

1. Go to https://mailtrap.io/
2. Sign up for a free account (500 emails/month free)
3. Verify your email address

### 2. Get SMTP Credentials

1. Go to "Sending Domains" → "SMTP Settings"
2. Or go to "Inboxes" → "Integrations" → "SMTP"
3. Copy the credentials:
   - **Host**: `smtp.mailtrap.io`
   - **Port**: `2525` or `587`
   - **Username**: Your Mailtrap username
   - **Password**: Your Mailtrap password

### 3. Update Configuration

Open `includes/email_helper.inc.php` and update lines 24-27:

```php
// Mailtrap SMTP Configuration (Test Environment)
define('MAILTRAP_HOST', 'smtp.mailtrap.io');
define('MAILTRAP_PORT', '2525');
define('MAILTRAP_USERNAME', 'your_actual_mailtrap_username');
define('MAILTRAP_PASSWORD', 'your_actual_mailtrap_password');
```

---

## File Structure

```
Phambili-Ma-Africa/
├── api/
│   └── otp_actions.php              # OTP API endpoints
├── includes/
│   ├── config_session.inc.php       # Session configuration
│   ├── dbh.inc.php                  # Database connection
│   ├── email_helper.inc.php         # Email functions (PHPMailer)
│   ├── otp_model.inc.php            # OTP model functions
│   └── contact_handler.php          # Contact form handler
├── css/
│   ├── login.css                    # Login/registration styles
│   └── social-media.css             # Contact form styles
├── login.php                        # Registration with OTP
├── password_reset.php               # Password reset with OTP
├── contact.php                      # Contact form
├── vendor/                          # PHPMailer (after installation)
│   └── phpmailer/
│       └── phpmailer/
│           └── src/
│               ├── PHPMailer.php
│               ├── SMTP.php
│               └── Exception.php
└── db.sql                           # Database schema
```

---

## Configuration

### 1. Update Email Settings

In `includes/email_helper.inc.php`, update lines 30-35 for production:

```php
// For production, replace with your actual SMTP settings
define('SMTP_HOST', 'smtp.gmail.com');      // Your SMTP host
define('SMTP_PORT', '587');                  // Your SMTP port
define('SMTP_USERNAME', 'your_email@gmail.com');  // Your SMTP username
define('SMTP_PASSWORD', 'your_app_password');     // Your SMTP password
define('SMTP_FROM_EMAIL', 'noreply@phambili-ma-africa.co.za');
define('SMTP_FROM_NAME', 'Phambili Ma-Africa');
```

### 2. Update Contact Form Recipient

In `includes/email_helper.inc.php` line 185:

```php
$mail->addAddress('admin@phambili-ma-africa.co.za', 'Admin');
```

Change to your actual admin email.

### 3. Update OTP Expiry (Optional)

To change OTP expiry time, edit `includes/otp_model.inc.php` line 23:

```php
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
```

Change `+10 minutes` to your desired duration.

---

## Testing

### 1. Test Email Verification

1. Go to `login.php`
2. Click "Register"
3. Fill in registration details
4. Click "Register"
5. Check your Mailtrap inbox for the OTP email
6. Enter the OTP code
7. Verify account creation

### 2. Test Password Reset

1. Go to `password_reset.php`
2. Enter a registered email
3. Check Mailtrap for OTP
4. Enter OTP code
5. Set new password
6. Try logging in with new password

### 3. Test Contact Form

1. Go to `contact.php`
2. Fill in contact form
3. Submit
4. Check Mailtrap for admin notification

### 4. Check Error Logs

If emails aren't sending, check your PHP error logs:

```php
// Logs are written to:
// - Local: Check your PHP error_log file
// - InfinityFree: Check error_log in file manager
// - Or add this to see logs:
error_log("Test message");
```

---

## Production Deployment

### 1. Switch from Mailtrap to Production SMTP

In `includes/email_helper.inc.php`, change lines 65-70:

```php
// Change from Mailtrap to production SMTP
$mail->Host       = SMTP_HOST;        // Instead of MAILTRAP_HOST
$mail->Username   = SMTP_USERNAME;    // Instead of MAILTRAP_USERNAME
$mail->Password   = SMTP_PASSWORD;    // Instead of MAILTRAP_PASSWORD
$mail->Port       = SMTP_PORT;        // Instead of MAILTRAP_PORT
```

### 2. Production SMTP Options

**Gmail:**
- Host: `smtp.gmail.com`
- Port: `587`
- Enable 2FA and create App Password
- Username: your Gmail address
- Password: App Password (not your regular password)

**SendGrid:**
- Host: `smtp.sendgrid.net`
- Port: `587`
- Username: `apikey`
- Password: Your SendGrid API key

**Other Providers:**
- Check your email provider's SMTP settings

### 3. Upload Files to InfinityFree

1. Upload all modified files via FTP
2. Upload `vendor/` folder
3. Import database schema to InfinityFree MySQL
4. Test the system

### 4. InfinityFree Specific Notes

- **No Cron Jobs**: Cleanup expired OTPs on page load
- **Execution Time Limit**: Keep queries efficient
- **Memory Limit**: PHPMailer is lightweight, should work fine
- **File Permissions**: Ensure PHP files have correct permissions (644)

---

## Troubleshooting

### Issue: Emails Not Sending

**Check:**
1. PHPMailer files are in the correct location
2. Mailtrap credentials are correct
3. PHP error logs for specific error messages
4. Internet connection on server

**Solution:**
```php
// Add this to email_helper.inc.php to debug:
error_log("PHPMailer files exist: " . (file_exists(__DIR__ . '/../vendor/autoload.php') ? 'YES' : 'NO'));
error_log("Mailtrap Host: " . MAILTRAP_HOST);
```

### Issue: "Class PHPMailer not found"

**Solution:**
1. Verify PHPMailer files exist in `vendor/phpmailer/phpmailer/src/`
2. Check file paths in `email_helper.inc.php`
3. Try using Composer autoloader

### Issue: OTP Not Verifying

**Check:**
1. OTP exists in database with correct email
2. OTP is not expired
3. OTP is not marked as used
4. OTP type matches

**Debug Query:**
```sql
SELECT * FROM otps WHERE email = 'your@email.com' ORDER BY created_at DESC LIMIT 5;
```

### Issue: SMTP Connection Failed

**Check:**
1. SMTP host and port are correct
2. Username and password are correct
3. Firewall allows SMTP connections
4. SSL/TLS settings match your SMTP provider

**Common Fixes:**
- Try port 587 instead of 2525
- Try ENCRYPTION_SMTPS instead of ENCRYPTION_STARTTLS
- Check if your ISP blocks SMTP ports

### Issue: Countdown Timer Not Working

**Check:**
1. JavaScript is enabled
2. No JavaScript errors in browser console
3. Timer variable is initialized correctly

**Debug:**
```javascript
console.log('Remaining time:', remainingTime);
console.log('Countdown interval:', countdownInterval);
```

---

## Security Best Practices

1. **Never commit SMTP credentials** to version control
2. **Use environment variables** for sensitive data in production
3. **Rate limit OTP requests** to prevent abuse
4. **Delete used OTPs** after verification
5. **Log all OTP attempts** for security monitoring
6. **Use HTTPS** in production
7. **Validate all inputs** before processing

---

## API Endpoints

### Send OTP
```
POST api/otp_actions.php?action=send-otp
Content-Type: application/json

{
    "email": "user@example.com",
    "otp_type": "email_verification"
}
```

### Verify OTP
```
POST api/otp_actions.php?action=verify-otp
Content-Type: application/json

{
    "email": "user@example.com",
    "otp_code": "123456",
    "otp_type": "email_verification"
}
```

### Reset Password
```
POST api/otp_actions.php?action=reset-password
Content-Type: application/json

{
    "email": "user@example.com",
    "new_password": "newpassword123"
}
```

---

## Support

For issues or questions:
1. Check error logs
2. Verify database schema
3. Test SMTP connection
4. Review this guide

---

## License

This OTP system is part of the Phambili Ma-Africa project.

---

**Last Updated:** April 23, 2026
**Version:** 1.0.0
