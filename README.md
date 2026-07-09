# StokvelX – Setup Guide

A student-focused digital stokvel (rotating savings club) platform. PHP + MySQL backend served through XAMPP.

---

## Requirements

- **XAMPP** (recommended) – includes Apache + MySQL + phpMyAdmin
  - Download: https://www.apachefriends.org
  - Windows / macOS / Linux versions available
- **VS Code** – to browse and edit the code
- Any modern browser (Chrome, Firefox, Edge)

---

## Quick Start (5 minutes)

### 1. Install & Start XAMPP

1. Download and install XAMPP from https://www.apachefriends.org
2. Open the **XAMPP Control Panel**
3. Click **Start** next to **Apache** and **MySQL**
4. Both status lights should turn green ✅

### 2. Copy the Project

Copy the entire `stokvelx` folder into XAMPP's web root:

| OS | Web root location |
|----|------------------|
| Windows | `C:\xampp\htdocs\` |
| macOS | `/Applications/XAMPP/htdocs/` |
| Linux | `/opt/lampp/htdocs/` |

Your final path should be: `htdocs/stokvelx/`

### 3. Import the Database

1. Open **phpMyAdmin** in your browser: http://localhost/phpmyadmin
2. Click **Import** in the top menu
3. Click **Choose File** and select:
   `stokvelx/backend/database/schema.sql`
4. Scroll down and click **Go / Import**
5. You should see: *Import has been successfully finished*

This creates the `stokvelx` database with all tables and demo data.

### 4. Open the App

Visit in your browser: **http://localhost/stokvelx/**

> ⚠️ Do **not** open the HTML files directly or use VS Code Live Server.
> The app **must** be served through Apache (XAMPP) because it uses PHP.

---

## Demo Login Accounts

| Role | Email | Password |
|------|-------|----------|
| 🎓 Student (Sipho) | sipho@student.uct.ac.za | password123 |
| 🛡️ Admin | admin@stokvelx.com | admin123 |

The demo student account (Sipho) has:
- Wallet balance: **R980.00**
- 2 active circles (Campus Builders + Future Leaders)
- 1 completed circle with payout received
- Upcoming payouts: R2,400 on 29 May + R2,320 on 5 June
- 5 referrals (3 paid, 2 pending)
- Full transaction history

---

## Database Config

If your MySQL uses a different username or password, edit:
`backend/config/db.php`

```php
$DB_USER = 'root';   // change if needed
$DB_PASS = '';       // add your password here
```

Default XAMPP settings use `root` with no password — no changes needed.

---

## Project Structure

```
stokvelx/
├── index.html              ← Landing / marketing page
├── css/
│   └── style.css           ← All styles (single file)
├── js/
│   ├── api.js              ← API helper + auth guards
│   ├── nav.js              ← Shared sidebar navigation
│   └── main.js             ← Shared UI (toasts, modals, etc.)
├── pages/
│   ├── login.html
│   ├── signup.html         ← 3-step onboarding
│   ├── dashboard.html
│   ├── circles.html        ← My Circles
│   ├── join.html           ← Browse & join circles
│   ├── wallet.html
│   ├── transactions.html
│   ├── referrals.html
│   ├── chat.html
│   ├── profile.html
│   ├── settings.html
│   └── admin.html          ← Admin panel (admin login required)
└── backend/
    ├── config/
    │   └── db.php          ← ← Edit this for your DB credentials
    ├── includes/
    │   ├── bootstrap.php   ← Session, headers, helper functions
    │   └── auth.php        ← Login guards, user fetcher
    ├── database/
    │   └── schema.sql      ← Import this into phpMyAdmin
    └── api/
        ├── auth/           ← register, login, logout, me
        ├── circles/        ← list, my-circles, join, create
        ├── payments/       ← pay
        ├── wallet/         ← summary, add-money, withdraw
        ├── transactions/   ← list (paginated)
        ├── referrals/      ← stats
        ├── chat/           ← messages, send
        ├── profile/        ← update (GET + POST)
        └── admin/          ← stats, members
```

---

## Circle Types

| Type | Description |
|------|-------------|
| Standard | Classic rotating savings — one member receives the full pot per week |
| Premium | Fast-payout option with an extra fee for priority position |
| Investment | Longer cycle (24 weeks) with higher weekly contributions |

---

## How Referrals Work

1. Share your referral code (e.g. `SIPHO10`)
2. A new user enters it when signing up
3. Once they make their **first ever contribution**, you earn **R10**
4. Reach **10 paid referrals** and earn a one-off **R50 milestone bonus**

---

## Editing the Code

1. Open VS Code: **File → Open Folder** → select `stokvelx/`
2. Edit any file and save
3. Refresh the browser at `http://localhost/stokvelx/` to see changes

PHP changes take effect immediately on refresh.

---

## Common Issues

**"Database connection failed"**
→ Make sure MySQL is started in the XAMPP Control Panel and you've imported the schema.

**Page loads but shows no data / spins**
→ Check you're on `http://localhost/stokvelx/` not `file://...` or a Live Server port.

**Login says "Incorrect email or password"**
→ Make sure the schema was imported correctly. Try the admin account first.

**phpMyAdmin won't open**
→ Try http://127.0.0.1/phpmyadmin instead of localhost.
