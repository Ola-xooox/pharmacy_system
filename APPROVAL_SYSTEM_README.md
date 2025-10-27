# Login Approval System

## Overview
The Login Approval System adds an extra security layer requiring admin approval for non-admin users (POS, CMS, Inventory) after they successfully complete OTP verification.

## Installation

### Step 1: Create Database Table
Run the setup script to create the `login_approvals` table:
```
http://localhost/pharmacy_system/setup_login_approvals.php
```

Or manually run the SQL file:
```
pharmacy_system/create_login_approvals_table.sql
```

## How It Works

### For Non-Admin Users (POS, CMS, Inventory):
1. **Login** - Enter email and password
2. **OTP Verification** - Enter the OTP code sent to email
3. **Waiting for Approval** - User is redirected to a waiting page
4. **Admin Reviews** - Admin approves or declines the request
5. **Access Granted/Denied** - User is either logged in or redirected back to login

### For Admin Users:
- Admins bypass the approval system and login directly after OTP verification

## Admin Portal Features

### Login Approvals Page (`admin_portal/login_approvals.php`)
Access via Admin Portal → **Login Approvals** menu

**Features:**
- ✅ View all pending login requests in real-time
- ✅ See user details (name, email, role, IP address, time)
- ✅ Approve or decline requests with one click
- ✅ View recent approval history (last 24 hours)
- ✅ Auto-refresh every 10 seconds
- ✅ Activity logging for all approvals/declines

**Pending Requests Section:**
- Shows all users waiting for approval
- Display user profile, role badge, email, timestamp, IP address
- Quick approve/decline buttons

**Recent Activity Section:**
- Shows approved/declined requests from last 24 hours
- Includes admin name who reviewed the request
- Color-coded status badges

## User Experience

### Waiting Page (`waiting_approval.php`)
When non-admin users complete OTP verification, they see:
- **Purple gradient design** with professional UI
- **Real-time status checking** (polls every second)
- **Countdown timer** showing elapsed time
- **Auto-redirect** when approved or declined
- **10-minute timeout** after which user must re-login
- **Cancel button** to return to login page

### Status Messages:
- 🟡 **Pending** - "Waiting for approval... (0:30)"
- 🟢 **Approved** - "Approved! Redirecting to your dashboard..."
- 🔴 **Declined** - "Your login request was declined. Redirecting..."

## Database Structure

### `login_approvals` Table:
```sql
id              - Primary key
user_id         - Foreign key to users table
email           - User email
name            - User full name
role            - pos, cms, or inventory
status          - pending, approved, or declined
requested_at    - Request timestamp
reviewed_at     - Review timestamp
reviewed_by     - Admin user ID who reviewed
ip_address      - User's IP address
user_agent      - User's browser info
```

## Security Features

1. **Duplicate Prevention** - Prevents multiple pending approvals for same user
2. **Session Management** - Secure session handling for pending approvals
3. **Activity Logging** - All approvals/declines logged in user_activity_log
4. **IP Tracking** - Records IP address for security auditing
5. **Auto-cleanup** - Declined users redirected to login with error message

## Files Modified/Created

### New Files:
- ✅ `create_login_approvals_table.sql` - Database schema
- ✅ `setup_login_approvals.php` - Setup script
- ✅ `waiting_approval.php` - User waiting page
- ✅ `check_approval_status.php` - AJAX status checker
- ✅ `admin_portal/login_approvals.php` - Admin approval interface
- ✅ `APPROVAL_SYSTEM_README.md` - This documentation

### Modified Files:
- ✅ `index.php` - Added approval logic after OTP verification
- ✅ `admin_portal/admin_sidebar.php` - Added Login Approvals menu item

## Usage Instructions

### For Administrators:
1. Login as admin (bypasses approval)
2. Navigate to **Admin Portal → Login Approvals**
3. Review pending requests
4. Click **Approve** to grant access
5. Click **Decline** to deny access (user redirected to login)

### For Non-Admin Users:
1. Enter email and password
2. Complete OTP verification
3. Wait on approval page (auto-refreshes)
4. Once approved, automatically redirected to dashboard
5. If declined, redirected to login with error message

## Testing

### Test the Flow:
1. Create or use a non-admin user (POS/CMS/Inventory)
2. Login with that user's credentials
3. Complete OTP verification
4. Observe waiting page appears
5. In another browser/tab, login as admin
6. Go to Login Approvals
7. Approve or decline the pending request
8. Observe user is redirected accordingly

## Customization

### Adjust Timeout:
Edit `waiting_approval.php` line 223:
```javascript
const maxChecks = 600; // 10 minutes (600 checks * 1 second)
```

### Change Auto-refresh Interval:
Edit `admin_portal/login_approvals.php` line 386:
```javascript
setTimeout(function() { location.reload(); }, 10000); // 10 seconds
```

### Modify Polling Frequency:
Edit `waiting_approval.php` line 271:
```javascript
setTimeout(checkApprovalStatus, 1000); // Check every 1 second
```

## Troubleshooting

### Issue: Table doesn't exist
**Solution:** Run `setup_login_approvals.php`

### Issue: Users not seeing waiting page
**Solution:** Check that user role is 'pos', 'cms', or 'inventory' (not 'admin')

### Issue: Page not auto-refreshing
**Solution:** Check browser console for JavaScript errors

### Issue: Approval status not updating
**Solution:** Verify `check_approval_status.php` is accessible and session is active

## Support
For issues or questions, contact the system administrator.

---

**Version:** 1.0  
**Last Updated:** October 27, 2025  
**Developed for:** MJ Pharmacy System
