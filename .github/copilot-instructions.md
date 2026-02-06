# Copilot Instructions for JAWS

## Repository Overview

**JAWS** is a PHP REST API for boat fleet and crew assignment management using **Clean Architecture** (Domain → Application → Infrastructure → Presentation).

- **Tech**: PHP 8.1+ (CI: 8.5), SQLite, Phinx, PHPUnit (~15k lines, 350+ tests)
- **Critical**: SelectionService & AssignmentService algorithms must be preserved
- **Rule**: Outer layers depend on inner only. Domain has ZERO external dependencies.

## Critical Build Instructions

### 1. Install Dependencies

**CRITICAL**: If `composer install` fails with lock file errors, run `composer update` first:

```bash
composer update --no-interaction  # If lock file mismatch (PHP 8.4+ packages)
composer install --prefer-dist --no-progress --no-interaction  # Takes 2-3 min
```

### 2. Initialize Database

```bash
vendor/bin/phinx migrate     # Creates database/jaws.db (~200KB)
vendor/bin/phinx seed:run    # Optional: test data
```

### 3. Configure Environment

```bash
cp .env.example .env  # Set JWT_SECRET (min 32 chars, REQUIRED)
```

### 4. Run Development Server

```bash
php -S localhost:8000 -t public  # Required for API tests
curl http://localhost:8000/api/events  # Test
```

### 5. Run Tests

```bash
vendor/bin/phpunit Tests/Unit         # 346 tests, ~8s, no DB needed
vendor/bin/phpunit Tests/Integration  # 10 tests, needs DB

# API tests (start server first)
php -S localhost:8000 -t public > /dev/null 2>&1 & SERVER_PID=$!; sleep 2
php Tests/Integration/api_test.php -v
kill $SERVER_PID
```

**NEVER** run tests before `phinx migrate` - integration/API tests need the schema.

## Project Layout

### Directory Structure

```
src/
├── Domain/              # Pure business logic (NO external dependencies)
│   ├── Entity/          # Boat, Crew, Event
│   ├── Service/         # SelectionService, AssignmentService, RankingService (CRITICAL)
│   ├── ValueObject/     # BoatKey, CrewKey, EventId, Rank
│   └── Enum/            # AvailabilityStatus, SkillLevel, AssignmentRule
├── Application/         # Use cases & ports (depends on Domain only)
│   ├── UseCase/         # ProcessSeasonUpdateUseCase, UpdateAvailabilityUseCase
│   ├── Port/            # Repository & Service interfaces
│   └── DTO/             # Request/Response data transfer objects
├── Infrastructure/      # External adapters (DB, email, calendar)
│   ├── Persistence/     # SQLite repositories
│   └── Service/         # AwsSesEmailService, ICalendarService
└── Presentation/        # HTTP API layer
    ├── Controller/      # EventController, AvailabilityController
    ├── Middleware/      # JwtAuthMiddleware, ErrorHandlerMiddleware
    └── Router.php       # Route definitions

config/                  # DI container, routes, config
database/migrations/     # Phinx migration files (do NOT edit archive/)
Tests/{Unit,Integration}/  # PHPUnit test suites
public/                  # Web root (index.php, frontend app/)
```

### Key Files

**Config**: `composer.json`, `phinx.php`, `.env`, `config/{routes,container}.php`  
**Critical (preserve)**: `src/Domain/Service/{Selection,Assignment}Service.php`, `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php`

## CI/CD Pipeline

`.github/workflows/ci.yml` runs 5 parallel jobs: build, setup-database, unit-tests, integration-tests, api-tests. Uses PHP 8.5 (local: 8.1+).

**Common failures**: Composer lock out of sync (run `composer update`), missing DB migrations, server not started.

## Environment Requirements

**Required extensions**: pdo, pdo_sqlite, sqlite3, curl, mbstring, openssl  
**Optional**: Xdebug, LocalStack (for AWS SES testing: `cd LocalStack && docker-compose up -d`)

## Common Issues & Solutions

**Composer lock mismatch**: Run `composer update` then `composer install`  
**DB permission errors**: `chmod 775 database && chmod 664 database/jaws.db`  
**JWT 401 errors**: Verify `.env` has JWT_SECRET (min 32 chars)  
**Migration "already exists"**: Check `vendor/bin/phinx status`, rollback if needed  
**Port in use**: `lsof -ti:8000 | xargs kill -9` or use different port  
**Local test failures**: `rm database/jaws.db && vendor/bin/phinx migrate && rm -rf .phpunit.cache`

## Architecture Rules

**Layer boundaries**: Domain (no imports) ← Application ← Infrastructure, Presentation → Application only  
**Wrong**: Domain importing PDO/repositories. **Right**: Application ports, Infrastructure implements.

**Critical algorithms**: DO NOT modify core logic in Selection/AssignmentService. Add features around them. ALWAYS run `Tests/Unit/Domain/SelectionServiceTest.php` after changes. Verify deterministic output.

## Pre-PR Checklist

1. `vendor/bin/phpunit` passes
2. `vendor/bin/phinx status` shows all "up"
3. API responds locally (`curl http://localhost:8000/api/events`)
4. GitHub Actions passes (5 jobs)
5. Code follows PSR-12 (4 spaces, strict types, type hints)

Update docs if needed: `README.md`, `CLAUDE.md`, `docs/{DEVELOPER_GUIDE,API,CONTRIBUTING}.md`

## Quick Reference Commands

```bash
# Setup from scratch
composer update && composer install
vendor/bin/phinx migrate
cp .env.example .env && nano .env  # Set JWT_SECRET

# Development workflow
php -S localhost:8000 -t public &           # Start server
vendor/bin/phpunit Tests/Unit               # Quick test
vendor/bin/phinx create MyMigrationName     # New migration
vendor/bin/phinx migrate                    # Apply migrations

# Database operations
vendor/bin/phinx rollback                   # Undo last migration
vendor/bin/phinx status                     # Check migration status
sqlite3 database/jaws.db "SELECT * FROM boats LIMIT 5"  # Query DB

# Cleanup
rm database/jaws.db && vendor/bin/phinx migrate  # Reset DB
rm -rf vendor && composer install           # Reinstall deps
```

## Trust These Instructions

These instructions have been verified by running all commands on a clean clone of the repository. If you encounter issues not documented here, it's likely a new problem - investigate and update this file with your findings.
