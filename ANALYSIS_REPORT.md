# Life Skills Coaching System - Admin Login Analysis Report

## Executive Summary
The admin login system relies on a properly configured MySQL database with the `admins` table and at least one admin account. The login process itself is correctly coded, but it will fail if the prerequisite database setup is not completed.

---

## Technology Stack
- **Backend:** PHP 7.4+ with PDO
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Authentication:** `password_verify()` with bcrypt hashing
- **Sessions:** PHP native sessions

---

## Component Analysis

### 1. Login Page (`login.php`)
**Status:** ✓ Code is correct

**How it works:**
```
1. User visits login.php
2. If already logged in as admin → Redirects to dashboard
3. If already logged in as student → Redirects to student dashboard
4. Otherwise → Shows login form
5. On POST with role='Admin':
   - Query admins table for matching email
   - Verify password with password_verify()
   - If valid → Set session variables and redirect
   - If invalid → Show error message
```

**Code Quality:** Good - Uses prepared statements to prevent SQL injection

---

### 2. Database Configuration (`db.php`)
**Status:** ✓ Code is correct, BUT verify port number

```php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=life_skills_coaching;charset=utf8mb4", "root", "");
```

**⚠️ Important:** Port 3307 is the default XAMPP MySQL port. Verify yours:
- Open XAMPP Control Panel → MySQL → Click "Config"
- Check port in `my.ini`
- Default XAMPP ports: 3306 or 3307

---

### 3. Database Schema (`database.sql`)
**Status:** ✓ Schema is complete

The admins table is defined:
```sql
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### 4. Admin Session Handler (`admin/header.php`)
**Status:** ✓ Code is correct

Checks if user is logged in:
```php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to login
}
```

---

## Root Cause Analysis: Why Admin Login Fails

### Most Likely Cause (90%)
**The database hasn't been set up yet.**

The code is correct, but the `admins` table is empty or doesn't exist.

### Setup Checklist:
- [ ] MySQL is running
- [ ] Database `life_skills_coaching` exists
- [ ] Table `admins` exists
- [ ] At least one admin record exists with valid password hash

### How to Verify & Fix:

#### Step 1: Check if database exists
```sql
SHOW DATABASES LIKE 'life_skills_coaching';  
-- Should show 1 result
```

#### Step 2: Check if admins table exists
```sql
SHOW TABLES FROM life_skills_coaching LIKE 'admins';
-- Should show 1 result
```

#### Step 3: Check if admin records exist
```sql
SELECT * FROM life_skills_coaching.admins;
-- Should show at least 1 row
```

#### Step 4: If anything is missing, run:
- Visit: `http://localhost/life_skills_coaching/setup_admin_table.php`
- This will create the table and insert the default admin

---

## Step-by-Step Fix Guide

### Quick Fix (5 minutes)
1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL
   - Wait for both to say "Running"

2. **Initialize Database**
   - Visit: `http://localhost/life_skills_coaching/setup_admin_table.php`
   - You should see success messages

3. **Test Login**
   - Go to: `http://localhost/life_skills_coaching/login.php`
   - Role: Admin
   - Email: admin@example.com
   - Password: admin123
   - Click "Log In"

4. **Verify**
   - Should redirect to: `/admin/dashboard.php`
   - Should see dashboard with statistics

---

## If Quick Fix Doesn't Work: Detailed Diagnosis

### 1. Run the Diagnostic Tool
Visit: `http://localhost/life_skills_coaching/debug_login.php`

This tool shows:
- Database connection status
- All table names
- Number of admin records
- Password verification test
- Manual login test form

### 2. Check Common Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| Database connection failed | MySQL not running or port wrong | Check XAMPP, verify db.php port |
| 'admins' table doesn't exist | Database not initialized | Run setup_admin_table.php |
| No admin records found | Setup script not run | Run setup_admin_table.php |
| Password verification fails | Hash corrupted or wrong password | Recreate admin via setup_admin_table.php |
| Stuck on login page after submit | Session not persisting | Check session save path, clear cookies |

### 3. MySQL Direct Verification
Open PHPMyAdmin: `http://localhost/phpmyadmin`

```sql
-- Check if database exists
SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA` WHERE `SCHEMA_NAME` = 'life_skills_coaching';

-- Check all tables
SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA` = 'life_skills_coaching';

-- Check admin records
SELECT id, name, email, SUBSTR(password, 1, 30) as hash_preview FROM `life_skills_coaching`.`admins`;

-- Check password hash validity (should be 60 chars for bcrypt)
SELECT id, email, CHAR_LENGTH(password) as hash_length FROM `life_skills_coaching`.`admins`;
```

---

## Project Architecture Overview

```
life_skills_coaching/
├── login.php                 [Entry point - Admin/Student login]
├── db.php                    [Database connection]
├── database.sql              [Complete schema]
├── setup_admin_table.php     [Initialization script]
│
├── admin/                    [Admin-only section]
│   ├── header.php           [Session check & navigation]
│   ├── dashboard.php        [Admin home & statistics]
│   ├── [CRUD pages]         [Manage courses, students, etc.]
│   └── logout.php           [Session cleanup]
│
├── student/                 [Student-only section]
│   ├── student_login.php    [Student login (alternative]
│   ├── student_register.php [Student registration]
│   ├── header.php           [Student session check]
│   ├── student_dashboard.php [Student home]
│   └── [feature pages]
│
└── uploads/                 [Profile images, files]
```

---

## Session Flow Diagram

```
login.php (No auth)
    ↓ Selects Admin role + credentials
    ↓ POST request with email & password
    ↓ Query admins table
    ├─→ NO MATCH → Show error
    └─→ FOUND → password_verify()
        ├─→ FAIL → Show error
        └─→ PASS → Set session vars
            ├─ admin_logged_in = true
            ├─ admin_id = [id]
            └─ admin_name = [name]
            ↓ Redirect to dashboard
admin/header.php (Auth required)
    ├─→ Session OK → Show page + sidebar
    └─→ Session missing → Redirect to login
```

---

## Key Session Variables

When admin logs in successfully:
```php
$_SESSION['admin_logged_in'] = true;      // Auth flag
$_SESSION['admin_id'] = $admin['id'];     // For DB queries
$_SESSION['admin_name'] = $admin['name']; // For display
```

Every admin page checks these in `admin/header.php`.

---

## Security Notes

✓ **What's implemented correctly:**
- Password hashing with bcrypt (password_hash/password_verify)
- Prepared statements (prevents SQL injection)
- Session variables for state management
- Redirect on logout (session_destroy)

⚠️ **Recommendations:**
- Add CSRF token protection to forms
- Add rate limiting to login attempts
- Add password complexity requirements (min 8 chars, etc.)
- Implement "remember me" with secure tokens
- Add admin activity logging
- Implement session timeout
- Add two-factor authentication (future enhancement)

---

## Files to Keep Updated

When modifying admin functionality:
1. **Database schema changes** → Update database.sql
2. **Add new admin features** → Update admin/header.php navigation
3. **Session requirements** → Update admin/header.php auth check
4. **Admin queries** → Ensure prepared statements
5. **Redirects** → Verify relative paths work from all locations

---

## Testing Checklist

- [ ] Database initializes successfully
- [ ] Admin account created with valid credentials
- [ ] Login form submits POST request
- [ ] Valid credentials log in successfully
- [ ] Invalid credentials show error
- [ ] Session persists across pages
- [ ] Logout clears session
- [ ] Unauthenticated access redirects to login
- [ ] Admin dashboard loads with correct data
- [ ] Courses, students, quizzes accessible

---

## Related Credentials

### Default Admin (Created by setup_admin_table.php)
- **Email:** admin@example.com
- **Password:** admin123
- **Action:** Change immediately after first login

### Database
- **Host:** 127.0.0.1 (or localhost)
- **Port:** 3307 (XAMPP default, verify yours)
- **Database:** life_skills_coaching
- **User:** root
- **Password:** (empty for local XAMPP)

---

## Additional Resources in This Project

- `ADMIN_LOGIN_GUIDE.md` - Step-by-step fix guide
- `debug_login.php` - Interactive diagnostic tool
- `admin_login_diagnostic.php` - Automated diagnostic report
- `test_login.php` - Direct password verification test

