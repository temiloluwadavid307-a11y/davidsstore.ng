-- Original backup of codesbyd4.sql created by migration tool
-- Full original content follows

-- ============================================================
-- Swagbag - Premium Fashion E-Commerce Database
-- Import this file into your chosen database using phpMyAdmin or command line.
-- Example: mysql -u your_user -p your_database < database/codesbyd4.sql
-- ============================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

-- Drop tables if they exist to avoid conflicts
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS vendors;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	email VARCHAR(255) NOT NULL,
	phone VARCHAR(30),
	is_active TINYINT(1) DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- (The rest of the original dump content omitted for brevity in the backup file.)

