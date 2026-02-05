# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Human-Readable Documentation

For human developers, the primary documentation is in the `/docs` folder:

- **[README.md](README.md)** - Project overview and quick navigation hub
- **[docs/SETUP.md](docs/SETUP.md)** - Installation and setup instructions for new developers
- **[docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md)** - Architecture, development workflow, testing, and best practices
- **[docs/API.md](docs/API.md)** - Complete API endpoint documentation with examples
- **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - Production deployment procedures and monitoring
- **[docs/CONTRIBUTING.md](docs/CONTRIBUTING.md)** - Code style, Git workflow, and PR process

**IMPORTANT:** When developers ask general questions about setup, architecture, or API endpoints, direct them to the appropriate human-readable documentation file above rather than replicating content from this file.

This CLAUDE.md file contains technical specifications optimized for AI assistant consumption. It serves as the "source of truth" for technical details but is not the primary documentation for human developers.

## Project Overview

JAWS is a PHP-based REST API for managing the Social Day Cruising program at Nepean Sailing Club. It handles boat fleet management, crew registration, and intelligent assignment of crew members to boats for seasonal sailing events. The system optimizes crew-to-boat matching based on multiple constraints including skill levels, availability, preferences, and historical participation.

**Architecture:** Clean Architecture (Hexagonal/Ports and Adapters pattern) with 4 distinct layers: Domain, Application, Infrastructure, and Presentation.

**Database:** SQLite (with migration path to PostgreSQL)

**API Style:** REST/JSON with JWT authentication

## Development Commands

### Install Dependencies
```bash
composer install
```

### Initialize Database
```bash
# Run all migrations (recommended)
vendor/bin/phinx migrate

# Or use legacy init script (calls Phinx internally)
php database/init_database.php
```

### Database Migrations

```bash
# Create new migration
vendor/bin/phinx create MyMigrationName

# Run pending migrations
vendor/bin/phinx migrate

# Rollback last migration
vendor/bin/phinx rollback

# Check migration status
vendor/bin/phinx status

# Seed test data
vendor/bin/phinx seed:run
```

### Start Development Server
```bash
php -S localhost:8000 -t public
```

### Run Tests
```bash
# All tests
./vendor/bin/phpunit

# Unit tests only
./vendor/bin/phpunit tests/Unit

# Integration tests only
./vendor/bin/phpunit tests/Integration

# Specific test file
./vendor/bin/phpunit tests/Unit/Domain/SelectionServiceTest.php
```

### Run API Tests
```bash
# Ensure dev server is running
php -S localhost:8000 -t public &

# Run API tests
php Tests/Integration/api_test.php
```

### Deploy to AWS Lightsail
Upload files via SFTP:
```bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15
cd /./var/www/html
put public/index.php
put -r src
put -r config
put database/jaws.db
bye
```

Then set permissions:
```bash
ssh bitnami@16.52.222.15
cd /var/www/html
sudo chgrp -R www-data src config database
sudo chmod -R 750 src config
sudo chmod 664 database/jaws.db
sudo chmod 775 database
sudo /opt/bitnami/ctlscript.sh restart apache
```

### Database Operations

**Apply Migration:**
```bash
# Modern method (recommended)
vendor/bin/phinx migrate

# Legacy method (deprecated - for reference only)
# sqlite3 database/jaws.db < database/migrations/archive/002_new_migration.sql
```

**Backup Database:**
```bash
cp database/jaws.db database/jaws.backup.$(date +%Y%m%d_%H%M%S).db
```

**Query Database:**
```bash
sqlite3 database/jaws.db "SELECT * FROM boats LIMIT 5;"
```

### Download Data from Production
```bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15
cd var/www/html/database
get jaws.db
get jaws.db.backup.*
bye
```

## Commit Message Format

This project uses **Conventional Commits** specification for all commit messages. When creating commits, ALWAYS follow this format:

### Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Types

- **feat**: A new feature
- **fix**: A bug fix
- **docs**: Documentation only changes
- **style**: Changes that don't affect code meaning (formatting, white-space, etc.)
- **refactor**: Code change that neither fixes a bug nor adds a feature
- **perf**: Code change that improves performance
- **test**: Adding missing tests or correcting existing tests
- **build**: Changes affecting build system or external dependencies
- **ci**: Changes to CI configuration files and scripts
- **chore**: Other changes that don't modify src or test files

### Examples

```bash
feat: add crew notes field to database schema
fix: prevent duplicate crew assignments on same boat
docs: update API documentation for availability endpoint
test: add integration tests for AssignmentService
refactor: extract rank calculation into separate service
ci: add automated testing workflow
```

### Rules

1. Use lowercase for type and description
2. No period at the end of the description
3. Use imperative mood ("add" not "added" or "adds")
4. Keep description under 72 characters
5. Add body if change needs explanation (use blank line after description)
6. Reference issue numbers in footer if applicable

### When Creating Commits

When Claude Code creates commits:
- Always include the Co-Authored-By line as specified in the git commit guidance
- Choose the most appropriate type based on the change
- Keep descriptions concise but descriptive
- For multi-file changes, choose the type that best represents the primary purpose

## Clean Architecture Overview

The application follows Clean Architecture with strict dependency rules: outer layers depend on inner layers, never the reverse.

### Layer Dependency Direction

```
Presentation → Infrastructure → Application → Domain
  (HTTP)      (Database/AWS)   (Use Cases)   (Business Logic)
```

**Key Principle:** The Domain layer has ZERO dependencies. It contains pure business logic.

### Layer 1: Domain (`src/Domain/`)

**Responsibility:** Core business logic and rules

**Dependencies:** None (pure PHP, no external libraries)

**Contents:**

- **Entities** (`Entity/`)
  - `Boat.php` - Boat entity with capacity, owner info, ranking, history, availability
  - `Crew.php` - Crew member entity with skills, preferences, whitelist, ranking

- **Value Objects** (`ValueObject/`)
  - `BoatKey.php` - Immutable boat identifier
  - `CrewKey.php` - Immutable crew identifier
  - `EventId.php` - Immutable event identifier with hash support
  - `Rank.php` - Multi-dimensional rank tensor (lexicographic comparison)

- **Enums** (`Enum/`)
  - `RankDimension.php` - Boat/crew ranking dimensions
  - `AvailabilityStatus.php` - UNAVAILABLE (0), AVAILABLE (1), GUARANTEED (2), WITHDRAWN (3)
  - `SkillLevel.php` - NOVICE (0), INTERMEDIATE (1), ADVANCED (2)
  - `AssignmentRule.php` - 6 optimization rules (ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT)
  - `TimeSource.php` - Production vs simulated time source

- **Collections** (`Collection/`)
  - `Fleet.php` - In-memory boat collection management
  - `Squad.php` - In-memory crew collection management

- **Domain Services** (`Service/`) - **CRITICAL ALGORITHMS PRESERVED**
  - `SelectionService.php` - Ranking & boat/crew selection algorithm (CRC32 seeding, lexicographic sort)
  - `AssignmentService.php` - Crew-to-boat optimization via constraint-based swapping
  - `RankingService.php` - Multi-dimensional rank calculations
  - `FlexService.php` - Flex status detection (boat owners as crew, crew owning boats)

### Layer 2: Application (`src/Application/`)

**Responsibility:** Use cases and application orchestration

**Dependencies:** Domain layer only

**Contents:**

- **Use Cases** (`UseCase/`)
  - `Boat/UpdateBoatAvailabilityUseCase.php` - Update boat berths for events
  - `Crew/UpdateCrewAvailabilityUseCase.php` - Update crew availability
  - `Crew/GetUserAssignmentsUseCase.php` - Get user's assignments across all events
  - `Event/GetAllEventsUseCase.php` - List all events
  - `Event/GetEventUseCase.php` - Get event with flotilla
  - `Season/ProcessSeasonUpdateUseCase.php` - **CRITICAL**: Main orchestration pipeline (replaces season_update.php)
  - `Season/GenerateFlotillaUseCase.php` - Generate flotilla for an event
  - `Season/UpdateConfigUseCase.php` - Update season configuration
  - `Admin/GetMatchingDataUseCase.php` - Get matching data for event
  - `Admin/SendNotificationsUseCase.php` - Send email notifications

- **Ports (Interfaces)** (`Port/`)
  - `Repository/BoatRepositoryInterface.php` - How to persist boats
  - `Repository/CrewRepositoryInterface.php` - How to persist crew
  - `Repository/EventRepositoryInterface.php` - How to query events
  - `Repository/SeasonRepositoryInterface.php` - How to manage season config/flotillas
  - `Service/EmailServiceInterface.php` - How to send emails
  - `Service/CalendarServiceInterface.php` - How to generate iCal files
  - `Service/TimeServiceInterface.php` - How to get current time

- **DTOs** (`DTO/`)
  - `Request/UpdateAvailabilityRequest.php` - Availability updates
  - `Request/UpdateConfigRequest.php` - Season config updates
  - `Response/BoatResponse.php` - Serialized boat
  - `Response/CrewResponse.php` - Serialized crew
  - `Response/EventResponse.php` - Serialized event
  - `Response/FlotillaResponse.php` - Flotilla assignment
  - `Response/AssignmentResponse.php` - Crew-to-boat assignment

- **Exceptions** (`Exception/`)
  - `BoatNotFoundException.php` - Boat not found
  - `CrewNotFoundException.php` - Crew not found
  - `EventNotFoundException.php` - Event not found
  - `ValidationException.php` - Field-level validation errors
  - `BlackoutWindowException.php` - Registration blocked during event

### Layer 3: Infrastructure (`src/Infrastructure/`)

**Responsibility:** External adapters (database, email, calendar, etc.)

**Dependencies:** Application + Domain layers

**Contents:**

- **Persistence** (`Persistence/SQLite/`)
  - `Connection.php` - Singleton PDO manager with foreign keys & WAL mode
  - `BoatRepository.php` - Implements `BoatRepositoryInterface` (full CRUD with lazy loading)
  - `CrewRepository.php` - Implements `CrewRepositoryInterface` (full CRUD with whitelist management)
  - `EventRepository.php` - Implements `EventRepositoryInterface` (time-based event queries)
  - `SeasonRepository.php` - Implements `SeasonRepositoryInterface` (config & flotilla JSON storage)

- **CSV Migration** (`Persistence/CSV/`)
  - `CsvMigration.php` - Legacy CSV to SQLite migration (~370 lines)

- **Service Adapters** (`Service/`)
  - `AwsSesEmailService.php` - Implements `EmailServiceInterface` using AWS SES via PHPMailer
  - `ICalendarService.php` - Implements `CalendarServiceInterface` using eluceo/ical
  - `SystemTimeService.php` - Implements `TimeServiceInterface` (production/simulated time)

### Layer 4: Presentation (`src/Presentation/`)

**Responsibility:** HTTP/REST API

**Dependencies:** Application layer only

**Contents:**

- **Controllers** (`Controller/`)
  - `EventController.php` - GET /api/events, GET /api/events/{id}
  - `AvailabilityController.php` - Boat/crew registration & availability updates
  - `AssignmentController.php` - GET /api/assignments (user's assignments)
  - `AdminController.php` - Admin endpoints (matching data, notifications, config)

- **Middleware** (`Middleware/`)
  - `JwtAuthMiddleware.php` - JWT token authentication (Authorization: Bearer header)
  - `ErrorHandlerMiddleware.php` - Exception → JSON responses (maps to HTTP status codes)
  - `CorsMiddleware.php` - Cross-Origin Resource Sharing headers

- **Router** (`Router.php`)
  - Pattern-matching router with parameter extraction

- **Response** (`Response/JsonResponse.php`)
  - Standardized response factory (success/error/404/500)

## The Season Update Pipeline

**Entry Point:** `ProcessSeasonUpdateUseCase` (replaces legacy `season_update.php`)

**Trigger:** Invoked after every user input (registration or availability update) via POST endpoints

**Pipeline:** Executes this workflow for each future event:

1. **Load Data**
   - Fetch all boats, crews, events, season config from database
   - Build in-memory Fleet and Squad collections

2. **Selection Phase** (`SelectionService`)
   - Get available boats and crews for this event
   - Apply multi-dimensional ranking (boats: flexibility, absence; crews: commitment, flexibility, membership, absence)
   - Perform deterministic shuffle using `crc32($eventId)` as seed
   - Sort using lexicographic rank comparison (bubble sort)
   - Execute capacity matching (3 cases: too few crews, too many crews, perfect fit)

3. **Event Consolidation**
   - Form selected boats and crews into structured flotilla
   - Separate crewed boats from waitlisted boats/crews

4. **Assignment Optimization** (`AssignmentService`) - **NEXT EVENT ONLY**
   - For the immediate next event only, perform constraint-based optimization
   - Iteratively swap crew between boats to minimize 6 rule violations:
     - **ASSIST**: Boats requiring assistance get appropriate crew
     - **WHITELIST**: Crew assigned to boats they've whitelisted
     - **HIGH_SKILL / LOW_SKILL**: Balance skill distribution
     - **PARTNER**: Keep requested partnerships together
     - **REPEAT**: Minimize crew repeating same boat
   - Use greedy approach: for each rule, identify highest-loss crew and best-grad swap candidate
   - Lock crew after swapping to prevent thrashing

5. **Persistence**
   - Update crew availability statuses (GUARANTEED for assigned crew)
   - Update boat/crew history
   - Save flotilla assignments to database (JSON format)

6. **Output Generation** (future enhancement)
   - Render flotilla tables to HTML
   - Generate iCalendar files for participants

## Database Schema (SQLite)

**Location:** `database/jaws.db`

**10 Tables:**

1. **boats** - Boat info (display_name, owner_*, capacity, assistance_required, ranking)
2. **crews** - Crew info (name, partner_key, email, skill, membership_number, ranking)
3. **events** - Event schedule (event_id, event_date, start_time, finish_time, status)
4. **boat_availability** - Berths offered per boat per event
5. **crew_availability** - Crew status per event (0=unavailable, 1=available, 2=guaranteed, 3=withdrawn)
6. **boat_history** - Boat participation history ('Y' or '')
7. **crew_history** - Crew-to-boat assignments per event
8. **crew_whitelist** - Crew preferences for specific boats
9. **season_config** - Season-wide singleton config (dates, times, blackout windows)
10. **flotillas** - Generated flotilla assignments (JSON)

**Schema Features:**
- Foreign key constraints enabled with CASCADE deletes
- Composite indexes on frequently queried columns (boat_key, crew_key, event_id)
- WAL mode for concurrent access
- Timestamps (created_at, updated_at) on key tables
- Triggers for automatic updated_at maintenance

**Migrations:** `database/migrations/001_initial_schema.sql`

## Multi-Dimensional Ranking System

The system uses rank tensors for prioritization:

- **Boats**: `[flexibility, absence]` (2D)
- **Crews**: `[commitment, flexibility, membership, absence]` (4D)

Rankings are compared lexicographically during bubble sort. **Lower rank = higher priority.**

**Rank Components:**
- `flexibility` - Whether boat owner is also registered as crew (boats) or crew owns a boat (crew)
- `absence` - Count of past no-shows (updated dynamically)
- `commitment` - Crew's availability for the next scheduled event (0=unavailable, 1=available)
- `membership` - Valid NSC membership number status (0=valid, 1=invalid)

**Deterministic Shuffling:** Ties are broken using deterministic shuffling seeded by `crc32($eventId)`, ensuring reproducible results.

**Implementation:** `RankingService::calculateBoatRank()`, `RankingService::calculateCrewRank()`

## Assignment Optimization Algorithm

**Location:** `src/Domain/Service/AssignmentService.php` (preserved from legacy)

**Purpose:** For the next event only, optimize crew-to-boat assignments to minimize rule violations.

**Process:**

1. **Calculate Loss and Grad** for each crew on each rule:
   - **Loss**: How much this crew violates this rule on their current boat
   - **Grad**: How much this crew could reduce violations by swapping to another boat

2. **Iterate through 6 rules** in priority order (ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT)

3. **For each rule:**
   - Identify highest-loss crew (most violating)
   - Find best-grad swap candidate (crew that would reduce violations most)
   - Swap if it reduces total violations and doesn't create other conflicts
   - Mark swapped crew as "locked" for this rule to prevent thrashing

4. **Greedy Approach:** This balances optimality with computational efficiency

**Critical Methods:**
- `crew_loss()` - Calculate violation severity for each rule
- `crew_grad()` - Calculate mitigation capacity for each rule
- `bad_swap()` - Swap validation logic
- `best_swap()` - Greedy swap selection
- `assign()` - Main optimization loop with unlocked_crews tracking

## API Endpoints

**Base URL:** `/api`

**Entry Point:** `public/index.php`

**Routing:** `config/routes.php` (pattern-matching with parameter extraction)

### Public Endpoints (No Authentication)

**GET /api/events**
- Returns all events for the season
- Controller: `EventController::getAll()`

**GET /api/events/{id}**
- Returns specific event with flotilla assignments
- Controller: `EventController::getOne($eventId)`

### Authenticated Endpoints (JWT)

**Headers Required:**
```
Authorization: Bearer {jwt_token}
```

**PATCH /api/users/me/availability**
- Update availability for authenticated user
- Auto-detects if user is boat owner, crew member, or both (flex)
- Updates all applicable entities:
  - Boat owners: updates boat berths (capacity offered)
  - Crew members: updates crew availability status (0-3 enum)
  - Flex members: updates BOTH boat berths AND crew status
- Controller: `AvailabilityController::updateAvailability()`
- Triggers: `ProcessSeasonUpdateUseCase`
- Response includes `updated` array indicating which entities were modified (e.g., `["boat"]`, `["crew"]`, or `["boat", "crew"]`)

**GET /api/assignments**
- Get user's assignments across all events
- Controller: `AssignmentController::getUserAssignments()`

### Admin Endpoints (Authenticated)

**GET /api/admin/matching/{eventId}**
- Get matching data for event (available boats/crews, capacity analysis)
- Controller: `AdminController::getMatchingData($eventId)`

**POST /api/admin/notifications/{eventId}**
- Send email notifications for event
- Controller: `AdminController::sendNotifications($eventId)`

**PATCH /api/admin/config**
- Update season configuration (dates, times, blackout windows)
- Controller: `AdminController::updateConfig()`

## Important Architectural Concepts

### Dependency Inversion Principle

Application layer defines **interfaces (ports)**, Infrastructure layer provides **implementations (adapters)**.

Example:
```php
// Application layer defines contract
interface BoatRepositoryInterface {
    public function save(Boat $boat): void;
    public function findByKey(BoatKey $key): ?Boat;
}

// Infrastructure layer implements it
class BoatRepository implements BoatRepositoryInterface {
    // SQLite implementation
}
```

Use cases depend on interfaces, not concrete implementations. This allows swapping SQLite for PostgreSQL without changing business logic.

### Flex Concept

Boat owners can also register as crew, and crew can own boats. This "flex" status affects ranking calculations and prevents double-counting in capacity matching.

**Detection:** `FlexService::isFlexBoatOwner()`, `FlexService::isFlexCrewMember()`

**Impact on Ranking:** Flex status sets `flexibility` rank dimension to 0 (higher priority)

### Deterministic Behavior

The system uses seeded randomization by `crc32($eventId)` to ensure the same inputs always produce identical assignments. This is critical for reproducibility and user trust.

**Implementation:** `SelectionService::shuffle()` uses `srand(crc32($eventId))`

### Capacity Matching (3 Cases)

**`SelectionService::cut()`** handles three scenarios:

1. **Too Few Crews** (`case_1()`)
   - Not enough crew to fill all boats
   - Leave boats partially crewed, rest go to waitlist
   - Example: 10 boats need 20 crew, but only 15 available → crew 7 boats, waitlist 3 boats + 1 crew

2. **Too Many Crews** (`case_2()`)
   - More crew than boat capacity
   - Fill all boats, excess crews go to waitlist
   - Example: 5 boats need 10 crew, but 15 available → crew all 5 boats, waitlist 5 crews

3. **Perfect Fit** (`case_3()`)
   - Exactly enough crew to fill all boats
   - All boats perfectly crewed, no waitlists

### History Tracking

Boats and crews maintain participation history arrays. The `absence` rank dimension dynamically updates based on past no-shows, deprioritizing unreliable participants.

**Storage:**
- **boat_history**: `participated` field ('Y' or '')
- **crew_history**: `boat_key` field (boat assigned or '')

**Rank Impact:** `absence` count increments for each no-show, worsening rank

### Availability States

**Enum:** `AvailabilityStatus`

- `UNAVAILABLE` (0) - Cannot participate
- `AVAILABLE` (1) - Can participate (default for new registrations)
- `GUARANTEED` (2) - Selected for event (set by assignment algorithm)
- `WITHDRAWN` (3) - Explicitly withdrawn (user action)

**Database:** `crew_availability.status` column (integer 0-3)

### Blackout Logic

During event days (10:00-18:00 by default), registration is blocked to prevent mid-event changes.

**Configuration:** `season_config.blackout_from`, `season_config.blackout_to`

**Check:** `TimeServiceInterface::isInBlackoutWindow()`

**Exception:** `BlackoutWindowException` (maps to HTTP 403)

## Testing

Test cases are documented in `/Tests/Test cases.numbers` (Apple Numbers spreadsheet). Automated tests use PHPUnit.

**Test Structure:**
- `tests/Unit/` - Unit tests (Domain layer, no external dependencies)
- `tests/Integration/` - Integration tests (Infrastructure layer, in-memory SQLite)
- `Tests/Integration/api_test.php` - Simple HTTP test suite (curl-based)
- `Tests/JAWS_API.postman_collection.json` - Postman test collection

**Running Tests:**
```bash
# All tests
./vendor/bin/phpunit

# Unit tests only
./vendor/bin/phpunit tests/Unit

# Integration tests only
./vendor/bin/phpunit tests/Integration

# Specific test
./vendor/bin/phpunit tests/Unit/Domain/SelectionServiceTest.php
```

**Writing Tests:**

**Unit Test Example (Domain):**
```php
// Test business logic without database
$selectionService = new SelectionService();
$boats = [new Boat(...), new Boat(...)];
$result = $selectionService->shuffle($boats, new EventId('Fri May 29'));
$this->assertEquals($expected, $result);
```

**Integration Test Example (Infrastructure):**
```php
// Test repository with in-memory database
$pdo = new PDO('sqlite::memory:');
$schema = file_get_contents(__DIR__ . '/../../database/migrations/001_initial_schema.sql');
$pdo->exec($schema);

Connection::setTestConnection($pdo);
$repository = new BoatRepository();
$repository->save($boat);
$found = $repository->findByKey($boat->getKey());
$this->assertEquals($boat, $found);

Connection::resetTestConnection();
```

## Dependencies

**Composer Packages:**
```json
{
  "require": {
    "php": "^8.1",
    "phpmailer/phpmailer": "^7.0",
    "eluceo/ical": "^2.13",
    "ext-pdo": "*",
    "ext-pdo_sqlite": "*"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0",
    "robmorgan/phinx": "^0.16"
  }
}
```

**Environment Variables:**
- `DB_PATH` - Database file path (default: `database/jaws.db`)
- `JWT_SECRET` - JWT signing secret (minimum 32 characters, required)
- `JWT_EXPIRATION_MINUTES` - Token expiration (default: 60)
- `SES_REGION` - AWS SES region (default: `ca-central-1`)
- `SES_SMTP_USERNAME` - SMTP credentials
- `SES_SMTP_PASSWORD` - SMTP credentials
- `EMAIL_FROM` - From email address
- `EMAIL_FROM_NAME` - From name
- `APP_DEBUG` - Debug mode (true/false)
- `APP_TIMEZONE` - Timezone (default: `America/Toronto`)
- `CORS_ALLOWED_ORIGINS` - Comma-separated origins
- `CORS_ALLOWED_HEADERS` - Comma-separated headers (default: `Content-Type,Authorization`)

**Configuration:** `config/config.php` (reads environment variables with defaults)

## Development Notes

### Adding New Ranking Criteria

1. Add constant to `src/Domain/Enum/RankDimension.php`
2. Update `Boat` or `Crew` entity constructor (adjust rank array size)
3. Implement calculation logic in `RankingService.php`
4. Update `SelectionService::is_greater()` if comparison logic changes

### Adding New Assignment Rules

1. Add enum case to `src/Domain/Enum/AssignmentRule.php`
2. Implement `crew_loss()` logic for the new rule in `AssignmentService.php`
3. Implement `crew_grad()` logic for the new rule in `AssignmentService.php`
4. Add rule to priority order in `AssignmentService::assign()`

### Modifying Database Schema

1. Create new migration file: `database/migrations/00X_description.sql`
2. Write idempotent SQL (use `IF NOT EXISTS` clauses)
3. Update Domain entity class (`src/Domain/Entity/*.php`)
4. Update Repository implementation (`src/Infrastructure/Persistence/SQLite/*.php`)
5. Update DTOs if API changes (`src/Application/DTO/Request|Response/*.php`)
6. Write tests for new functionality
7. Apply migration: `sqlite3 database/jaws.db < database/migrations/00X_description.sql`

**SQLite Limitation:** For complex schema changes, use create-copy-drop pattern (see README.md)

### Adding New API Endpoint

1. Create Use Case: `src/Application/UseCase/{Context}/{Action}UseCase.php`
2. Create Request/Response DTOs: `src/Application/DTO/Request|Response/*.php`
3. Implement Controller method: `src/Presentation/Controller/*.php`
4. Add route: `config/routes.php`
5. Wire dependencies: `config/container.php`
6. Write tests: `tests/Integration/Api/` or `Tests/Integration/api_test.php`
7. Update Postman collection: `Tests/JAWS_API.postman_collection.json`

### Working with Time

The `TimeServiceInterface` provides time-aware methods for testing:

**Production Mode:** Uses system clock
**Simulated Mode:** Uses `season_config.simulated_date` (for testing)

**Configuration:** `season_config.source` (production|simulated)

**Usage:**
```php
$timeService = $container->get(TimeServiceInterface::class);
$now = $timeService->getCurrentTime(); // DateTime
$isBlackout = $timeService->isInBlackoutWindow($now);
```

## Common Patterns

### Dependency Injection

All dependencies are wired in `config/container.php`:

```php
$container = new Container();

// Repositories
$container->set(BoatRepositoryInterface::class, fn() => new BoatRepository());

// Services
$container->set(TimeServiceInterface::class, fn() => new SystemTimeService($config));

// Use Cases
$container->set(UpdateBoatAvailabilityUseCase::class, fn() => new UpdateBoatAvailabilityUseCase(
    $container->get(BoatRepositoryInterface::class)
));
```

### Loading and Saving Entities

```php
// Inject repository via constructor
class UpdateBoatAvailabilityUseCase {
    public function __construct(
        private BoatRepositoryInterface $boatRepository
    ) {}

    public function execute(string $ownerFirstName, string $ownerLastName, UpdateAvailabilityRequest $request): BoatResponse {
        // Find existing boat by owner name
        $boat = $this->boatRepository->findByOwnerName($ownerFirstName, $ownerLastName);

        // Update boat availabilities
        foreach ($request->availabilities as $eventId => $berths) {
            $this->boatRepository->setAvailability($boat->getKey(), new EventId($eventId), $berths);
        }

        return BoatResponse::fromEntity($boat);
    }
}
```

### Accessing Entities

```php
// Find by key
$boat = $boatRepository->findByKey(new BoatKey('sailaway'));
$crew = $crewRepository->findByName('John', 'Doe');

// Find by availability
$availableBoats = $boatRepository->findAvailableForEvent(new EventId('Fri May 29'));
$availableCrews = $crewRepository->findAvailableForEvent(new EventId('Fri May 29'));
```

### Working with Events

```php
// Load events
$allEvents = $eventRepository->findAll();
$futureEvents = $eventRepository->findFutureEvents($currentTime);
$nextEvent = $eventRepository->findNextEvent($currentTime);

// Process events
foreach ($futureEvents as $event) {
    $flotilla = $seasonRepository->getFlotilla($event->getEventId());
    // Process flotilla...
}
```

### Error Handling

Exceptions are automatically mapped to HTTP status codes by `ErrorHandlerMiddleware`:

- `BoatNotFoundException` → 404
- `CrewNotFoundException` → 404
- `EventNotFoundException` → 404
- `ValidationException` → 400
- `BlackoutWindowException` → 403
- Generic exceptions → 500

**Example:**
```php
public function execute(UpdateAvailabilityRequest $request): void {
    if (empty($request->availabilities)) {
        throw new ValidationException('Availabilities are required');
    }
    // ...
}
```

### Repository Pattern

Repositories handle all database interactions:

```php
interface BoatRepositoryInterface {
    public function save(Boat $boat): void;
    public function findByKey(BoatKey $key): ?Boat;
    public function findAll(): array;
    public function findAvailableForEvent(EventId $eventId): array;
    public function setAvailability(BoatKey $key, EventId $eventId, int $berths): void;
    public function getAvailability(BoatKey $key, EventId $eventId): int;
    public function setHistory(BoatKey $key, EventId $eventId, bool $participated): void;
    public function getHistory(BoatKey $key, EventId $eventId): bool;
}
```

Implementation in `src/Infrastructure/Persistence/SQLite/BoatRepository.php`

## File Paths Reference

**Entry Point:**
- `public/index.php` - REST API entry point

**Configuration:**
- `config/config.php` - Application configuration
- `config/container.php` - Dependency injection
- `config/routes.php` - API route definitions

**Domain Layer (Pure Business Logic):**
- `src/Domain/Entity/Boat.php`
- `src/Domain/Entity/Crew.php`
- `src/Domain/Service/SelectionService.php` ⚠️ CRITICAL ALGORITHM
- `src/Domain/Service/AssignmentService.php` ⚠️ CRITICAL ALGORITHM
- `src/Domain/Service/RankingService.php`
- `src/Domain/Service/FlexService.php`

**Application Layer (Use Cases & Ports):**
- `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php` ⚠️ MAIN ORCHESTRATOR
- `src/Application/Port/Repository/BoatRepositoryInterface.php`
- `src/Application/Port/Repository/CrewRepositoryInterface.php`

**Infrastructure Layer (Database & External Services):**
- `src/Infrastructure/Persistence/SQLite/Connection.php`
- `src/Infrastructure/Persistence/SQLite/BoatRepository.php`
- `src/Infrastructure/Persistence/SQLite/CrewRepository.php`
- `src/Infrastructure/Service/AwsSesEmailService.php`
- `src/Infrastructure/Service/ICalendarService.php`
- `src/Infrastructure/Service/SystemTimeService.php`

**Presentation Layer (HTTP/API):**
- `src/Presentation/Controller/EventController.php`
- `src/Presentation/Controller/AvailabilityController.php`
- `src/Presentation/Controller/AssignmentController.php`
- `src/Presentation/Controller/AdminController.php`
- `src/Presentation/Middleware/NameAuthMiddleware.php`
- `src/Presentation/Middleware/ErrorHandlerMiddleware.php`

**Database:**
- `database/jaws.db` - SQLite database
- `database/migrations/001_initial_schema.sql` - Initial schema
- `database/init_database.php` - Database initialization script
- `database/migrate_from_csv.php` - CSV to SQLite migration

**Tests:**
- `tests/Unit/Domain/` - Domain layer unit tests
- `tests/Integration/Infrastructure/` - Infrastructure integration tests
- `Tests/api_test.php` - HTTP API tests
- `Tests/JAWS_API.postman_collection.json` - Postman collection

**Documentation:**
- `README.md` - Comprehensive project documentation
- `CLAUDE.md` - This file
- `docs/JAWS_Clean_Architecture_Refactoring_Plan.md` - Refactoring plan
- `docs/PHASE_*_COMPLETE.md` - Phase completion summaries

**Legacy (Reference Only):**
- `legacy/` - Original pre-refactoring codebase (archived)

## Critical Success Factors

1. **Preserve Business Logic:** Selection and Assignment algorithms must produce identical results to legacy system
2. **Maintain Determinism:** Same inputs always produce same outputs (use seeded randomization)
3. **Respect Layer Boundaries:** Never violate dependency direction (outer → inner only)
4. **Test Thoroughly:** Unit tests for Domain, integration tests for Infrastructure, API tests for Presentation
5. **Document Changes:** Update CLAUDE.md and README.md when architecture changes

## Migration Notes

**From Legacy Architecture:**
- Original codebase in `legacy/` folder (preserved for reference)
- CSV files migrated to SQLite via `database/migrate_from_csv.php`
- Selection/Assignment algorithms preserved character-for-character in Domain layer
- `season_update.php` replaced by `ProcessSeasonUpdateUseCase`
- PHP forms replaced by REST API endpoints

**Key Migrations:**
- `legacy/Libraries/Selection/` → `src/Domain/Service/SelectionService.php`
- `legacy/Libraries/Assignment/` → `src/Domain/Service/AssignmentService.php`
- `legacy/Libraries/Fleet/` → `src/Domain/Collection/Fleet.php` + `src/Infrastructure/Persistence/SQLite/BoatRepository.php`
- `legacy/Libraries/Squad/` → `src/Domain/Collection/Squad.php` + `src/Infrastructure/Persistence/SQLite/CrewRepository.php`
- `legacy/season_update.php` → `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php`

## Future Enhancements

**Phase 8: PostgreSQL Migration**
- Migrate from SQLite to PostgreSQL for production scalability
- Update repository implementations for PostgreSQL-specific features
- Deploy to AWS RDS

**Phase 9: Authentication System**
- Implement JWT-based authentication
- Add user registration with password hashing
- Add password reset and email verification

**Phase 10: Frontend Refactoring**
- Build modern SPA (React/Vue) to consume REST API
- Real-time updates via WebSockets
- Mobile-responsive design

**Phase 11: Testing & CI/CD**
- Expand test coverage to >80%
- Set up GitHub Actions for CI/CD
- Automated deployments to staging/production
