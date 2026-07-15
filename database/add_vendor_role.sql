-- Add vendor role to users table in the selected database.
-- Run this script against the active database using your hosting tools.

-- Add vendor role to enum if not present
ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin', 'vendor') DEFAULT 'customer';

-- Create a test vendor user (password: password)
INSERT INTO users (first_name, last_name, email, password_hash, phone, role) VALUES
('David', 'Vendor', 'vendor@davidsstore.ng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '08098765432', 'vendor');
