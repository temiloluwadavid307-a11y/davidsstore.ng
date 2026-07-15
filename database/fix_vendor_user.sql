-- Run this script in the selected database.

-- Ensure vendor role exists
ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'vendor') DEFAULT 'customer';

-- Delete if exists first
DELETE FROM users WHERE email = 'vendor@davidsstore.ng';

-- Insert with correct hash (password = "password")
INSERT INTO users (first_name, last_name, email, password_hash, phone, role) 
VALUES ('David', 'Vendor', 'vendor@davidsstore.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08098765432', 'vendor');
