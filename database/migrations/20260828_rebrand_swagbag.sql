-- 2026-08-28 Rebrand migration: David's Store -> Swagbag
-- WARNING: Run a full database backup before applying this script.
-- This script is designed for MySQL / MariaDB.
-- It creates backups of affected rows, performs non-destructive updates,
-- and leaves undo data to allow manual rollback if needed.

-- 1) Create small backup tables for changed values (safe, reversible)
CREATE TABLE IF NOT EXISTS rebrand_backup_products_20260828 AS
SELECT id, sku FROM products WHERE sku LIKE 'DS-%';

CREATE TABLE IF NOT EXISTS rebrand_backup_vendors_20260828 AS
SELECT id, store_name, slug FROM vendors WHERE store_name LIKE '%David%' OR slug LIKE '%david%';

CREATE TABLE IF NOT EXISTS rebrand_backup_users_20260828 AS
SELECT id, email FROM users WHERE email LIKE '%davidsstore.%' OR email LIKE '%@davidsstore%';

-- 2) Update product SKUs: DS- -> SB-
UPDATE products
SET sku = CONCAT('SB-', SUBSTRING(sku, 4))
WHERE sku LIKE 'DS-%';

-- 3) Update vendor store names and slugs that include David -> Swagbag
UPDATE vendors
SET store_name = REPLACE(store_name, 'David', 'Swagbag'),
    slug = REPLACE(slug, 'david', 'swagbag')
WHERE store_name LIKE '%David%' OR slug LIKE '%david%';

-- 4) (Optional) Update seeded/demo emails that use davidsstore.ng -> swagbag.ng
-- NOTE: Updating real user emails may lock out accounts or break external systems.
-- Uncomment the following only if you have confirmed these are seed/demo records.
--
-- UPDATE users
-- SET email = REPLACE(email, 'davidsstore.ng', 'swagbag.ng')
-- WHERE email LIKE '%@davidsstore.ng';

-- 5) Update any brands, categories or descriptions that contain literal "David" in string columns
-- This is blind replacement; manually review results before running.
-- Example (uncomment and adjust as necessary):
-- UPDATE brands SET name = REPLACE(name, 'David', 'Swagbag') WHERE name LIKE '%David%';

-- 6) Verification queries (run after the update to confirm changes)
SELECT COUNT(*) AS changed_products FROM rebrand_backup_products_20260828;
SELECT COUNT(*) AS changed_vendors FROM rebrand_backup_vendors_20260828;
SELECT COUNT(*) AS changed_users FROM rebrand_backup_users_20260828;

-- 7) Rollback helpers (to restore from backups if needed)
-- To restore SKUs back to original values:
-- UPDATE products p
-- JOIN rebrand_backup_products_20260828 b ON p.id = b.id
-- SET p.sku = b.sku;

-- To restore vendor names/slugs:
-- UPDATE vendors v
-- JOIN rebrand_backup_vendors_20260828 b ON v.id = b.id
-- SET v.store_name = b.store_name, v.slug = b.slug;

-- To restore user emails:
-- UPDATE users u
-- JOIN rebrand_backup_users_20260828 b ON u.id = b.id
-- SET u.email = b.email;

-- End of migration script
