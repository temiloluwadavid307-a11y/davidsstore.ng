# CodesbyDavid Marketplace Setup Guide

## Prerequisites
- PHP and MySQL/MariaDB hosting
- Web browser

## Step 1: Database Setup

1. Create a new database using your hosting control panel or phpMyAdmin.
2. Import the schema from `server/schema.sql`.
3. Optionally run `server/seed.php` after database setup to populate sample users and products.

### To run seed.php:
Visit `http://your-domain-or-localhost/your-app-path/server/seed.php` in your browser.

This will create:
- Admin user: admin@example.com / admin123
- Vendor user: vendor@example.com / vendor123
- Customer user: customer@example.com / customer123
- Sample products

## Step 2: Configure Database Connection

Open `includes/config.php` and set the database values for your environment.
If your host supports environment variables, you can also define:
- `DB_HOST`
- `DB_PORT`
- `DB_USER`
- `DB_PASS`
- `DB_NAME`
- `APP_URL`
- `APP_ENV`

For XAMPP, the defaults are usually:
- Host: `localhost`
- Port: `3306`
- Username: `root`
- Password: `` (empty)
- Database: `codesbyd4`

For live hosting like InfinityFree or Namecheap, update `includes/config.php` with the credentials
provided by your hosting control panel, or set the environment variables as available.

## Step 3: Access the Application

Open the application in your browser using the app URL from `APP_URL` in `includes/config.php`.

## Features Implemented

- User Registration & Authentication
- Product Catalog
- Shopping Cart
- Order Management
- Multiple user roles: Customer, Vendor, Admin
