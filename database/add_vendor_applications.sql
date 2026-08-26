-- Vendor application workflow migration for David's Store
-- Run this script after importing the main schema or on an existing installation.

ALTER TABLE users
    MODIFY COLUMN role ENUM('customer','admin','vendor') DEFAULT 'customer';

ALTER TABLE vendors
    ADD COLUMN user_id INT NULL AFTER id,
    ADD COLUMN store_name VARCHAR(150) NULL AFTER name,
    ADD COLUMN business_name VARCHAR(150) NULL AFTER store_name,
    ADD COLUMN business_email VARCHAR(255) NULL AFTER email,
    ADD COLUMN business_type VARCHAR(100) NULL AFTER business_email,
    ADD COLUMN location VARCHAR(150) NULL AFTER business_type,
    ADD COLUMN website VARCHAR(255) NULL AFTER location,
    ADD COLUMN description TEXT NULL AFTER website,
    ADD COLUMN slug VARCHAR(160) NULL AFTER description,
    ADD COLUMN verification_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER slug,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE TABLE IF NOT EXISTS vendor_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(150) NOT NULL,
    business_name VARCHAR(150) NOT NULL,
    contact_phone VARCHAR(30) NOT NULL,
    business_email VARCHAR(255) NOT NULL,
    location VARCHAR(150) NOT NULL,
    business_type VARCHAR(100) NOT NULL,
    website VARCHAR(255) NULL,
    description TEXT NOT NULL,
    terms_accepted TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    review_notes TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

UPDATE vendors
SET store_name = COALESCE(store_name, name),
    business_name = COALESCE(business_name, name),
    verification_status = COALESCE(verification_status, 'pending')
WHERE store_name IS NULL OR business_name IS NULL;
