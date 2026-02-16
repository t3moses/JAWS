# Copilot Instructions for JAWS

## Repository Overview

**JAWS** is a PHP REST API for boat fleet and crew assignment management using **Clean Architecture** (Domain → Application → Infrastructure → Presentation).

- **Tech**: PHP 8.1+ (CI: 8.5), SQLite, Phinx, PHPUnit (~15k lines, 350+ tests)
- **Critical**: SelectionService & AssignmentService algorithms must be preserved
- **Rule**: Outer layers depend on inner only. Domain has ZERO external dependencies.

## Critical Build Instructions

### 1. Install Dependencies

```bash
composer install --prefer-dist --no-progress --no-interaction
```

**If using PHP < 8.4**: The lock file requires PHP 8.4+ (matching CI). Use `--ignore-platform-reqs` if needed:

```bash
composer install --prefer-dist --no-progress --no-interaction --ignore-platform-reqs
```

**NEVER run `composer update`** - it will break CI by downgrading packages to match your local PHP version.

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
# Unit tests (fast, no DB needed)
vendor/bin/phpunit tests/Unit         # 346 tests, ~8s

# Integration tests (require DB)
vendor/bin/phpunit tests/Integration  # 10 tests

# All tests
vendor/bin/phpunit                    # Runs all test suites

# Specific test file
vendor/bin/phpunit tests/Unit/Domain/SelectionServiceTest.php

# API tests (start server first)
php -S localhost:8000 -t public > /dev/null 2>&1 & SERVER_PID=$!; sleep 2
php tests/Integration/api_test.php -v
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
tests/{Unit,Integration}/  # PHPUnit test suites
public/                  # Web root (index.php, frontend app/)
```

### Key Files

**Config**: `composer.json`, `phinx.php`, `.env`, `config/{routes,container}.php`  
**Critical (preserve)**: `src/Domain/Service/{Selection,Assignment}Service.php`, `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php`

## CI/CD Pipeline

`.github/workflows/ci.yml` runs 5 parallel jobs: build, setup-database, unit-tests, integration-tests, api-tests. Uses PHP 8.5 (local: 8.1+).

**Common failures**: Missing DB migrations, server not started. DO NOT modify composer.lock unless using PHP 8.4+.

## Environment Requirements

**Required extensions**: pdo, pdo_sqlite, sqlite3, curl, mbstring, openssl
**Optional**: Xdebug (for debugging), MailHog (for local email testing)

## Common Issues & Solutions

**Composer install fails (PHP < 8.4)**: Use `composer install --ignore-platform-reqs` - lock file requires PHP 8.4+  
**DB permission errors**: `chmod 775 database && chmod 664 database/jaws.db`  
**JWT 401 errors**: Verify `.env` has JWT_SECRET (min 32 chars)  
**Migration "already exists"**: Check `vendor/bin/phinx status`, rollback if needed  
**Port in use**: `lsof -ti:8000 | xargs kill -9` or use different port  
**Local test failures**: `rm database/jaws.db && vendor/bin/phinx migrate && rm -rf .phpunit.cache`

## Architecture Rules

**Layer boundaries**: Domain (no imports) ← Application ← Infrastructure, Presentation → Application only  
**Wrong**: Domain importing PDO/repositories. **Right**: Application ports, Infrastructure implements.

**Critical algorithms**: DO NOT modify core logic in Selection/AssignmentService. Add features around them. ALWAYS run `tests/Unit/Domain/SelectionServiceTest.php` after changes. Verify deterministic output.

## Code Quality

**Style**: Follow PSR-12 (4 spaces, strict types, type hints)

**Testing**: Write tests for new features. Maintain existing test coverage.
- Unit tests for Domain layer (pure business logic)
- Integration tests for Infrastructure layer (database interactions)
- API tests for Presentation layer (HTTP endpoints)

**Safety**: Preserve existing functionality. If unsure, ask before modifying critical code.

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
composer install --prefer-dist --no-progress --no-interaction
vendor/bin/phinx migrate
cp .env.example .env && nano .env  # Set JWT_SECRET

# Development workflow
php -S localhost:8000 -t public &           # Start server
vendor/bin/phpunit tests/Unit               # Quick test
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

## Commit Message Format

This project uses **Conventional Commits** specification. Always follow this format:

```
<type>[optional scope]: <description>
```

**Types**: feat, fix, docs, style, refactor, perf, test, build, ci, chore

**Examples**:
```bash
feat: add crew notes field to database schema
fix: prevent duplicate crew assignments on same boat
docs: update API documentation for availability endpoint
test: add integration tests for AssignmentService
```

**Rules**:
- Use lowercase for type and description
- No period at end of description
- Use imperative mood ("add" not "added")
- Keep description under 72 characters

## Documentation

For detailed information, see:
- **README.md** - Project overview and quick start
- **docs/SETUP.md** - Installation guide for new developers
- **docs/DEVELOPER_GUIDE.md** - Architecture and development workflow
- **docs/API.md** - Complete API endpoint documentation
- **docs/CONTRIBUTING.md** - Code style and Git workflow
- **CLAUDE.md** - Extended technical specifications for AI assistants

## Trust These Instructions

These instructions have been verified by running all commands on a clean clone of the repository. If you encounter issues not documented here, it's likely a new problem - investigate and update this file with your findings.
