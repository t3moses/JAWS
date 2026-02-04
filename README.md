# JAWS - Social Day Cruising Program Management System

JAWS is a web application for managing the Social Day Cruising program at Nepean Sailing Club. It handles boat fleet management, crew registration, and intelligent assignment of crew members to boats for seasonal sailing events.

The system uses a sophisticated optimization algorithm to match crew to boats based on multiple constraints including skill levels, availability, preferences, historical participation, and boat requirements.

## Table of Contents

- [What This Project Does](#what-this-project-does)
- [Prerequisites](#prerequisites)
- [Project Setup](#project-setup)
  - [Local Development Setup](#local-development-setup)
  - [AWS Lightsail Setup](#aws-lightsail-setup)
  - [LocalStack Setup (for testing AWS services locally)](#localstack-setup-for-testing-aws-services-locally)
- [Testing](#testing)
  - [Running Tests](#running-tests)
  - [Writing New Tests](#writing-new-tests)
- [Database Schema Changes](#database-schema-changes)
- [Clean Architecture](#clean-architecture)
  - [What is Clean Architecture?](#what-is-clean-architecture)
  - [Why Clean Architecture for JAWS?](#why-clean-architecture-for-jaws)
  - [Layer Overview](#layer-overview)
- [Development Workflow](#development-workflow)
- [API Documentation](#api-documentation)
- [Deployment](#deployment)

---

## What This Project Does

JAWS automates the complex task of assigning sailing crew to boats for weekly social sailing events. Here's how it works:

### Core Features

1. **Boat Registration**: Boat owners register their boats with capacity (min/max berths), assistance requirements, and availability for each event
2. **Crew Registration**: Sailors register as crew with their skill level (Novice/Intermediate/Advanced), partner preferences, and availability
3. **Multi-Dimensional Ranking**: The system ranks boats and crews using multiple criteria:
   - **Boats**: Flexibility (owner also registered as crew) and absence history
   - **Crews**: Commitment (availability for next event), flexibility (also owns a boat), membership status, and absence history
4. **Intelligent Assignment**: An optimization algorithm assigns crew to boats while minimizing rule violations:
   - ASSIST: Boats requiring assistance get appropriate crew
   - WHITELIST: Crew assigned to their preferred boats when possible
   - HIGH_SKILL / LOW_SKILL: Balanced skill distribution across boats
   - PARTNER: Keep requested partnerships together
   - REPEAT: Minimize crew repeating the same boat
5. **Deterministic Behavior**: Same inputs always produce identical assignments (using seeded randomization)
6. **Blackout Windows**: Prevents registration changes during event hours
7. **History Tracking**: Maintains participation history to dynamically adjust rankings

### The Assignment Pipeline

After every user input (registration or availability update), the system runs this pipeline for all future events:

1. **Selection Phase**: Ranks and selects boats/crews based on multi-dimensional criteria
2. **Consolidation Phase**: Forms selected boats and crews into a structured "flotilla"
3. **Assignment Optimization**: For the next event only, performs constraint-based crew swapping to minimize rule violations
4. **Persistence**: Saves updated fleet/squad data and flotilla assignments to database
5. **Output Generation**: Renders flotilla tables to HTML and generates calendar files

---

## Prerequisites

Before setting up the project, ensure you have the following installed:

### Required Software

1. **PHP 8.1 or higher**
   - Check version: `php -v`
   - Download from: https://www.php.net/downloads

2. **Composer** (PHP dependency manager)
   - Check version: `composer -v`
   - Download from: https://getcomposer.org/download/

3. **SQLite 3** (usually comes with PHP)
   - Check: `php -m | grep sqlite`
   - Should see `pdo_sqlite` and `sqlite3`

4. **Git** (for version control)
   - Check version: `git --version`
   - Download from: https://git-scm.com/downloads

### Required PHP Extensions

Ensure these extensions are enabled in your `php.ini`:

```ini
extension=pdo_sqlite
extension=sqlite3
extension=curl
extension=mbstring
extension=openssl
```

To check enabled extensions: `php -m`

### Optional but Recommended

- **SQLite Browser** (for database inspection): https://sqlitebrowser.org/
- **Postman** (for API testing): https://www.postman.com/downloads/
- **PHPUnit** (installed via Composer for testing)

---

## Project Setup

### Local Development Setup

#### 1. Clone the Repository

```bash
git clone <repository-url>
cd JAWS
```

#### 2. Install Dependencies

Install PHP dependencies using Composer:

```bash
composer install
```

This will install:
- `phpmailer/phpmailer` - Email service via AWS SES
- `eluceo/ical` - iCalendar file generation
- `phpunit/phpunit` (dev) - Testing framework

#### 3. Initialize the Database

The project uses Phinx for database migrations. Create and populate the SQLite database:

```bash
# Run all migrations (creates database and applies schema)
vendor/bin/phinx migrate

# Optional: Seed test data
vendor/bin/phinx seed:run
```

This will:
- Create `database/jaws.db` (SQLite database file)
- Create the `phinxlog` table to track migrations
- Apply all pending migrations in order (currently 4 migrations)
- Set up the complete database schema with 10 tables

**Expected Output:**
```
Phinx by CakePHP - https://phinx.org.

using config file ./phinx.php
using config parser php
using migration paths
 - /path/to/JAWS/database/migrations
using seed paths

 == 20260101000000 InitialSchema: migrating
 == 20260101000000 InitialSchema: migrated (0.0234s)

 == 20260130000000 AddUsersAuthentication: migrating
 == 20260130000000 AddUsersAuthentication: migrated (0.0187s)

All Done. Took 0.0421s
```

**Note:** For historical reference, the legacy `database/legacy/init_database.php` script has been preserved but is no longer used.

For more information about database management, see [database/README.md](database/README.md).

#### 4. Configure Environment Variables

Create a `.env` file in the project root for custom configuration:

```bash
cp .env.example .env
# Edit .env with your values
```

**Available Configuration Options:**

```bash
# Database
DB_PATH=database/jaws.db

# JWT Authentication (required)
JWT_SECRET=your-secret-key-minimum-32-characters-long
JWT_EXPIRATION_MINUTES=60

# AWS SES (Email Service)
SES_REGION=ca-central-1
SES_SMTP_USERNAME=your_smtp_username
SES_SMTP_PASSWORD=your_smtp_password
# SES_ENDPOINT=http://localhost:4566  # Uncomment for LocalStack
EMAIL_FROM=noreply@nsc-sdc.ca
EMAIL_FROM_NAME="Nepean Sailing Club - Social Day Cruising"

# Application
APP_DEBUG=true
APP_ENV=development
APP_TIMEZONE=America/Toronto

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080
```

**Important Notes:**
- `JWT_SECRET` is **required** and must be at least 32 characters
- For LocalStack development, use `.env.localstack` as a template
- For production, set `APP_DEBUG=false` and use strong credentials

**Note:** If you don't create a `.env` file, the application will use default values from `config/config.php`.

#### 5. Start a Development Server

PHP has a built-in web server for development:

```bash
# Start server on port 8000
php -S localhost:8000 -t public

# Or on a different port
php -S localhost:3000 -t public
```

The API will be available at: `http://localhost:8000/api`

#### 6. Verify Installation

Test the API is working:

```bash
# Using curl
curl http://localhost:8000/api/events

# Or visit in browser
open http://localhost:8000/api/events
```

Expected response:
```json
{
  "success": true,
  "data": {
    "events": []
  }
}
```

#### 7. (Optional) Migrate Legacy Data

If you have data in the legacy CSV format, migrate it to SQLite:

```bash
# First, backup CSV files
cp legacy/Libraries/Fleet/data/fleet_data.csv legacy/Libraries/Fleet/data/fleet_data.backup.csv
cp legacy/Libraries/Squad/data/squad_data.csv legacy/Libraries/Squad/data/squad_data.backup.csv

# Run migration
php database/migrate_from_csv.php
```

---

### AWS Lightsail Setup

Deploy JAWS to AWS Lightsail (production environment).

#### Prerequisites

1. AWS Lightsail instance running (current: 16.52.222.15)
2. SSH key file: `LightsailDefaultKey-ca-central-1.pem`
3. Apache/Bitnami stack installed on Lightsail
4. AWS SES configured for email sending

#### Deployment Steps

##### 1. Add SSH Key to Agent

```bash
ssh-add LightsailDefaultKey-ca-central-1.pem
```

##### 2. Upload Files via SFTP

```bash
sftp bitnami@16.52.222.15
cd /./var/www/html
put public/index.php
put -r src
put -r config
put composer.json
put composer.lock
bye
```

**Tip:** Upload changed files only. For bulk uploads, use wildcards:
```bash
put -r src/Domain/*.php
```

##### 3. Set File Permissions

After uploading, SSH into the server and set permissions:

```bash
ssh bitnami@16.52.222.15

# Set ownership and permissions for PHP files
cd /var/www/html
sudo chgrp -R www-data src config public
sudo chmod -R 750 src config
sudo chmod 644 public/index.php
sudo chmod 644 public/.htaccess
```

##### 4. Upload and Configure Database

```bash
# Upload database file
sftp bitnami@16.52.222.15
cd /./var/www/html/database
put jaws.db
bye

# Set database permissions
ssh bitnami@16.52.222.15
cd /var/www/html/database
sudo chgrp www-data jaws.db
sudo chmod 664 jaws.db
sudo chgrp www-data .
sudo chmod 775 .
```

**Important:** The database directory must be writable by `www-data` for SQLite to create journal files.

##### 5. Install Composer Dependencies

```bash
ssh bitnami@16.52.222.15
cd /var/www/html
composer install --no-dev --optimize-autoloader
```

The `--no-dev` flag excludes development dependencies (like PHPUnit) from production.

##### 6. Configure Environment Variables

Create `.env` file on the server:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html
nano .env
```

Add production configuration:
```bash
DB_PATH=/var/www/html/database/jaws.db
SES_REGION=ca-central-1
SES_SMTP_USERNAME=<your-ses-username>
SES_SMTP_PASSWORD=<your-ses-password>
EMAIL_FROM=noreply@nsc-sdc.ca
EMAIL_FROM_NAME=Nepean Sailing Club - Social Day Cruising
APP_DEBUG=false
APP_URL=https://nsc-sdc.com
```

Save and exit (Ctrl+X, Y, Enter).

##### 7. Verify Apache Configuration

Ensure Apache is configured to route all requests to `public/index.php`. The `public/.htaccess` file should contain:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

##### 8. Test Deployment

```bash
curl https://your-domain.com/api/events
```

##### 9. Download Production Data (Backup)

To backup production data to your local machine:

```bash
#!/bin/bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15 <<EOF
cd var/www/html/database
get jaws.db
get jaws.db.backup.*
bye
EOF
```

---

### LocalStack Setup (for testing AWS services locally)

JAWS uses AWS SDK for PHP to send emails via AWS Simple Email Service (SES). For local development and testing, LocalStack emulates AWS SES without sending real emails or incurring costs.

#### Quick Start

```bash
# Start LocalStack
docker-compose -f LocalStack/docker-compose.yml up -d

# Verify email address (required once)
php LocalStack/verify_email.php

# Start development server
php -S localhost:8000 -t public

# Test sending notifications (requires JWT token)
# First, login to get a token:
# curl -X POST "http://localhost:8000/api/auth/login" -H "Content-Type: application/json" -d '{"email":"admin@example.com","password":"your_password"}'

curl -X POST "http://localhost:8000/api/admin/notifications/Fri%20May%2029" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"

# View sent emails in logs
docker-compose logs -f localstack
```

#### Complete Documentation

For comprehensive setup instructions, configuration details, troubleshooting, and switching between LocalStack and production AWS SES, see:

**[LocalStack/LOCALSTACK_SETUP.md](LocalStack/LOCALSTACK_SETUP.md)**

The documentation includes:

- Prerequisites and Docker Desktop setup
- Email address verification for LocalStack
- Environment variable configuration
- Testing email functionality
- Troubleshooting common issues
- Production deployment configuration

---

## Testing

### Running Tests

JAWS uses PHPUnit for testing. Tests are organized into Unit and Integration tests.

#### Run All Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Or using composer script (if configured)
composer test
```

#### Run Specific Test Suites

```bash
# Run only unit tests
./vendor/bin/phpunit Tests/Unit

# Run only integration tests
./vendor/bin/phpunit Tests/Integration

# Run Domain layer tests only
./vendor/bin/phpunit Tests/Unit/Domain

# Run a specific test file
./vendor/bin/phpunit Tests/Unit/Domain/SelectionServiceTest.php
```

#### Domain Layer Test Coverage

The Domain layer has comprehensive unit test coverage:

- **Value Objects**: [BoatKey](Tests/Unit/Domain/ValueObject/BoatKeyTest.php), [CrewKey](Tests/Unit/Domain/ValueObject/CrewKeyTest.php), [EventId](Tests/Unit/Domain/ValueObject/EventIdTest.php), [Rank](Tests/Unit/Domain/ValueObject/RankTest.php)
- **Enums**: [AvailabilityStatus](Tests/Unit/Domain/Enum/AvailabilityStatusTest.php), [SkillLevel](Tests/Unit/Domain/Enum/SkillLevelTest.php), [BoatRankDimension](Tests/Unit/Domain/Enum/BoatRankDimensionTest.php), [CrewRankDimension](Tests/Unit/Domain/Enum/CrewRankDimensionTest.php)
- **Entities**: [Boat](Tests/Unit/Domain/Entity/BoatTest.php), [Crew](Tests/Unit/Domain/Entity/CrewTest.php)
- **Collections**: [Fleet](Tests/Unit/Domain/Collection/FleetTest.php), [Squad](Tests/Unit/Domain/Collection/SquadTest.php)
- **Services**: [RankingService](Tests/Unit/Domain/Service/RankingServiceTest.php), [FlexService](Tests/Unit/Domain/Service/FlexServiceTest.php)

These tests are pure unit tests with **no external dependencies** (no database, no API calls), making them fast and reliable.

#### Run Tests with Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage
```

This generates an HTML coverage report in the `coverage/` directory.

#### Run API Tests

There's a simple API test script for manual testing:

```bash
# Make sure your dev server is running
php -S localhost:8000 -t public &

# Run API tests
php Tests/Integration/api_test.php
```

Expected output:
```
=================================
JAWS API Test Suite
=================================

Test: GET /api/events
✓ PASSED

Test: GET /api/events/{id}
✓ PASSED

...

=================================
Test Results
=================================
Passed: 7
Failed: 0
Total:  7

✓ All tests passed!
```

#### Test with Postman

Import the Postman collection:

1. Open Postman
2. File → Import
3. Select `Tests/JAWS_API.postman_collection.json`
4. Update the `baseUrl` variable to your local server
5. Run the collection

---

### Writing New Tests

#### Unit Test Example

Unit tests test individual classes in isolation. They should be fast and have no external dependencies (no database, no API calls).

**Location:** `Tests/Unit/Domain/`

**Example:** Testing the SelectionService

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use App\Domain\Service\SelectionService;
use App\Domain\Entity\Boat;
use App\Domain\Entity\Crew;
use App\Domain\ValueObject\EventId;

class SelectionServiceTest extends TestCase
{
    private SelectionService $selectionService;

    protected function setUp(): void
    {
        $this->selectionService = new SelectionService();
    }

    public function testDeterministicShuffle(): void
    {
        // Arrange
        $eventId = new EventId('Fri May 29');
        $boats = $this->createTestBoats(10);

        // Act
        $result1 = $this->selectionService->shuffle($boats, $eventId);
        $result2 = $this->selectionService->shuffle($boats, $eventId);

        // Assert - same seed should produce same order
        $this->assertEquals($result1, $result2);
    }

    public function testLexicographicRankComparison(): void
    {
        // Arrange
        $crew1 = $this->createCrew('john', [0, 1, 0, 0]); // Better rank
        $crew2 = $this->createCrew('jane', [1, 1, 0, 0]); // Worse rank

        // Act
        $isGreater = $this->selectionService->isGreater(
            $crew1->getRank(),
            $crew2->getRank()
        );

        // Assert
        $this->assertFalse($isGreater, 'Crew1 should have better (lower) rank');
    }

    public function testCapacityMatchingCase1TooFewCrews(): void
    {
        // Arrange
        $boats = $this->createTestBoats(5); // 5 boats needing 2 crew each = 10 spots
        $crews = $this->createTestCrews(7); // Only 7 crew available

        // Act
        $result = $this->selectionService->cut($boats, $crews);

        // Assert
        $this->assertCount(3, $result['crewed_boats'], 'Should crew 3 boats (6 crew)');
        $this->assertCount(2, $result['waitlist_boats'], 'Should have 2 boats on waitlist');
        $this->assertCount(1, $result['waitlist_crews'], 'Should have 1 crew on waitlist');
    }

    private function createTestBoats(int $count): array
    {
        $boats = [];
        for ($i = 0; $i < $count; $i++) {
            $boats[] = new Boat(/* ... */);
        }
        return $boats;
    }

    private function createTestCrews(int $count): array
    {
        // Similar to createTestBoats
    }

    private function createCrew(string $name, array $rank): Crew
    {
        // Helper to create crew with specific rank
    }
}
```

**Run this test:**
```bash
./vendor/bin/phpunit Tests/Unit/Domain/SelectionServiceTest.php
```

#### Integration Test Example

Integration tests test how components work together, including database interactions.

**Location:** `Tests/Integration/Infrastructure/`

**Example:** Testing the BoatRepository

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Persistence\SQLite\Connection;
use App\Infrastructure\Persistence\SQLite\BoatRepository;
use App\Domain\Entity\Boat;
use App\Domain\ValueObject\BoatKey;

class BoatRepositoryTest extends TestCase
{
    private BoatRepository $repository;
    private \PDO $pdo;

    protected function setUp(): void
    {
        // Use in-memory SQLite for tests
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Apply schema
        $schema = file_get_contents(__DIR__ . '/../../../database/migrations/001_initial_schema.sql');
        $this->pdo->exec($schema);

        // Inject test database connection
        Connection::setTestConnection($this->pdo);

        $this->repository = new BoatRepository();
    }

    protected function tearDown(): void
    {
        Connection::resetTestConnection();
    }

    public function testSaveAndFindBoat(): void
    {
        // Arrange
        $boat = new Boat(
            new BoatKey('sailaway'),
            'Sail Away',
            'John',
            'Doe',
            'john@example.com',
            '555-1234',
            1,
            3,
            false,
            false
        );

        // Act
        $this->repository->save($boat);
        $found = $this->repository->findByKey(new BoatKey('sailaway'));

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals('Sail Away', $found->getDisplayName());
        $this->assertEquals('john@example.com', $found->getOwnerEmail());
    }

    public function testUpdateBoatAvailability(): void
    {
        // Arrange
        $boat = $this->createAndSaveBoat();
        $eventId = new EventId('Fri May 29');

        // Act
        $this->repository->setAvailability($boat->getKey(), $eventId, 2);
        $berths = $this->repository->getAvailability($boat->getKey(), $eventId);

        // Assert
        $this->assertEquals(2, $berths);
    }

    private function createAndSaveBoat(): Boat
    {
        $boat = new Boat(/* ... */);
        $this->repository->save($boat);
        return $boat;
    }
}
```

**Run this test:**
```bash
./vendor/bin/phpunit Tests/Integration/Infrastructure/BoatRepositoryTest.php
```

#### Test Best Practices

1. **Arrange-Act-Assert Pattern**: Structure tests clearly
   ```php
   // Arrange - set up test data
   $input = createTestData();

   // Act - execute the code under test
   $result = $service->doSomething($input);

   // Assert - verify the result
   $this->assertEquals($expected, $result);
   ```

2. **One Assertion Per Test**: Each test should verify one behavior
   - Good: `testShuffleProducesSameOrderWithSameSeed()`
   - Bad: `testEverythingAboutShuffle()`

3. **Use Descriptive Names**: Test names should describe what they test
   ```php
   testCapacityMatchingCase1TooFewCrews()  // Good
   testCase1()                              // Bad
   ```

4. **Test Edge Cases**: Don't just test the happy path
   - Empty arrays
   - Null values
   - Boundary conditions (min/max values)
   - Invalid inputs

5. **Mock External Dependencies**: Use PHPUnit mocks for external services
   ```php
   $emailService = $this->createMock(EmailServiceInterface::class);
   $emailService->expects($this->once())
       ->method('send')
       ->with($this->equalTo('test@example.com'));
   ```

6. **Use In-Memory Database for Integration Tests**: Fast and isolated
   ```php
   $pdo = new \PDO('sqlite::memory:');
   ```

7. **Clean Up After Tests**: Use `tearDown()` to reset state
   ```php
   protected function tearDown(): void
   {
       Connection::resetTestConnection();
   }
   ```

---

## Database Schema Changes

The project uses [Phinx](https://phinx.org/) for database migrations. All database changes should be made through Phinx migration files.

### Creating a New Migration

```bash
# Create migration with descriptive name
vendor/bin/phinx create AddCrewNotes

# This creates: database/migrations/YYYYMMDDHHMMSS_add_crew_notes.php
```

### Writing a Migration

Migrations are PHP classes that define schema changes:

```php
<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCrewNotes extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('crews');
        $table->addColumn('notes', 'text', ['null' => true])
              ->update();
    }
}
```

### Applying Migrations

```bash
# Apply all pending migrations
vendor/bin/phinx migrate

# Rollback last migration
vendor/bin/phinx rollback

# Check migration status
vendor/bin/phinx status
```

### Update Domain Entities

After creating a migration, update the corresponding entity:

**File:** `src/Domain/Entity/Crew.php`

```php
class Crew
{
    private ?string $notes;

    public function __construct(
        private CrewKey $key,
        // ... other parameters
        ?string $notes = null
    ) {
        $this->notes = $notes;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }
}
```

### Update Repository Implementation

**File:** `src/Infrastructure/Persistence/SQLite/CrewRepository.php`

Update `save()` and `mapRowToCrew()` methods to handle the new field.

### Complete Workflow

1. Create migration: `vendor/bin/phinx create MyMigration`
2. Write migration code in generated PHP file
3. Update Domain entity class
4. Update Infrastructure repository
5. Update Application DTOs (if exposed via API)
6. Apply migration: `vendor/bin/phinx migrate`
7. Write tests for new functionality
8. Document changes

For detailed Phinx documentation and examples, see:
- [database/README.md](database/README.md)
- [Phinx Documentation](https://book.cakephp.org/phinx/0/en/migrations.html)

### Production Deployment

```bash
# Backup production database first
ssh bitnami@16.52.222.15
cd /var/www/html/database
sudo cp jaws.db jaws.backup.$(date +%Y%m%d_%H%M%S).db

# Apply migrations
cd /var/www/html
vendor/bin/phinx migrate --environment=production

# Verify
vendor/bin/phinx status --environment=production
```

---

## Clean Architecture

### What is Clean Architecture?

Clean Architecture (also called Hexagonal Architecture or Ports and Adapters) is a software design pattern that separates business logic from external concerns like databases, frameworks, and user interfaces.

**Core Principles:**

1. **Dependency Rule**: Dependencies point inward. Outer layers can depend on inner layers, but not vice versa.
2. **Independence**: Business logic doesn't know about databases, frameworks, or UIs
3. **Testability**: Inner layers can be tested without external dependencies
4. **Flexibility**: Swap implementations (e.g., SQLite → PostgreSQL) without changing business logic

**Layer Diagram:**

```
┌─────────────────────────────────────────┐
│   Presentation (HTTP/API)               │  Outer Layer
│   - Controllers                         │  (Delivery Mechanism)
│   - Middleware                          │
│   - Request/Response Formatting         │
└─────────────────────────────────────────┘
              ↓ depends on
┌─────────────────────────────────────────┐
│   Infrastructure (External Services)    │  Adapter Layer
│   - Database Repositories               │  (Connects to Outside World)
│   - Email Service                       │
│   - Calendar Service                    │
└─────────────────────────────────────────┘
              ↓ depends on
┌─────────────────────────────────────────┐
│   Application (Use Cases)               │  Use Case Layer
│   - UpdateBoatAvailabilityUseCase       │  (Application-Specific Rules)
│   - ProcessSeasonUpdateUseCase          │
│   - Repository Interfaces (Ports)       │
└─────────────────────────────────────────┘
              ↓ depends on
┌─────────────────────────────────────────┐
│   Domain (Business Logic)               │  Core Layer
│   - Entities (Boat, Crew)               │  (Business Rules)
│   - Value Objects (Rank, BoatKey)       │  (Framework-Independent)
│   - Domain Services (Selection)         │
│   - Business Rules                      │
└─────────────────────────────────────────┘
```

**Key Insight:** Dependency always points inward. The Domain layer has ZERO external dependencies.

---

### Why Clean Architecture for JAWS?

The JAWS codebase was refactored from a traditional PHP form-based application to Clean Architecture. Here's why:

#### Problems with the Old Architecture

The legacy codebase (`legacy/` folder) had these issues:

1. **Tight Coupling**: Business logic mixed with database access and HTML rendering
   ```php
   // Old code mixed everything together
   $fleet = new Fleet();  // Loads CSV
   foreach ($fleet->boats as $boat) {
       echo "<tr><td>{$boat->name}</td></tr>";  // HTML in business logic
   }
   ```

2. **Hard to Test**: Couldn't test business logic without CSV files and file I/O
3. **Hard to Change**: Switching from CSV to database required rewriting everything
4. **Hard to Understand**: 50+ interdependent files with unclear responsibilities
5. **No API**: Web forms only, no programmatic access

#### Benefits of Clean Architecture for JAWS

1. **Separation of Concerns**
   - Business logic (selection algorithm) is isolated and preserved
   - Database can be swapped (CSV → SQLite → PostgreSQL) without changing business rules
   - API can be added without touching core logic

2. **Testability**
   - Unit test domain services without database: `SelectionServiceTest`
   - Integration test repositories with in-memory SQLite
   - Mock external services (email) for testing

3. **Maintainability**
   - Clear layer boundaries (68 files in organized structure)
   - Each class has a single responsibility
   - Easy to find where to make changes

4. **Flexibility**
   - Started with SQLite, can migrate to PostgreSQL later
   - JWT authentication with token-based security
   - Can add GraphQL or gRPC without changing business logic

5. **Deployment Agnostic**
   - Same code works on Apache, Nginx, AWS Lambda, Docker
   - Framework-independent (no Laravel/Symfony lock-in)

6. **Algorithm Preservation**
   - Critical business logic (Selection, Assignment) preserved character-for-character
   - Deterministic behavior maintained
   - User trust preserved during migration

---

### Layer Overview

#### Layer 1: Domain (`src/Domain/`)

**Responsibility:** Core business logic and rules

**Dependencies:** None (pure PHP)

**What's in this layer:**

- **Entities** (`Entity/`): Core business objects
  - `Boat.php` - Boat with capacity, owner, ranking
  - `Crew.php` - Crew member with skills, preferences

- **Value Objects** (`ValueObject/`): Immutable values
  - `BoatKey.php`, `CrewKey.php` - Identifiers
  - `Rank.php` - Multi-dimensional rank tensor
  - `EventId.php` - Event identifier

- **Enums** (`Enum/`): Constants
  - `SkillLevel.php` - NOVICE, INTERMEDIATE, ADVANCED
  - `AvailabilityStatus.php` - UNAVAILABLE, AVAILABLE, GUARANTEED, WITHDRAWN
  - `AssignmentRule.php` - 6 optimization rules

- **Domain Services** (`Service/`): Business algorithms
  - `SelectionService.php` - Ranking and selection (CRITICAL ALGORITHM)
  - `AssignmentService.php` - Crew-to-boat optimization (CRITICAL ALGORITHM)
  - `RankingService.php` - Rank calculations
  - `FlexService.php` - Flex status detection

- **Collections** (`Collection/`): In-memory collections
  - `Fleet.php` - Boat collection
  - `Squad.php` - Crew collection

**Example:**

```php
// Domain entities have no knowledge of databases or APIs
$boat = new Boat(
    new BoatKey('sailaway'),
    'Sail Away',
    'John',
    'Doe',
    'john@example.com',
    '555-1234',
    1,  // min berths
    3,  // max berths
    false,  // assistance required
    false   // social preference
);

$rank = $boat->calculateRank();  // Pure business logic
```

---

#### Layer 2: Application (`src/Application/`)

**Responsibility:** Use cases and application logic

**Dependencies:** Domain layer only

**What's in this layer:**

- **Use Cases** (`UseCase/`): Application workflows
  - `ProcessSeasonUpdateUseCase.php` - Run the assignment pipeline
  - `GenerateFlotillaUseCase.php` - Generate flotilla for an event
  - `GetUserAssignmentsUseCase.php` - Get user's assignments
  - `UpdateBoatAvailabilityUseCase.php` - Update boat availability
  - `RegisterUseCase.php` - Register new user account
  - `LoginUseCase.php` - Authenticate user and generate JWT token
  - `GetUserProfileUseCase.php` - Get user profile
  - `AddUserProfileUseCase.php` - Create profile after registration
  - `UpdateUserProfileUseCase.php` - Update user profile

- **Ports/Interfaces** (`Port/`): Contracts for outer layers
  - `BoatRepositoryInterface.php` - How to persist boats (contract)
  - `EmailServiceInterface.php` - How to send emails (contract)
  - `TimeServiceInterface.php` - How to get current time (contract)

- **DTOs** (`DTO/`): Data transfer objects
  - Request: `UpdateAvailabilityRequest.php`
  - Response: `BoatResponse.php`, `FlotillaResponse.php`

- **Exceptions** (`Exception/`): Application errors
  - `BoatNotFoundException.php`
  - `ValidationException.php`
  - `BlackoutWindowException.php`

**Example:**

```php
// Use Case orchestrates domain logic and persistence
class UpdateBoatAvailabilityUseCase
{
    public function __construct(
        private BoatRepositoryInterface $boatRepository  // Port (interface)
    ) {}

    public function execute(string $ownerFirstName, string $ownerLastName, UpdateAvailabilityRequest $request): BoatResponse
    {
        // Find boat via repository
        $boat = $this->boatRepository->findByOwnerName($ownerFirstName, $ownerLastName);

        if (!$boat) {
            throw new BoatNotFoundException("Boat not found for owner");
        }

        // Update availabilities (via port - doesn't know if it's SQLite, PostgreSQL, etc.)
        foreach ($request->availabilities as $eventId => $berths) {
            $this->boatRepository->setAvailability($boat->getKey(), new EventId($eventId), $berths);
        }

        return BoatResponse::fromEntity($boat);
    }
}
```

**Key Insight:** Use Cases don't know about databases or APIs. They work with interfaces (ports).

---

#### Layer 3: Infrastructure (`src/Infrastructure/`)

**Responsibility:** External adapters (database, email, etc.)

**Dependencies:** Application + Domain layers

**What's in this layer:**

- **Persistence** (`Persistence/SQLite/`): Database implementations
  - `Connection.php` - SQLite connection manager
  - `BoatRepository.php` - Implements `BoatRepositoryInterface`
  - `CrewRepository.php` - Implements `CrewRepositoryInterface`
  - `EventRepository.php`, `SeasonRepository.php`

- **Services** (`Service/`): External service implementations
  - `AwsSesEmailService.php` - Implements `EmailServiceInterface` using AWS SES
  - `ICalendarService.php` - Implements `CalendarServiceInterface` using eluceo/ical
  - `SystemTimeService.php` - Implements `TimeServiceInterface` using system clock

- **CSV Migration** (`Persistence/CSV/`): Legacy support
  - `CsvMigration.php` - Migrate CSV → SQLite

**Example:**

```php
// Repository implements the interface defined in Application layer
class BoatRepository implements BoatRepositoryInterface
{
    private PDO $pdo;

    public function save(Boat $boat): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO boats (key, display_name, ...)
            VALUES (:key, :display_name, ...)
            ON CONFLICT(key) DO UPDATE SET ...
        ");

        $stmt->execute([
            ':key' => (string) $boat->getKey(),
            ':display_name' => $boat->getDisplayName(),
            // ... map entity to database
        ]);
    }

    public function findByKey(BoatKey $key): ?Boat
    {
        $stmt = $this->pdo->prepare("SELECT * FROM boats WHERE key = :key");
        $stmt->execute([':key' => (string) $key]);
        $row = $stmt->fetch();

        if (!$row) return null;

        // Map database row to domain entity
        return new Boat(/* ... */);
    }
}
```

**Key Insight:** Infrastructure adapts external systems to match Application interfaces. The Application layer doesn't know if data comes from SQLite, PostgreSQL, CSV, or an API.

---

#### Layer 4: Presentation (`src/Presentation/`)

**Responsibility:** HTTP/REST API

**Dependencies:** Application layer

**What's in this layer:**

- **Controllers** (`Controller/`): HTTP handlers
  - `EventController.php` - GET /api/events, GET /api/events/{id}, GET /api/flotillas
  - `AuthController.php` - POST /api/auth/register, POST /api/auth/login, GET /api/auth/session, POST /api/auth/logout
  - `UserController.php` - GET/POST/PATCH /api/users/me (profile management)
  - `AvailabilityController.php` - PATCH /api/users/me/availability (unified, auto-detects boat/crew)
  - `AssignmentController.php` - GET /api/assignments
  - `AdminController.php` - Admin endpoints

- **Middleware** (`Middleware/`): Request processing
  - `JwtAuthMiddleware.php` - JWT token authentication (Authorization: Bearer header)
  - `ErrorHandlerMiddleware.php` - Convert exceptions to JSON errors
  - `CorsMiddleware.php` - CORS headers

- **Router** (`Router.php`): URL pattern matching

- **Response** (`Response/JsonResponse.php`): JSON formatting

**Example:**

```php
// Controller handles HTTP, delegates to Use Case
class AvailabilityController
{
    public function __construct(
        private UpdateBoatAvailabilityUseCase $updateBoatAvailabilityUseCase
    ) {}

    public function updateBoatAvailability(array $body, array $auth): JsonResponse
    {
        try {
            // Create DTO from request body
            $dto = new UpdateAvailabilityRequest(
                availabilities: $body['availabilities'] ?? []
            );

            // Execute use case (business logic)
            $response = $this->updateBoatAvailabilityUseCase->execute(
                $auth['user_id'],
                $dto
            );

            // Return HTTP response
            return JsonResponse::success([
                'boat' => $response->toArray(),
                'message' => 'Boat availability updated successfully'
            ]);

        } catch (ValidationException $e) {
            return JsonResponse::error($e->getMessage(), 400);
        } catch (BoatNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        }
    }
}
```

**Key Insight:** Controllers translate HTTP requests to DTOs, call Use Cases, and translate results back to HTTP responses. They have no business logic.

---

### Data Flow Example

Here's how an availability update flows through the layers:

```
1. HTTP Request
   PATCH /api/users/me/availability
   Headers: Authorization: Bearer <jwt_token>
   {
     "availabilities": {
       "Fri May 29": 2,
       "Fri Jun 05": 3
     }
   }

   ↓

2. Presentation Layer (Controller)
   - Extract JSON from HTTP body
   - Extract auth data from JWT token (user_id, email, account_type)
   - Create UpdateAvailabilityRequest DTO
   - Auto-detect user role(s):
     * Try to find boat via BoatRepository
     * Try to find crew via CrewRepository
   - Call applicable use case(s):
     * UpdateBoatAvailabilityUseCase (if boat owner)
     * UpdateCrewAvailabilityUseCase (if crew member)
     * Both (if flex member)

   ↓

3. Application Layer (Use Case)
   - Validate request data
   - Find entity via Repository (Port/Interface)
   - Update availability for each event via Repository::setAvailability

   ↓

4. Infrastructure Layer (Repository)
   - Execute SQL UPDATE on boat_availability table
   - Return success

   ↓

5. Application Layer (Use Case)
   - Returns BoatResponse DTO to Controller

   ↓

6. Presentation Layer (Controller)
   - Create JsonResponse
   - Set HTTP status code 200
   - Return JSON with boat data

   ↓

7. HTTP Response
   {
     "success": true,
     "data": {
       "boat": { ... },
       "message": "Boat availability updated successfully"
     }
   }
```

**Notice:**
- Domain layer (Boat entity, RankingService) has no knowledge of HTTP or database
- Use Case has no knowledge of HTTP or SQL
- Only Infrastructure layer knows about database
- Only Presentation layer knows about HTTP

---

### Testing Benefits

Clean Architecture makes testing straightforward:

**Unit Test (Domain):**
```php
// Test business logic without database or HTTP
$selectionService = new SelectionService();
$boats = [new Boat(...), new Boat(...)];
$result = $selectionService->shuffle($boats, new EventId('Fri May 29'));
$this->assertEquals($expected, $result);
```

**Integration Test (Infrastructure):**
```php
// Test repository with in-memory database
$pdo = new PDO('sqlite::memory:');
$repository = new BoatRepository($pdo);
$repository->save($boat);
$found = $repository->findByKey($boat->getKey());
$this->assertEquals($boat, $found);
```

**API Test (Presentation):**
```php
// Test HTTP endpoint with mocked use case
$useCase = $this->createMock(UpdateBoatAvailabilityUseCase::class);
$controller = new AvailabilityController($useCase);
$response = $controller->updateBoatAvailability(['availabilities' => []], ['user_id' => 1, 'email' => 'john@example.com', 'account_type' => 'crew', 'is_admin' => false]);
$this->assertEquals(200, $response->statusCode);
```

---

## Development Workflow

### Daily Development

1. **Pull Latest Changes**
   ```bash
   git pull origin main
   ```

2. **Create Feature Branch**
   ```bash
   git checkout -b feature/add-crew-notes
   ```

3. **Make Changes** (following Clean Architecture layers)
   - Start with Domain (entities, value objects)
   - Add Application layer (use cases, DTOs)
   - Implement Infrastructure (repositories)
   - Add Presentation (controllers)

4. **Write Tests**
   ```bash
   ./vendor/bin/phpunit
   ```

5. **Test API Manually**
   ```bash
   php -S localhost:8000 -t public
   # Test with Postman or curl
   ```

6. **Commit Changes**
   ```bash
   git add .
   git commit -m "feat: add notes field to crew"
   ```

7. **Push and Create PR**
   ```bash
   git push origin feature/add-crew-notes
   # Create Pull Request on GitHub
   ```

### Common Tasks

#### Add a New API Endpoint

1. **Define Use Case** (`src/Application/UseCase/`)
2. **Create Request/Response DTOs** (`src/Application/DTO/`)
3. **Implement Controller Method** (`src/Presentation/Controller/`)
4. **Add Route** (`config/routes.php`)
5. **Write Tests**
6. **Update Postman Collection**

#### Add a New Domain Entity

1. **Create Entity Class** (`src/Domain/Entity/`)
2. **Create Repository Interface** (`src/Application/Port/Repository/`)
3. **Implement Repository** (`src/Infrastructure/Persistence/SQLite/`)
4. **Create Database Migration** (`database/migrations/`)
5. **Wire in Dependency Injection** (`config/container.php`)
6. **Write Tests**

#### Modify Business Logic

1. **Update Domain Service** (`src/Domain/Service/`)
2. **Write/Update Unit Tests**
3. **Verify Integration Tests Still Pass**
4. **Update Documentation**

---

## API Documentation

### Base URL

- **Development:** `http://localhost:8000/api`
- **Production:** `https://your-domain.com/api`

### Authentication

Most endpoints require JWT (JSON Web Token) authentication. To authenticate:

1. **Register or Login** to obtain a JWT token:

   ```bash
   curl -X POST http://localhost:8000/api/auth/register \
     -H "Content-Type: application/json" \
     -d '{
       "email": "user@example.com",
       "password": "your_secure_password",
       "accountType": "crew",
       "profile": {
         "firstName": "John",
         "lastName": "Doe",
         "skill": 1
       }
     }'

   # Or login if already registered:
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"user@example.com","password":"your_password"}'
   ```

2. **Use the token** in subsequent requests:

   ```http
   Authorization: Bearer YOUR_JWT_TOKEN_HERE
   ```

**Token expiration:** Tokens expire after 60 minutes by default (configurable via `JWT_EXPIRATION_MINUTES`).

### Endpoints

#### Public Endpoints

**GET /api/events**

List all events for the season.

Response:
```json
{
  "success": true,
  "data": {
    "events": [
      {
        "event_id": "Fri May 29",
        "event_date": "2026-05-29",
        "start_time": "12:45:00",
        "finish_time": "17:00:00",
        "status": "upcoming"
      }
    ]
  }
}
```

**GET /api/events/{id}**

Get details for a specific event.

Response:
```json
{
  "success": true,
  "data": {
    "event": {
      "event_id": "Fri May 29",
      "event_date": "2026-05-29"
    },
    "crewed_boats": [...],
    "waitlist_boats": [...],
    "waitlist_crews": [...]
  }
}
```

#### Authenticated Endpoints

**PATCH /api/users/me/availability**

Update your availability. The endpoint auto-detects if you are a boat owner, crew member, or both (flex member) and updates all applicable entities.

- **Boat owners**: Values represent berths (capacity offered, e.g., 2 = offering 2 berths)
- **Crew members**: Values represent availability status (0=UNAVAILABLE, 1=AVAILABLE, 2=GUARANTEED, 3=WITHDRAWN)
- **Flex members**: Same values are used for both boat berths AND crew status

Request Body:
```json
{
  "availabilities": {
    "Fri May 29": 1,
    "Fri Jun 05": 2
  }
}
```

Response:
```json
{
  "success": true,
  "updated": ["crew"],
  "crew": {
    "key": "john_doe",
    "firstName": "John",
    "lastName": "Doe",
    "availabilities": {
      "Fri May 29": 1,
      "Fri Jun 05": 2
    }
  },
  "message": "Availability updated successfully"
}
```

For boat owners, the response includes `"updated": ["boat"]` with boat details.
For flex members, the response includes `"updated": ["boat", "crew"]` with both entities.

**GET /api/assignments**

Get your crew assignments across all events.

Response:
```json
{
  "success": true,
  "data": {
    "assignments": [
      {
        "event_id": "Fri May 29",
        "boat_key": "sailaway",
        "boat_name": "Sail Away"
      }
    ]
  }
}
```

**GET /api/flotillas**

Get all flotillas for all events.

Response:
```json
{
  "success": true,
  "data": {
    "flotillas": [
      {
        "event_id": "Fri May 29",
        "crewed_boats": [...],
        "waitlist_boats": [...],
        "waitlist_crews": [...]
      }
    ]
  }
}
```

**GET /api/users/me/availability**

Get your current availability status across all events.

Response:
```json
{
  "success": true,
  "data": {
    "availabilities": {
      "Fri May 29": 1,
      "Fri Jun 05": 2
    }
  }
}
```

**GET /api/users/me**

Get your user profile (boat owner or crew).

Response:
```json
{
  "success": true,
  "data": {
    "accountType": "crew",
    "profile": {
      "key": "john_doe",
      "firstName": "John",
      "lastName": "Doe",
      "email": "john@example.com",
      "skill": 1
    }
  }
}
```

**POST /api/users/me**

Add a new profile (after registration, create boat or crew profile).

Request Body:
```json
{
  "accountType": "crew",
  "profile": {
    "firstName": "John",
    "lastName": "Doe",
    "skill": 1,
    "mobile": "555-1234"
  }
}
```

**PATCH /api/users/me**

Update your user profile.

Request Body:
```json
{
  "profile": {
    "mobile": "555-9999",
    "skill": 2
  }
}
```

**GET /api/auth/session**

Get current session information.

Response:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "accountType": "crew",
      "isAdmin": false
    }
  }
}
```

**POST /api/auth/logout**

Logout and invalidate the JWT token.

Response:
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### Admin Endpoints

**GET /api/admin/matching/{eventId}**

Get matching data for an event (boats, crews, capacity analysis).

**POST /api/admin/notifications/{eventId}**

Send email notifications for an event.

**PATCH /api/admin/config**

Update season configuration.

Request Body:
```json
{
  "year": 2026,
  "source": "production",
  "start_time": "12:45:00",
  "finish_time": "17:00:00",
  "blackout_from": "10:00:00",
  "blackout_to": "18:00:00"
}
```

### Error Responses

All errors return JSON:

```json
{
  "success": false,
  "error": "Boat not found",
  "code": 404
}
```

Common status codes:
- `400` - Bad Request (validation error)
- `401` - Unauthorized (missing authentication)
- `403` - Forbidden (blackout window)
- `404` - Not Found
- `500` - Internal Server Error

---

## Deployment

### Production Deployment Checklist

- [ ] Run tests locally: `./vendor/bin/phpunit`
- [ ] Backup production database
- [ ] Upload changed files via SFTP
- [ ] Run database migrations if needed
- [ ] Install/update composer dependencies: `composer install --no-dev`
- [ ] Set file permissions
- [ ] Verify API endpoints work
- [ ] Check error logs: `/opt/bitnami/apache/logs/error_log`
- [ ] Monitor for issues

### Rollback Procedure

If deployment fails:

```bash
ssh bitnami@16.52.222.15
cd /var/www/html

# Restore database backup
cp database/jaws.backup.YYYYMMDD_HHMMSS.db database/jaws.db

# Restore previous code version (if you tagged it)
git checkout v1.2.3

# Reinstall dependencies
composer install --no-dev

# Restart Apache
sudo /opt/bitnami/ctlscript.sh restart apache
```

### Monitoring

**Check Logs:**
```bash
ssh bitnami@16.52.222.15
tail -f /opt/bitnami/apache/logs/error_log
```

**Database Size:**
```bash
ls -lh /var/www/html/database/jaws.db
```

**Backup Database:**
```bash
sqlite3 jaws.db .dump > backup_$(date +%Y%m%d).sql
```

---

## Contributing

### Code Style

Follow PSR-12 coding standard:
- Use 4 spaces for indentation
- Declare strict types: `declare(strict_types=1);`
- Type hint all parameters and return types
- Use readonly properties where applicable

### Git Workflow

1. Create feature branch from `main`
2. Make changes following Clean Architecture
3. Write tests
4. Create Pull Request
5. Wait for review and approval
6. Merge to `main`

### Commit Message Format

Use [Conventional Commits](https://www.conventionalcommits.org/) format:

```bash
<type>: <description>
```

**Common types:**
- `feat`: New feature
- `fix`: Bug fix
- `test`: Add or update tests
- `docs`: Documentation changes
- `refactor`: Code refactoring
- `ci`: CI/CD changes

**Examples:**
```bash
git commit -m "feat: add notes field to crew"
git commit -m "fix: prevent duplicate assignments"
git commit -m "test: add SelectionService unit tests"
```

### Questions?

- Check `/docs` folder for detailed documentation
- Review `CLAUDE.md` for project guidelines
- Read phase completion documents in `/docs/PHASE_*.md`

---

## License

Proprietary - Nepean Sailing Club

---

## Credits

Developed for Nepean Sailing Club's Social Day Cruising Program.

Refactored to Clean Architecture in 2026, preserving all original business logic while improving maintainability, testability, and flexibility.
