-- Migration: Add payments and order_vendor_payouts and settings
-- Run: mysql -u user -p database < 20260824_add_payments_and_payouts.sql

START TRANSACTION;

-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT DEFAULT NULL,
  user_id INT DEFAULT NULL,
  gateway VARCHAR(50) NOT NULL DEFAULT 'paystack',
  paystack_reference VARCHAR(100) DEFAULT NULL,
  paystack_transaction_id VARCHAR(100) DEFAULT NULL,
  gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  marketplace_commission DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','success','failed','refunded') DEFAULT 'pending',
  meta JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_ref (paystack_reference)
) ENGINE=InnoDB;

-- Create order_vendor_payouts table
CREATE TABLE IF NOT EXISTS order_vendor_payouts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  vendor_id INT NOT NULL,
  gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  vendor_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  marketplace_commission DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  subaccount_code VARCHAR(100) DEFAULT NULL,
  paystack_split_reference VARCHAR(100) DEFAULT NULL,
  status ENUM('pending','paid','failed','on_hold') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payouts_order (order_id),
  INDEX idx_payouts_vendor (vendor_id)
) ENGINE=InnoDB;

-- Create settings table if not exists (simple key-value store)
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(191) NOT NULL UNIQUE,
  `value` TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default commission if not present
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('marketplace_commission_percent', '10');

COMMIT;
