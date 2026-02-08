# Archived SQL Fixtures

**Status:** DEPRECATED as of 2026-02-08

## Why Archived?

These SQL fixtures have been replaced with programmatic Phinx migration execution in integration tests. The fixtures are no longer used and should not be updated.

### Problems with SQL Fixtures

1. **Schema Drift**: SQL fixtures were missing 2 recent Phinx migrations:
   - `20260131000000_add_last_logout_column.php` (adds `users.last_logout`)
   - `20260201000000_make_display_name_nullable.php` (makes `crews.display_name` nullable)

2. **Duplicate Maintenance**: Required updating both Phinx migrations AND SQL fixtures for every schema change

3. **Fragile Parsing**: Manual SQL parsing with comment stripping and error suppression in test files was error-prone:
   ```php
   // 50+ lines of duplicated SQL parsing code in every test
   $lines = explode("\n", $sql);
   foreach ($lines as $line) {
       // Complex comment stripping...
       // Statement parsing...
       try { $pdo->exec($statement); } catch (\PDOException $e) { /* ignore */ }
   }
   ```

4. **Error Suppression**: Catching and ignoring all PDOExceptions hid real schema issues

### New Approach

Integration tests now extend `Tests\Integration\IntegrationTestCase` which:
- Runs **ALL** Phinx migrations programmatically using the Phinx Manager API
- Always uses the latest schema from `database/migrations/*.php`
- Provides single source of truth for database schema
- Eliminates ~240 lines of duplicated SQL parsing code across 4 test files

**Example:**
```php
use Tests\Integration\IntegrationTestCase;

class MyIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();  // Runs all Phinx migrations + initializes season config

        // Your test-specific setup
        $this->myRepository = new MyRepository();
    }
}
```

## Files in This Directory

| File | Purpose | Replaced By |
|------|---------|-------------|
| `001_initial_schema.sql` | Initial database schema (boats, crews, events, etc.) | `database/migrations/20260101000000_initial_schema.php` |
| `002_add_users_authentication.sql` | User authentication tables and foreign keys | `database/migrations/20260130000000_add_users_authentication.php` |

### Missing Migrations

These migrations existed in `database/migrations/` but were **NOT** included in the SQL fixtures, causing schema drift:

| Migration | Description | Impact |
|-----------|-------------|--------|
| `20260131000000_add_last_logout_column.php` | Adds `users.last_logout` column | Tests had manual `ALTER TABLE` workarounds |
| `20260201000000_make_display_name_nullable.php` | Makes `crews.display_name` nullable | Tests couldn't insert crews without display_name |

## Historical Reference

These files are kept for:
- **Git history preservation** - Shows schema evolution over time
- **Rollback safety net** - In case Phinx approach needs to be reverted
- **Documentation** - Demonstrates the problem that led to refactoring

**⚠️ DO NOT UPDATE THESE FILES**

All schema changes must go in Phinx migration files under `database/migrations/`.

## Migration Details

The refactoring (2026-02-08) involved:

**Created:**
- `tests/Integration/IntegrationTestCase.php` - Base class with Phinx Manager integration
- `tests/Unit/Integration/IntegrationTestCaseTest.php` - Tests for base class

**Refactored:**
- `tests/Integration/Application/UseCase/Crew/UpdateCrewAvailabilityUseCaseTest.php` (~60 lines removed)
- `tests/Integration/Application/UseCase/Boat/UpdateBoatAvailabilityUseCaseTest.php` (~60 lines removed)
- `tests/Integration/Application/UseCase/Season/ProcessSeasonUpdateUseCaseTest.php` (~50 lines removed)
- `tests/Integration/Application/UseCase/User/UpdateUserProfileUseCaseTest.php` (~70 lines removed)

**Total Impact:**
- ~240 lines of duplicate SQL parsing code removed
- All integration tests now use latest schema (4 migrations)
- Single source of truth for database schema
- Better error messages from Phinx

## Questions?

See the main project documentation:
- [CLAUDE.md](../../CLAUDE.md) - Technical specifications for AI assistants
- [docs/DEVELOPER_GUIDE.md](../../docs/DEVELOPER_GUIDE.md) - Developer documentation

Or review the implementation:
- `tests/Integration/IntegrationTestCase.php` - Base class implementation
- `phinx.php` - Phinx configuration with `testing` environment
