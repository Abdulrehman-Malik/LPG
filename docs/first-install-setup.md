# First-Time Database Installation

## Normal operation

Keep this setting disabled in the deployment .env file:

    FRESH_DATABASE_SETUP = false

The installer does not run while this flag is false. Regular login and the existing Transactional Data Refresh feature remain separate.

## Deploying a new customer

1. Configure the target database host, username, password, port, and database name in .env (database.default.* or the supported LPG_DB_* variables).
2. Ensure the MySQL account has permission to create the configured database and create/alter tables. For replacement, it also needs permission to drop that database.
3. Set FRESH_DATABASE_SETUP = true for the initial setup window.
4. Open the application login URL. If setup has not completed, the login page redirects to Installation Setup.
5. Review the target database name and submit INSTALL DATABASE.
6. The installer creates the database if it does not exist, executes database/schema.sql, runs pending numbered SQL migrations in sequence, and records completion only after the migrations succeed.
7. Confirm that login works, then set FRESH_DATABASE_SETUP = false.

## Replacing an existing database

**Replacement permanently deletes all tables and records in the configured database.** The installer requires all of the following before replacement:

- The existing database has an active user with the ADMIN role.
- The submitted password matches that administrator's stored password hash.
- The operator types REPLACE DATABASE and INSTALL DATABASE and confirms the browser warning.
- A verified SQL backup is created successfully before the database is dropped.

If the existing database has tables but no verifiable active administrator, replacement is blocked. If backup creation fails, the existing database is not dropped.

## Migration behavior

- Schema creation runs before numbered migrations.
- Migrations are processed in ascending sequence order.
- Migration history and checksums use the existing migration runner.
- A failed migration blocks completion and login; fix the cause and retry.
- Migration 011_20261003_refresh_transactional_data.sql remains excluded from automatic execution because it is a separate destructive refresh workflow.

## Important operational notes

- The schema script is intended for an empty database. It no longer creates/selects a hard-coded database or drops tables itself; the installer controls database creation and replacement.
- Do not leave FRESH_DATABASE_SETUP = true enabled after deployment. The completion marker prevents automatic re-entry on each login, but the installation route remains available while the flag is enabled.
- Keep backups outside the database server when possible and periodically test restore procedures.
