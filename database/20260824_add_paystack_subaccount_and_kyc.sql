-- Migration: Add Paystack subaccount fields and KYC tables
-- Run: mysql -u user -p database < 20260824_add_paystack_subaccount_and_kyc.sql

START TRANSACTION;

-- Add columns to vendors table
ALTER TABLE vendors
  ADD COLUMN paystack_subaccount_code VARCHAR(100) DEFAULT NULL,
  ADD COLUMN paystack_subaccount_status ENUM('none','created','active','restricted','failed') DEFAULT 'none',
  ADD COLUMN bank_account_number VARCHAR(50) DEFAULT NULL,
  ADD COLUMN bank_code VARCHAR(20) DEFAULT NULL,
  ADD COLUMN bank_account_name VARCHAR(200) DEFAULT NULL,
  ADD COLUMN kyc_status ENUM('not_started','incomplete','submitted','under_review','verified','rejected','requires_update') DEFAULT 'not_started',
  ADD COLUMN paystack_subaccount_created_at DATETIME DEFAULT NULL,
  ADD COLUMN paystack_subaccount_updated_at DATETIME DEFAULT NULL;

-- Create vendor_kyc table
CREATE TABLE IF NOT EXISTS vendor_kyc (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  legal_name VARCHAR(255) DEFAULT NULL,
  business_name VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  metadata JSON DEFAULT NULL,
  status ENUM('not_started','incomplete','submitted','under_review','verified','rejected','requires_update') DEFAULT 'not_started',
  submitted_at DATETIME DEFAULT NULL,
  reviewed_at DATETIME DEFAULT NULL,
  reviewed_by INT DEFAULT NULL,
  rejection_reason TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
  INDEX idx_vendor_kyc_vendor (vendor_id)
) ENGINE=InnoDB;

-- Create vendor_kyc_documents table
CREATE TABLE IF NOT EXISTS vendor_kyc_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  document_type VARCHAR(100) NOT NULL,
  file_path VARCHAR(1000) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) DEFAULT NULL,
  size INT DEFAULT 0,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
  INDEX idx_vendor_kyc_docs_vendor (vendor_id)
) ENGINE=InnoDB;

COMMIT;
