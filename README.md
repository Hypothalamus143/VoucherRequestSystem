# CIT-U Voucher Request System

A web-based voucher request system for Cebu Institute of Technology – University, built with PHP, HTML, CSS, and JavaScript.

---

## Repository Structure

```
voucher-request-system/
│
├── index.php                   # Student login page (entry point)
├── .htaccess                   # Apache security & config
│
├── config/
│   ├── database.php            # DB credentials & connection helper
│   └── schema.sql              # Full database schema — run once to initialize
│
├── includes/
│   ├── auth.php                # Session management, auth guards, helpers
│   ├── header.php              # Shared HTML <head> + <body> open
│   └── footer.php              # Shared </body></html> close
│
├── student/
│   ├── register.php            # Create student account
│   ├── dashboard.php           # View ongoing & accomplished requests
│   ├── submit-request.php      # Submit a new voucher request
│   ├── request.php             # View a single request + reply thread
│   ├── post-reply.php          # AJAX endpoint — submit a reply
│   ├── delete-reply.php        # POST endpoint — delete own reply
│   └── logout.php              # Destroy session, redirect to login
│
├── public/
│   ├── css/
│   │   └── main.css            # All styles (auth, dashboard, cards, replies)
│   ├── js/
│   │   └── main.js             # Client-side interactivity
│   └── assets/
│       └── cit-logo.png        # ← Place CIT-U logo here
│
└── tsg/                        # (Future scope — TSG admin views)
```

---

## Setup Instructions

### 1. Requirements
- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.6+
- Apache with `mod_rewrite` enabled (or Nginx equivalent)

### 2. Database
```bash
mysql -u root -p < config/schema.sql
```
Or run `schema.sql` in phpMyAdmin / TablePlus.

### 3. Configuration
Edit `config/database.php` and set your credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'voucher_request_db');
```

### 4. Assets
Place the CIT-U logo PNG at:
```
public/assets/cit-logo.png
```

### 5. Web Server
Point your document root to the `voucher-request-system/` folder.

For local development with PHP's built-in server:
```bash
cd voucher-request-system
php -S localhost:8000
```
Then visit http://localhost:8000

---

## Pages (Student Flow)

| Page | File | Description |
|------|------|-------------|
| Login | `index.php` | Username + password login |
| Register | `student/register.php` | Create a new student account |
| Dashboard | `student/dashboard.php` | List ongoing & accomplished requests |
| Submit Request | `student/submit-request.php` | Write and submit a new request |
| View Request | `student/request.php?id=N` | See request detail + reply thread |

---

## Business Rules Implemented

| # | Rule | Status |
|---|------|--------|
| 4 | User can log in | ✅ |
| 5 | Student can register an account | ✅ |
| 6 | Student can create a request | ✅ |
| 7 | Student can view his/her requests | ✅ |
| 8 | Student can delete his/her requests | ✅ |
| 2 | User can create a reply to a request or reply | ✅ |
| 3 | User can delete his/her reply | ✅ |

---

## Database Schema Summary

```
User(userID, username, fname, mname, lname, password, userType)
Student(studID, userID, yearLevel)
TSG(empID, userID)
Request(requestID, studID, datetime, message, isAccomplished)
Reply(replyID, parentID, userID, datetime, message, isFromRequest)
```

---

## Security Notes
- Passwords are hashed with `bcrypt` via PHP's `password_hash()`
- All DB queries use prepared statements (no raw interpolation)
- Sessions are used for authentication; user type is verified on each protected page
- Direct access to `.sql`, `.md`, and config files is blocked via `.htaccess`
