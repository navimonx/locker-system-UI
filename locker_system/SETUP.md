# SecureLocker — setup (PHP + MySQL)

Pure PHP and MySQL/MariaDB. No Node, no SQL Server, no extra installs.

## 1. Import the database
1. Start Apache and MySQL (XAMPP Control Panel) and open **http://localhost/phpmyadmin**.
2. Click the **Import** tab, choose `locker_system.sql`, press **Go**.
   - It creates the `locker_system` database, all tables, 720 lockers
     (CABA, CEIT, COED, CPAG, NB, CAS x 6 floors x 20) and one admin account.
   - Safe to import again: it never duplicates or deletes existing data.
   - On shared hosting (cPanel, etc.): create an empty database in your hosting panel,
     select it in phpMyAdmin, delete the `CREATE DATABASE` and `USE` lines at the top of
     the .sql file, then import.

## 2. Put the site online
- **XAMPP:** copy this whole folder to `C:\xampp\htdocs\locker_system`, then open
  **http://localhost/locker_system/**
- **Web hosting:** upload the folder contents with FTP / File Manager.

## 3. Check the database settings
Open `db.php`. The four lines at the top control the connection:

| Setting   | XAMPP default   | Shared hosting                         |
|-----------|-----------------|----------------------------------------|
| `DB_HOST` | `localhost`     | usually `localhost`                    |
| `DB_NAME` | `locker_system` | name shown in your hosting panel       |
| `DB_USER` | `root`          | the database user from your panel      |
| `DB_PASS` | *(empty)*       | the password you set for that user     |

## 4. First login
- ID: `admin@plv.edu.ph`   Password: `admin123`
- Change the password right away (Settings page) or create your own admin from
  **Create Admin**.
- Students: an admin adds their Student Number (XX-XXXX) on the Records page, then the
  student signs up with it.

## Requirements
PHP 7.4+ (8.x recommended) with the `mysqli` extension (on by default in XAMPP) and
MySQL 5.7+ or MariaDB 10.3+.
