-- Vendor workflow upgrade migration for David's Store
-- Run this script once on the target database to add application and approval fields.

ALTER TABLE users
    MODIFY COLUMN role ENUM('customer','vendor','admin','super_admin') DEFAULT 'customer',
    ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL AFTER phone;

ALTER TABLE vendors
    ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS approval_date TIMESTAMP NULL AFTER status,
    ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER approval_date,
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER approved_by,
    ADD COLUMN IF NOT EXISTS business_type VARCHAR(100) NULL AFTER business_name,
    ADD COLUMN IF NOT EXISTS business_address VARCHAR(255) NULL AFTER business_type,
    ADD COLUMN IF NOT EXISTS cac_number VARCHAR(100) NULL AFTER business_address,
    ADD COLUMN IF NOT EXISTS government_id VARCHAR(255) NULL AFTER cac_number,
    ADD COLUMN IF NOT EXISTS tax_number VARCHAR(100) NULL AFTER government_id,
    ADD COLUMN IF NOT EXISTS store_slug VARCHAR(160) NULL AFTER store_name,
    ADD COLUMN IF NOT EXISTS store_logo VARCHAR(255) NULL AFTER store_slug,
    ADD COLUMN IF NOT EXISTS store_banner VARCHAR(255) NULL AFTER store_logo,
    ADD COLUMN IF NOT EXISTS store_category VARCHAR(100) NULL AFTER store_banner,
    ADD COLUMN IF NOT EXISTS preferred_currency VARCHAR(10) NULL AFTER store_category,
    ADD COLUMN IF NOT EXISTS bank_name VARCHAR(128) NULL AFTER preferred_currency,
    ADD COLUMN IF NOT EXISTS account_name VARCHAR(128) NULL AFTER bank_name,
    ADD COLUMN IF NOT EXISTS account_number VARCHAR(64) NULL AFTER account_name;

ALTER TABLE vendors
    ADD UNIQUE KEY uq_vendors_store_slug (store_slug);

ALTER TABLE vendor_applications
    ADD COLUMN IF NOT EXISTS business_registration_number VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS business_address VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS state VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS country VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS postal_code VARCHAR(30) NULL,
    ADD COLUMN IF NOT EXISTS store_slug VARCHAR(160) NULL,
    ADD COLUMN IF NOT EXISTS store_logo VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS store_banner VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS store_category VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS preferred_currency VARCHAR(10) NULL,
    ADD COLUMN IF NOT EXISTS government_id VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS business_registration_document VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS tax_number VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS bank_name VARCHAR(128) NULL,
    ADD COLUMN IF NOT EXISTS account_name VARCHAR(128) NULL,
    ADD COLUMN IF NOT EXISTS account_number VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS review_request_message TEXT NULL;

ALTER TABLE vendor_applications
    MODIFY COLUMN status ENUM('pending','approved','rejected','information_requested') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('success','info','warning','error') NOT NULL DEFAULT 'info',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_id INT NOT NULL,
    subject_type VARCHAR(100) NOT NULL,
    subject_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_subject (subject_type, subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    html TEXT NOT NULL,
    text TEXT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    last_attempt_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
