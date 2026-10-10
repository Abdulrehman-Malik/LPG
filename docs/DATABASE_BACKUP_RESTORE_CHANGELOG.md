# Database Backup & Restore Change Log

## 2026-10-10 — Railway-compatible backup handling

### Changes committed directly to main
- Added a portable SQL backup path for deployments where `mysqldump` is not installed.
- Added a matching portable restore path for backups created by the portable exporter when the `mysql` client is unavailable.
- Kept the configured MySQL client path for environments such as local XAMPP, so a configured `mysqldump` / `mysql` pair can still be used.
- Added an authenticated download route for saved backup files.
- Updated the page to warn that Railway service files may be temporary and that backups should be downloaded or emailed to secure storage.
- Retained the pre-restore safety-backup behavior.

### Portable format scope and limits
- The portable exporter includes base-table definitions and row data.
- It does not export stored procedures, functions, triggers, events, or views. If these are added to the application database, configure the MySQL command-line tools for a full dump/restore.
- SQL restore can replace existing tables and data. The safety backup is created first, but a failed restore can still leave the database partially restored; keep an independent downloaded backup before restoring production data.
- Backups saved only inside a Railway service filesystem are not an independent durable backup. Download them or send them to a separately managed secure destination.

### Verification status
- Repository changes were committed to `main`.
- Automated PHP/runtime and live Railway database backup/restore verification have not been confirmed by this change log. Before relying on production restore, create a backup, download it, and test restore against a non-production database first.
