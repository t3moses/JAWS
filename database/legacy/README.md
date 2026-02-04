# Legacy Database Scripts

This folder contains deprecated database migration and seeding scripts that have been replaced by Phinx.

## Deprecated Files

### migrate.sh (Bash shell script)
**Replaced by:** `vendor/bin/phinx migrate`

**Reason:**
- Not cross-platform (requires Bash)
- Manual migration tracking
- No rollback support

### seed_test_data.php (PHP script)
**Replaced by:** `vendor/bin/phinx seed:run`

**Reason:**
- Converted to Phinx seeder class: `database/seeds/TestDataSeeder.php`
- Better integration with migration workflow
- Consistent command-line interface

## Migration to Phinx

### Before (Legacy)
```bash
# Initialize database
php database/init_database.php --yes

# Run migrations
database/migrate.sh

# Seed test data
php database/seed_test_data.php
```

### After (Phinx)
```bash
# Run all migrations
vendor/bin/phinx migrate

# Seed test data
vendor/bin/phinx seed:run

# Check migration status
vendor/bin/phinx status

# Rollback last migration
vendor/bin/phinx rollback
```

## Archived SQL Migrations

Original SQL migration files have been moved to:
- `database/migrations/archive/001_initial_schema.sql`
- `database/migrations/archive/002_add_users_authentication.sql`

These have been converted to Phinx PHP migration classes:
- `database/migrations/20260101000000_initial_schema.php`
- `database/migrations/20260130000000_add_users_authentication.php`

## Why Keep These Files?

These files are preserved for:
- Historical reference
- Understanding the original migration logic
- Emergency rollback scenarios
- Documentation of migration evolution

## Do Not Use

These scripts are no longer maintained and should not be used in development or CI/CD pipelines.

Use Phinx commands instead (see above).
