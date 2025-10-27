# Login Approval Timeout Feature

## Overview
The login approval system now includes **automatic timeout handling** for pending approval requests. When a login approval request exceeds **1 minute (60 seconds)** without admin response, it is automatically marked as "no response" and removed from the pending queue.

---

## 🔧 Setup Instructions

### Step 1: Update Database Schema
Run the update script to add the `no_response` status to your database:

```
http://localhost/pharmacy_system/update_approval_status_enum.php
```

This will modify the `login_approvals` table to support the new status option.

---

## ⚙️ How It Works

### 1. **User Waiting Page** (`waiting_approval.php`)
- User submits login credentials → OTP verification → Redirected to waiting page
- Page polls server **every 1 second** to check approval status
- Shows real-time countdown timer (MM:SS format)
- Automatically detects when request times out

### 2. **Server-Side Timeout Detection** (`check_approval_status.php`)
- Checks if pending request is older than **60 seconds**
- Automatically updates status to `no_response` when timeout occurs
- Logs timeout event in user activity log
- Returns timeout status to waiting page

### 3. **Admin Dashboard** (`admin_portal/login_approvals.php`)
- Shows pending requests in real-time
- **Auto-refreshes every 5 seconds** when there are pending requests
- Stops auto-refresh when no pending requests exist
- Displays timed-out requests in "Recent Activity" section with orange badge

---

## 📊 Status Types

| Status | Description | Badge Color | Action |
|--------|-------------|-------------|--------|
| `pending` | Waiting for admin response | Yellow | Admin can approve/decline |
| `approved` | Admin approved the request | Green | User redirected to dashboard |
| `declined` | Admin declined the request | Red | User redirected to login with error |
| `no_response` | Request timed out (> 60 seconds) | Orange | User redirected to login with timeout message |

---

## 🎨 User Experience

### Waiting Page Messages:
1. **Pending**: "Waiting for approval... (0:XX)"
2. **Approved**: "Approved! Redirecting to your dashboard..."
3. **Declined**: "Your login request was declined. Redirecting..."
4. **Timeout**: "Request timed out (no response). Redirecting..."

### Login Page Messages:
- **After Decline**: "Your login request was declined by an administrator."
- **After Timeout**: "Your login request timed out due to no response from administrator."

---

## 🔄 Auto-Refresh Behavior

### Admin Panel (login_approvals.php):
- **With pending requests**: Auto-refresh every **5 seconds**
- **No pending requests**: No auto-refresh (saves server resources)
- **Manual refresh**: Admin can always manually refresh the page

### Waiting Page (waiting_approval.php):
- **Continuous polling**: Every **1 second**
- **Automatic redirect**: When status changes (approved/declined/timeout)

---

## 📝 Activity Logging

All timeout events are logged in the `user_activity_log` table:
```
Action: "Login approval request timed out (no response)"
User ID: [user_id]
Timestamp: [when timeout occurred]
```

---

## 🎯 Benefits

1. **No Manual Cleanup**: Pending requests automatically clear after 1 minute
2. **Better UX**: Users know when their request timed out
3. **Resource Efficient**: Admin panel only auto-refreshes when needed
4. **Full Audit Trail**: All timeouts logged for security review
5. **Prevents Stale Requests**: Old requests don't pile up in pending queue

---

## 🛠️ Configuration

### Timeout Duration
To change the timeout duration, modify `check_approval_status.php`:

```php
// Current: 60 seconds (1 minute)
if ($approval['status'] === 'pending' && $elapsedSeconds >= 60) {
    // Change 60 to your desired seconds
}
```

### Auto-Refresh Interval
To change admin panel refresh rate, modify `login_approvals.php`:

```javascript
// Current: 5000 milliseconds (5 seconds)
setTimeout(function() {
    location.reload();
}, 5000); // Change this value
```

---

## 📋 Database Schema Update

The `login_approvals` table now includes:
```sql
status ENUM('pending', 'approved', 'declined', 'no_response') DEFAULT 'pending'
```

---

## 🚀 Testing

### Test Timeout Functionality:
1. Login with a non-admin account (POS/CMS/Inventory)
2. Complete OTP verification
3. Wait on approval page for 60+ seconds
4. Observe automatic timeout and redirect
5. Check "Recent Activity" in admin panel for "no_response" entry

---

## 📞 Support

If you encounter any issues:
1. Check that `update_approval_status_enum.php` was run successfully
2. Verify database connection in `db_connect.php`
3. Check browser console for JavaScript errors
4. Review `user_activity_log` table for timeout entries

---

**Version**: 2.0  
**Last Updated**: October 27, 2025  
**Feature**: Automatic Timeout Handling
