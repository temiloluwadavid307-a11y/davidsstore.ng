-- Add email_verified_at column to users table if missing
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL AFTER phone;
