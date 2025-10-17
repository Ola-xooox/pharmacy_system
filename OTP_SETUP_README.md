# OTP Authentication Setup for MJ Pharmacy System

This document provides instructions for setting up and using the OTP (One-Time Password) authentication system for the MJ Pharmacy management system.

## 🚀 Quick Setup

### 1. Database Setup
First, run the SQL script to add the necessary database changes:

```sql
-- Run this in your phpMyAdmin or MySQL client
SOURCE otp_setup.sql;
```

Or manually execute the contents of `otp_setup.sql` in your database.

### 2. Admin Account Setup
The system will automatically create/update the admin account with:
- **Email**: lhandelpamisa0@gmail.com
- **Password**: admin24
- **Role**: admin

### 3. Email Configuration
The system uses PHP's built-in `mail()` function. For production use, you may want to configure SMTP settings or use a service like PHPMailer with Gmail/SendGrid.

## 📧 How OTP Login Works

### For Admin (Existing Account)
1. Go to `index.php`
2. Enter email: `lhandelpamisa0@gmail.com`
3. Click "Send OTP Code"
4. Check email for 6-digit code
5. Enter the code and login

### For New Employees
1. Admin creates employee account via "Setup Account" page
2. **Important**: Admin must include employee's email address
3. Employee can then use OTP login with their email
4. No app passwords needed - just their email address

## 🔧 Features

### OTP System Features
- ✅ 6-digit random OTP codes
- ✅ 5-minute expiration time
- ✅ Email validation
- ✅ Secure HTML email templates
- ✅ Automatic cleanup of expired OTPs
- ✅ Rate limiting (one OTP per email at a time)

### Login Options
- **Traditional Login**: Username + Password (`index.php`)
- **OTP Login**: Email + OTP Code (`index.php`)
- Both methods redirect to appropriate dashboards based on user role

### Employee Management
- Admin can create employee accounts with email addresses
- Email field is optional but required for OTP functionality
- Employees can login using either method

## 📁 New Files Added

1. **`otp_setup.sql`** - Database schema changes
2. **`otp_mailer.php`** - OTP generation and email functionality
3. **`index.php`** - OTP login interface
4. **`OTP_SETUP_README.md`** - This documentation

## 🔒 Security Features

- Passwords are hashed using PHP's `password_hash()`
- OTP codes expire after 5 minutes
- Email validation for all email inputs
- SQL injection protection with prepared statements
- Session management for secure authentication

## 🎯 User Roles & Redirections

After successful login (both traditional and OTP), users are redirected based on their role:

- **Admin** → `admin_portal/dashboard.php`
- **POS** → `pos/pos.php`
- **Inventory** → `inventory/products.php`
- **CMS** → `cms/customer_history.php`

## 📧 Email Template

The OTP email includes:
- Professional MJ Pharmacy branding
- Large, easy-to-read OTP code
- 5-minute expiration notice
- Security warnings
- Responsive HTML design

## 🛠️ Troubleshooting

### OTP Not Received
1. Check spam/junk folder
2. Verify email address is correct
3. Ensure PHP mail() function is configured
4. Check server mail logs

### Login Issues
1. Verify database changes were applied
2. Check that email column exists in users table
3. Ensure OTP hasn't expired (5 minutes)
4. Try traditional username/password login

### Database Errors
1. Run `otp_setup.sql` to ensure all tables exist
2. Check database connection in `db_connect.php`
3. Verify user permissions for database operations

## 🔄 Migration Notes

- Existing users can continue using username/password login
- Admin account is automatically updated with the specified email
- New employee accounts should include email addresses for OTP functionality
- The system is backward compatible with existing authentication

## 📞 Support

For technical support or questions about the OTP system:
1. Check the troubleshooting section above
2. Verify all files are uploaded correctly
3. Ensure database changes are applied
4. Test with the admin account first

---

**Note**: This OTP system is designed for internal pharmacy management use. For production deployment, consider additional security measures like rate limiting, CAPTCHA, and professional email service integration.
