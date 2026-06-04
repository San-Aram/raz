# Testing Audit Logs - Quick Guide

## ✅ All Changes Applied Successfully

All PHP files have been updated and verified for syntax errors:
- ✅ `login.php` - User login logging
- ✅ `simple-admin-users.php` - User management logging  
- ✅ `basic-admin-auth.php` - Admin login logging
- ✅ `includes/admin-settings-helper.php` - logAuditEvent() function
- ✅ `includes/simple-admin-auth.php` - Admin logout logging

## Quick Test Workflow

### Step 1: Clear Existing Logs (Optional)
If you want a fresh start, delete the audit_logs table entries (don't delete the table itself):
```sql
TRUNCATE TABLE audit_logs;
```

### Step 2: Test Admin Login Logging
1. Go to `http://yoursite.com/admin-login.php`
2. Enter valid admin credentials
3. Click "Login"
4. You should now be in the Admin Dashboard
5. Click "Logs" tab
6. ✅ You should see an entry with action: `admin_login_success`

### Step 3: Test User Creation Logging
1. From Admin Dashboard, go to "User Management"
2. In the "Create New User" form on the right:
   - Username: `testuser001`
   - Password: `Password123`
   - Confirm Password: `Password123`
   - Role: Select "Manager"
3. Click "Create User"
4. ✅ You should see success message
5. Go to "Logs" tab
6. ✅ You should see a new entry with action: `user_created`

### Step 4: Test User Login Logging
1. Open a private/incognito browser window (or log out first)
2. Go to `http://yoursite.com/login.php`
3. Click on "Manager" tab (or make sure it's selected)
4. Enter credentials:
   - Username: `testuser001`
   - Password: `Password123`
5. Click "Login"
6. ✅ User should log in successfully
7. If admin, check "Logs" tab
8. ✅ You should see an entry with action: `user_login_success`

### Step 5: Test Failed Login Logging
1. Go to `http://yoursite.com/login.php`
2. Enter:
   - Username: `testuser001`
   - Password: `wrongpassword`
3. Click "Login"
4. ✅ Should see error "Invalid username or password"
5. Go back to admin (Admin Panel → Logs)
6. ✅ You should see an entry with action: `login_attempt_failed`

### Step 6: Test User Deletion Logging
1. From Admin Dashboard → User Management
2. Find the user you created (`testuser001`)
3. Click the trash icon to delete
4. ✅ You should see success message
5. Go to "Logs" tab
6. ✅ You should see an entry with action: `user_deleted`

### Step 7: Test Role Change Logging
1. Create another test user (same process as Step 3)
2. From User Management, find that user
3. Change their role (e.g., from "Seller" to "Manager")
4. ✅ Click the button to update role
5. Go to "Logs" tab
6. ✅ You should see an entry with action: `user_role_changed`

### Step 8: Test Admin Logout Logging
1. From Admin Dashboard, click "Logout"
2. ✅ Admin should be logged out
3. Log back in as admin
4. Go to "Logs" tab
5. ✅ You should see an entry with action: `admin_logout` (from previous session)

## What the Logs Should Show

Each log entry includes:
| Field | Example |
|-------|---------|
| **User** | Username of who performed the action |
| **Action** | `user_login_success`, `user_created`, `admin_login_success`, etc. |
| **Table** | Which table was affected (e.g., `users`) |
| **Record ID** | ID of the affected record |
| **Old Values** | Previous values (in JSON format) |
| **New Values** | Updated values (in JSON format) |
| **IP Address** | Source IP address |
| **Timestamp** | When the action occurred |

## Troubleshooting

### Logs Not Appearing?
1. Make sure you're an admin user
2. Check that you can see the "Logs" tab in Admin Dashboard
3. Verify the audit_logs table exists:
   ```sql
   SHOW TABLES LIKE 'audit_logs';
   ```
4. If table doesn't exist, perform any action (login, create user) and it will be created automatically

### Error Messages?
1. Check the PHP error logs in your web server
2. The system logs errors to error_log automatically
3. Each logging call has error handling to prevent crashes

### Getting "No records" in Logs?
1. Make sure you've actually performed an action (login, create user, etc.)
2. The table might exist but be empty
3. Create a new user or log in again - this should create an entry

## Expected Log Entries After Full Test

After following all 8 steps above, your audit logs should contain at least these entries (in reverse chronological order):

1. `admin_logout` (from Step 8)
2. `admin_login_success` (from Step 8)
3. `user_deleted` (from Step 6)
4. `login_attempt_failed` (from Step 5)
5. `user_login_success` (from Step 4)
6. `user_created` (from Step 3)
7. `admin_login_success` (from Step 2)

---

**If you see these entries in your Logs tab, the audit logging system is working perfectly! ✅**
