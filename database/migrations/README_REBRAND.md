Rebrand DB Migration — Swagbag

Purpose
- Safe, minimal migration to replace DS- SKU prefixes and vendor store names referencing "David" with Swagbag equivalents.

Important notes
- Always take a full database backup (mysqldump or your hosting backup) before applying any migration.
- The provided SQL creates small backup tables that capture the pre-change values for easy rollback.
- Do NOT uncomment the user-email replacement unless you have verified the affected rows are seed/demo accounts.

How to run (CLI)
1. SSH to your DB host or use a local client with access to the database.
2. From the project root run (replace placeholders):

```bash
# dump a full backup first (recommended)
mysqldump -u DB_USER -p DB_NAME > db-backup-$(date +%F).sql

# run migration (example)
mysql -u DB_USER -p DB_NAME < database/migrations/20260828_rebrand_swagbag.sql
```

3. After running, verify counts and sample rows using the SELECT queries in the migration file.

How to run (phpMyAdmin)
- Import `database/migrations/20260828_rebrand_swagbag.sql` using phpMyAdmin's Import tab. Review the import progress and then run the verification SELECTs.

Rollback
- If you need to rollback the SKU and vendor changes, use the commented rollback UPDATEs near the bottom of the SQL file. They restore values from the `rebrand_backup_*_20260828` tables.
- If anything goes wrong, restore the full dump you created earlier.

Post-migration checklist
- Check admin, vendor, and customer views for correct branding and SKU formats.
- Run a test checkout and ensure SKUs show correctly in emails and orders.
- If you changed emails, verify logins and reset flows for affected users.

Questions or assistance
- If you'd like, I can apply the migration to a development copy of your DB here (if you provide a dump), run verification, and produce a post-migration report.
