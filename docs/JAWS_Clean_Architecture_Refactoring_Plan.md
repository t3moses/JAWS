# JAWS Clean Architecture Refactoring Plan

## Executive Summary

Refactor the JAWS PHP application from a traditional form-based architecture to **clean architecture** (hexagonal/ports and adapters pattern) with a REST API, while **preserving all proven business logic algorithms**.

**Key Decisions:**
- **Database:** SQLite for development (migration path to PostgreSQL documented)
- **Authentication:** Preserve name-based lookup (no JWT/password system in this phase)
- **Deployment:** Architecture remains deployment-agnostic (works on Lightsail, Lambda, containers)
- **Algorithms:** Selection and Assignment algorithms preserved exactly as-is (proven business logic)
- **Timeline:** 12 weeks with incremental migration and parallel operation

---

## 1. Clean Architecture Layers

### Layer 1: Domain (Core Business Logic)
**Location:** `/src/Domain`
**Dependencies:** None (pure PHP)

**Responsibilities:**
- Business entities (Boat, Crew, Event, Season)
- Value objects (BoatKey, CrewKey, Rank, SkillLevel)
- Domain services (SelectionService, AssignmentService, RankingService)
- Business rules and constraints

**Critical Requirement:** Preserve Selection and Assignment algorithms character-for-character during initial migration.

### Layer 2: Application (Use Cases)
**Location:** `/src/Application`
**Dependencies:** Domain layer only

**Responsibilities:**
- Use cases (RegisterBoat, UpdateAvailability, ProcessSeasonUpdate, etc.)
- Repository interfaces (ports)
- Service interfaces (EmailService, CalendarService)
- DTOs (Request/Response objects)
- Application-specific exceptions

### Layer 3: Infrastructure (External Adapters)
**Location:** `/src/Infrastructure`
**Dependencies:** Application + Domain layers

**Responsibilities:**
- SQLite repository implementations
- AWS SES email service implementation
- iCalendar service implementation
- CSV migration utilities
- Database schema management

### Layer 4: Presentation (API)
**Location:** `/src/Presentation`
**Dependencies:** Application layer

**Responsibilities:**
- HTTP controllers
- Request validation
- Response formatting
- Name-based authentication middleware
- Error handling middleware

**Dependency Direction:** Domain ← Application ← Infrastructure ← Presentation

---

## 2. Directory Structure

```
/d/source/repos/JAWS/
├── composer.json                          # PSR-4 autoloading + dependencies
├── config/
│   ├── config.php                        # Application configuration
│   ├── container.php                     # Dependency injection container
│   └── routes.php                        # API route definitions
├── public/
│   └── index.php                         # Application entry point
├── src/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── Boat.php
│   │   │   ├── Crew.php
│   │   │   ├── Event.php
│   │   │   └── Season.php
│   │   ├── ValueObject/
│   │   │   ├── BoatKey.php
│   │   │   ├── CrewKey.php
│   │   │   ├── EventId.php
│   │   │   ├── Rank.php
│   │   │   ├── AvailabilityStatus.php
│   │   │   └── SkillLevel.php
│   │   ├── Service/
│   │   │   ├── SelectionService.php      # CRITICAL: Preserve algorithm
│   │   │   ├── AssignmentService.php     # CRITICAL: Preserve algorithm
│   │   │   ├── RankingService.php
│   │   │   └── FlexService.php
│   │   ├── Collection/
│   │   │   ├── Fleet.php
│   │   │   └── Squad.php
│   │   └── Enum/
│   │       ├── AssignmentRule.php
│   │       ├── RankDimension.php
│   │       └── TimeSource.php
│   │
│   ├── Application/
│   │   ├── UseCase/
│   │   │   ├── Boat/
│   │   │   │   ├── RegisterBoatUseCase.php
│   │   │   │   ├── UpdateBoatAvailabilityUseCase.php
│   │   │   │   └── GetBoatUseCase.php
│   │   │   ├── Crew/
│   │   │   │   ├── RegisterCrewUseCase.php
│   │   │   │   ├── UpdateCrewAvailabilityUseCase.php
│   │   │   │   ├── GetCrewUseCase.php
│   │   │   │   └── GetUserAssignmentsUseCase.php
│   │   │   ├── Event/
│   │   │   │   ├── GetAllEventsUseCase.php
│   │   │   │   ├── GetEventUseCase.php
│   │   │   │   └── GetEventAssignmentsUseCase.php
│   │   │   ├── Season/
│   │   │   │   ├── ProcessSeasonUpdateUseCase.php  # Replaces season_update.php
│   │   │   │   ├── GenerateFlotillaUseCase.php
│   │   │   │   └── UpdateConfigUseCase.php
│   │   │   └── Admin/
│   │   │       ├── GetMatchingDataUseCase.php
│   │   │       └── SendNotificationsUseCase.php
│   │   ├── Port/
│   │   │   ├── Repository/
│   │   │   │   ├── BoatRepositoryInterface.php
│   │   │   │   ├── CrewRepositoryInterface.php
│   │   │   │   ├── EventRepositoryInterface.php
│   │   │   │   └── SeasonRepositoryInterface.php
│   │   │   └── Service/
│   │   │       ├── EmailServiceInterface.php
│   │   │       ├── CalendarServiceInterface.php
│   │   │       └── TimeServiceInterface.php
│   │   ├── DTO/
│   │   │   ├── Request/
│   │   │   │   ├── RegisterBoatRequest.php
│   │   │   │   ├── UpdateAvailabilityRequest.php
│   │   │   │   └── RegisterCrewRequest.php
│   │   │   └── Response/
│   │   │       ├── BoatResponse.php
│   │   │       ├── CrewResponse.php
│   │   │       ├── EventResponse.php
│   │   │       ├── AssignmentResponse.php
│   │   │       └── FlotillaResponse.php
│   │   └── Exception/
│   │       ├── BoatNotFoundException.php
│   │       ├── CrewNotFoundException.php
│   │       └── ValidationException.php
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── SQLite/
│   │   │   │   ├── Connection.php
│   │   │   │   ├── BoatRepository.php
│   │   │   │   ├── CrewRepository.php
│   │   │   │   ├── EventRepository.php
│   │   │   │   ├── SeasonRepository.php
│   │   │   │   └── Schema.php
│   │   │   └── CSV/
│   │   │       ├── CsvBoatRepository.php      # Temporary legacy support
│   │   │       ├── CsvCrewRepository.php      # Temporary legacy support
│   │   │       └── CsvMigration.php           # CSV → SQLite migration
│   │   ├── Service/
│   │   │   ├── AwsSesEmailService.php
│   │   │   ├── ICalendarService.php
│   │   │   └── SystemTimeService.php
│   │   └── Http/
│   │       └── NameBasedAuthenticator.php
│   │
│   └── Presentation/
│       ├── Controller/
│       │   ├── EventController.php
│       │   ├── AvailabilityController.php
│       │   ├── AssignmentController.php
│       │   └── AdminController.php
│       ├── Middleware/
│       │   ├── NameAuthMiddleware.php
│       │   ├── ErrorHandlerMiddleware.php
│       │   └── JsonResponseMiddleware.php
│       ├── Request/
│       │   └── Validator.php
│       └── Response/
│           ├── JsonResponse.php
│           └── ErrorResponse.php
│
├── database/
│   ├── jaws.db                            # SQLite database
│   └── migrations/
│       └── 001_initial_schema.sql
│
├── legacy/                                # Original files (reference during migration)
│   ├── Libraries/
│   └── *.php
│
└── tests/
    ├── Unit/
    │   ├── Domain/
    │   │   ├── SelectionServiceTest.php
    │   │   └── AssignmentServiceTest.php
    │   └── Application/
    └── Integration/
        ├── Persistence/SQLite/
        └── Api/
```

---

## 3. Database Schema (SQLite)

### boats
```sql
CREATE TABLE boats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    owner_first_name TEXT NOT NULL,
    owner_last_name TEXT NOT NULL,
    owner_email TEXT NOT NULL,
    owner_mobile TEXT,
    min_berths INTEGER NOT NULL DEFAULT 1,
    max_berths INTEGER NOT NULL DEFAULT 1,
    assistance_required TEXT CHECK(assistance_required IN ('Yes', 'No')) DEFAULT 'No',
    social_preference TEXT CHECK(social_preference IN ('Yes', 'No')) DEFAULT 'No',
    rank_flexibility INTEGER DEFAULT 1,
    rank_absence INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_boats_key ON boats(key);
```

### crews
```sql
CREATE TABLE crews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    partner_key TEXT,
    email TEXT NOT NULL,
    mobile TEXT,
    social_preference TEXT CHECK(social_preference IN ('Yes', 'No')) DEFAULT 'No',
    membership_number TEXT,
    skill INTEGER CHECK(skill IN (0, 1, 2)) DEFAULT 0,
    experience TEXT,
    rank_commitment INTEGER DEFAULT 0,
    rank_flexibility INTEGER DEFAULT 1,
    rank_membership INTEGER DEFAULT 0,
    rank_absence INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_crews_key ON crews(key);
CREATE INDEX idx_crews_name ON crews(first_name, last_name);
```

### events
```sql
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT NOT NULL UNIQUE,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    finish_time TIME NOT NULL,
    status TEXT CHECK(status IN ('upcoming', 'past', 'cancelled')) DEFAULT 'upcoming',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_events_date ON events(event_date);
```

### boat_availability
```sql
CREATE TABLE boat_availability (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    boat_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    berths INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(boat_id, event_id)
);
CREATE INDEX idx_boat_availability_event ON boat_availability(event_id);
```

### crew_availability
```sql
CREATE TABLE crew_availability (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crew_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    status INTEGER CHECK(status IN (0, 1, 2, 3)) DEFAULT 0,
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(crew_id, event_id)
);
CREATE INDEX idx_crew_availability_event ON crew_availability(event_id);
```

### boat_history
```sql
CREATE TABLE boat_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    boat_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    participated TEXT CHECK(participated IN ('Y', '')) DEFAULT '',
    FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(boat_id, event_id)
);
```

### crew_history
```sql
CREATE TABLE crew_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crew_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    boat_key TEXT DEFAULT '',
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(crew_id, event_id)
);
```

### crew_whitelist
```sql
CREATE TABLE crew_whitelist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crew_id INTEGER NOT NULL,
    boat_key TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE,
    UNIQUE(crew_id, boat_key)
);
```

### season_config
```sql
CREATE TABLE season_config (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    year INTEGER NOT NULL,
    source TEXT CHECK(source IN ('simulated', 'production')) DEFAULT 'production',
    simulated_date DATE,
    start_time TIME NOT NULL,
    finish_time TIME NOT NULL,
    blackout_from TIME NOT NULL,
    blackout_to TIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO season_config (id, year, source, start_time, finish_time, blackout_from, blackout_to)
VALUES (1, 2026, 'production', '12:45:00', '17:00:00', '10:00:00', '18:00:00');
```

### flotillas
```sql
CREATE TABLE flotillas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT NOT NULL UNIQUE,
    flotilla_data TEXT NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);
```

---

## 4. Critical Files to Migrate

These files contain the core business logic that must be preserved exactly:

### 4.1 Selection Algorithm
**Source:** `d:\source\repos\JAWS\Libraries\Selection\src\Selection.php`
**Target:** `src/Domain/Service/SelectionService.php`

**Critical Methods to Preserve:**
- `shuffle()` - Deterministic seeding with `crc32(event_id)`
- `is_greater()` - Lexicographic rank comparison
- `bubble()` - Bubble sort algorithm
- `cut()` - Capacity matching (3 cases)
- `case_1()`, `case_2()`, `case_3()` - Specific capacity scenarios

**Migration Strategy:** Copy character-for-character initially, then refactor to use new entity classes with minimal changes.

### 4.2 Assignment Algorithm
**Source:** `d:\source\repos\JAWS\Libraries\Assignment\src\Assignment.php`
**Target:** `src/Domain/Service/AssignmentService.php`

**Critical Methods to Preserve:**
- 6 rule enums: ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT
- `crew_loss()` - Calculate violation severity for each rule
- `crew_grad()` - Calculate mitigation capacity for each rule
- `bad_swap()` - Swap validation logic
- `best_swap()` - Greedy swap selection
- `assign()` - Main optimization loop with unlocked_crews tracking

**Migration Strategy:** Copy exactly, then incrementally refactor to use new domain entities.

### 4.3 Orchestration Logic
**Source:** `d:\source\repos\JAWS\season_update.php`
**Target:** `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php`

**Pipeline to Preserve:**
1. Load Fleet, Squad, Season
2. For each future event:
   - Selection phase
   - Event consolidation
   - Assignment optimization (next event only)
   - Update availability statuses
   - Update history
   - Save flotilla
3. Persist changes
4. Generate output

### 4.4 Data Persistence Patterns
**Sources:**
- `d:\source\repos\JAWS\Libraries\Fleet\src\Fleet.php`
- `d:\source\repos\JAWS\Libraries\Squad\src\Squad.php`

**Migration Insights:**
- CSV uses semicolon-delimited arrays for rank, berths, history, availability
- Array indices correspond to event order in config
- Key generation uses lowercase, no spaces via `Name::key_from_strings()`

---

## 5. API Endpoints (Simplified for Name-Based Auth)

### Public Endpoints

**GET /api/events**
- Returns all events for the season
- Response: `{success: true, events: [...]}`

**GET /api/events/:id**
- Returns specific event with flotilla assignments
- Response: `{success: true, event: {...}, crewed_boats: [...], waitlist: [...]}`

### Authenticated Endpoints (Name-Based)

**PATCH /api/users/me/availability**
- Headers: `X-User-FirstName`, `X-User-LastName`
- Body: `{availabilities: {"Fri May 29": 2, ...}}`
- Response: `{success: true, message: "..."}`

**GET /api/assignments**
- Headers: `X-User-FirstName`, `X-User-LastName`
- Returns user's assignments across all events
- Response: `{success: true, assignments: [...]}`

### Admin Endpoints

**GET /api/admin/matching/:eventId**
- Returns matching data (available boats/crews, capacity analysis)
- Response: `{success: true, available_boats: [...], available_crews: [...], capacity: {...}}`

---

## 6. Implementation Phases

### Phase 1: Foundation (Weeks 1-2)
**Goal:** Establish directory structure and database schema

**Tasks:**
1. Create `/src` directory structure (Domain, Application, Infrastructure, Presentation)
2. Configure `composer.json` with PSR-4 autoloading
3. Install dependencies: PHPUnit, PDO SQLite extension
4. Create `database/migrations/001_initial_schema.sql` with all table definitions
5. Create SQLite database: `database/jaws.db`
6. Apply schema and validate
7. Move original files to `/legacy` (Libraries, entry point PHP files)

**Validation:**
- Directory structure matches plan
- Composer autoloading works (`composer dump-autoload`)
- Database schema applies without errors
- All tables and indexes created

### Phase 2: Domain Layer (Weeks 3-4)
**Goal:** Migrate core business logic with zero algorithm changes

**Week 3: Entities & Value Objects**
1. Create `BoatKey.php`, `CrewKey.php`, `EventId.php` value objects
2. Create `Rank.php` immutable value object (multi-dimensional tensor)
3. Create `SkillLevel.php`, `AvailabilityStatus.php` enums
4. Migrate `Boat.php` entity (use value objects, encapsulate behavior)
5. Migrate `Crew.php` entity (use value objects, encapsulate behavior)
6. Create `Fleet.php` and `Squad.php` collections (in-memory)

**Week 4: Domain Services (CRITICAL)**
1. **Copy** `/legacy/Libraries/Selection/src/Selection.php` → `SelectionService.php`
2. Minimal refactoring: use new Boat/Crew/Rank classes
3. Write 10+ unit tests for Selection (deterministic shuffle, lexicographic comparison, capacity matching)
4. **Copy** `/legacy/Libraries/Assignment/src/Assignment.php` → `AssignmentService.php`
5. Minimal refactoring: use new domain entities
6. Write 10+ unit tests for Assignment (6 rules, loss/grad calculations, swap logic)
7. Create `RankingService.php` and `FlexService.php`

**Validation:**
- All unit tests pass
- Deterministic behavior verified (same inputs = same outputs)
- Compare outputs with legacy system (must be identical)

### Phase 3: Infrastructure Layer (Weeks 5-6)
**Goal:** Implement database persistence

**Week 5: Repository Interfaces**
1. Create `BoatRepositoryInterface.php` (findByKey, save, getAvailability, setHistory, etc.)
2. Create `CrewRepositoryInterface.php` (findByName, save, getAvailability, addToWhitelist, etc.)
3. Create `EventRepositoryInterface.php` (getFutureEvents, getNextEvent, etc.)
4. Create `SeasonRepositoryInterface.php` (getConfig, saveFlotilla, etc.)
5. Create `EmailServiceInterface.php`, `CalendarServiceInterface.php`

**Week 6: Repository Implementations**
1. Create `Connection.php` (PDO manager with singleton pattern)
2. Implement `BoatRepository.php` with all interface methods
3. Implement `CrewRepository.php` with all interface methods
4. Implement `EventRepository.php` and `SeasonRepository.php`
5. Write integration tests for each repository (in-memory SQLite)
6. Create `CsvMigration.php` script
7. Run migration: CSV → SQLite (backup CSV files first!)
8. Validate data integrity (row counts, spot checks, foreign keys)

**Validation:**
- All integration tests pass
- CSV data successfully migrated
- No data loss (compare row counts)
- Foreign key constraints enforced

### Phase 4: Application Layer (Weeks 7-8)
**Goal:** Implement use cases

**Week 7: DTOs & Use Cases (Boat/Crew)**
1. Create request DTOs (`RegisterBoatRequest`, `UpdateAvailabilityRequest`, etc.)
2. Create response DTOs (`BoatResponse`, `CrewResponse`, `EventResponse`, etc.)
3. Implement `RegisterBoatUseCase`
4. Implement `RegisterCrewUseCase`
5. Implement `UpdateBoatAvailabilityUseCase`
6. Implement `UpdateCrewAvailabilityUseCase`
7. Write use case tests

**Week 8: Core Use Cases**
1. Implement `GetAllEventsUseCase`
2. Implement `GetEventUseCase`
3. Implement `GetUserAssignmentsUseCase`
4. Implement `GetMatchingDataUseCase` (admin)
5. **CRITICAL:** Implement `ProcessSeasonUpdateUseCase` (replaces season_update.php)
   - Orchestrates Selection → Consolidation → Assignment → Persistence
   - Updates availability statuses and history
   - Saves flotillas
6. Write comprehensive tests for `ProcessSeasonUpdateUseCase`

**Validation:**
- All use case tests pass
- `ProcessSeasonUpdateUseCase` produces identical flotillas to legacy season_update.php
- End-to-end flow verified

### Phase 5: Presentation Layer (Weeks 9-10)
**Goal:** Implement REST API

**Week 9: Controllers**
1. Set up routing (use Slim framework or native router)
2. Create `config/routes.php` (map HTTP methods/paths to controllers)
3. Create `config/container.php` (dependency injection)
4. Implement `EventController` (getAll, getOne)
5. Implement `AvailabilityController` (update)
6. Implement `AssignmentController` (getUserAssignments)
7. Implement `AdminController` (getMatchingData)
8. Create `JsonResponse` and `ErrorResponse` wrappers

**Week 10: Middleware & Testing**
1. Implement `NameAuthMiddleware` (extract first_name/last_name from headers)
2. Implement `ErrorHandlerMiddleware` (catch exceptions, format errors)
3. Implement request validation helper
4. Write API endpoint tests (PHPUnit)
5. Create Postman collection for manual testing
6. Test all endpoints end-to-end

**Validation:**
- All API endpoints return correct responses
- Name-based authentication works
- Error handling covers edge cases (404, 400, 500)
- Postman tests pass

### Phase 6: Parallel Operation (Week 11)
**Goal:** Run legacy and new systems side-by-side

**Strategy:**
1. Keep legacy PHP forms operational
2. Both systems read/write to SQLite database
3. Add comparison logging (legacy output vs new API output)
4. Monitor for discrepancies
5. Fix any bugs found

**Tasks:**
1. Configure legacy forms to use SQLite (via CSV-compatible repositories)
2. Add logging to compare flotilla generation results
3. Run parallel tests with real user data
4. Investigate and fix any differences
5. Performance testing

**Validation:**
- Legacy forms still functional
- API produces identical results to legacy system
- No data corruption
- Performance acceptable

### Phase 7: Cutover (Week 12)
**Goal:** Decommission legacy system

**Tasks:**
1. Final data validation and backup
2. Redirect legacy entry points to API (or display "use API" message)
3. Archive `/legacy` directory (keep for reference)
4. Update deployment scripts for AWS Lightsail
5. Deploy to production
6. Monitor for issues
7. Update documentation

**Validation:**
- Production deployment successful
- API operational on AWS Lightsail
- Users can access new system
- Monitoring alerts configured

---

## 7. Testing Strategy

### Unit Tests (Domain Layer)
**SelectionService:**
- Test deterministic shuffle (same seed = same order)
- Test lexicographic rank comparison (all 4 dimensions for crew, 2 for boat)
- Test capacity matching Case 1 (too few crews)
- Test capacity matching Case 2 (too many crews)
- Test capacity matching Case 3 (perfect fit)

**AssignmentService:**
- Test each of 6 rules (ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT)
- Test crew_loss() calculations
- Test crew_grad() calculations
- Test swap validation (bad_swap, bad_rule_swap)
- Test greedy optimization loop

### Integration Tests (Infrastructure Layer)
**Repositories:**
- Test save and retrieve (Boat, Crew)
- Test availability updates (per event)
- Test history updates (participation tracking)
- Test whitelist management
- Test complex queries (findAvailableForEvent)

### API Tests (Presentation Layer)
**Endpoints:**
- Test GET /api/events (returns all events)
- Test GET /api/events/:id (returns specific event with flotilla)
- Test PATCH /api/users/me/availability (updates availability)
- Test GET /api/assignments (returns user assignments)
- Test authentication failures (invalid names)
- Test error responses (404, 400, 500)

---

## 8. Risk Assessment & Mitigation

### Risk 1: Algorithm Behavior Changes
**Impact:** CRITICAL - Incorrect assignments damage user trust
**Probability:** Medium

**Mitigation:**
- Copy Selection/Assignment code character-for-character initially
- Write extensive unit tests with known inputs/outputs from `/Tests/Test cases.numbers`
- Run legacy and new systems in parallel with output comparison
- Use deterministic seeding to validate reproducibility
- Get user acceptance testing before cutover

### Risk 2: Data Loss During CSV → SQLite Migration
**Impact:** CRITICAL - Loss of historical data
**Probability:** Low

**Mitigation:**
- Back up CSV files before migration
- Write migration script with transaction safety
- Implement rollback mechanism
- Validate row counts post-migration
- Manual spot-checks of migrated data
- Keep CSV backups indefinitely

### Risk 3: Name-Based Authentication Security
**Impact:** Medium - Unauthorized access
**Probability:** Medium

**Mitigation:**
- Document security limitations clearly
- Plan for future JWT/password system (noted in roadmap)
- Implement rate limiting on API endpoints
- Add audit logging for sensitive operations
- Consider email verification for new registrations

### Risk 4: Breaking Flotilla Structure
**Impact:** High - HTML/Calendar generation fails
**Probability:** Medium

**Mitigation:**
- Preserve flotilla array structure exactly
- Write serialization tests
- Test end-to-end workflow (registration → assignment → HTML output)
- Keep legacy HTML/Calendar libraries operational

### Risk 5: Performance Degradation
**Impact:** Medium - Slower response times
**Probability:** Low

**Mitigation:**
- Add database indexes on foreign keys and frequently queried columns
- Profile database queries during testing
- Implement flotilla caching
- Monitor response times during parallel operation
- Consider connection pooling for production

---

## 9. Verification Steps

After each phase, verify:

### Phase 1 (Foundation)
- [ ] Directory structure created
- [ ] Composer autoloading configured
- [ ] SQLite database created with schema applied
- [ ] All tables and indexes exist

### Phase 2 (Domain Layer)
- [ ] All entity classes created
- [ ] Value objects implemented (immutable)
- [ ] SelectionService unit tests pass (10+)
- [ ] AssignmentService unit tests pass (10+)
- [ ] Deterministic behavior verified

### Phase 3 (Infrastructure Layer)
- [ ] All repository interfaces defined
- [ ] All repository implementations complete
- [ ] Integration tests pass
- [ ] CSV data migrated to SQLite
- [ ] Data integrity validated (row counts match)

### Phase 4 (Application Layer)
- [ ] All DTOs created
- [ ] All use cases implemented
- [ ] ProcessSeasonUpdateUseCase produces identical output to legacy
- [ ] Use case tests pass

### Phase 5 (Presentation Layer)
- [ ] All controllers implemented
- [ ] Routing configured
- [ ] Middleware working (auth, error handling)
- [ ] API tests pass
- [ ] Postman collection validates all endpoints

### Phase 6 (Parallel Operation)
- [ ] Legacy forms still work
- [ ] API produces identical results
- [ ] No data corruption
- [ ] Performance acceptable

### Phase 7 (Cutover)
- [ ] Production deployment successful
- [ ] Users can access API
- [ ] Legacy system archived
- [ ] Monitoring configured

---

## 10. Post-Migration Roadmap

### Future Enhancements (Post-Cutover)

**Phase 8: PostgreSQL Migration**
- Create PostgreSQL schema (identical to SQLite)
- Write migration script: SQLite → PostgreSQL
- Update repository implementations for PostgreSQL-specific features
- Deploy to AWS RDS or Aurora Serverless

**Phase 9: Authentication System**
- Implement JWT-based authentication
- Add user registration with password hashing (bcrypt)
- Add password reset via email
- Add email verification
- Migrate name-based lookup to proper user accounts

**Phase 10: Frontend Refactoring**
- Build modern SPA (React/Vue) to replace legacy forms
- Implement client-side routing
- Add real-time updates (WebSockets)
- Improve mobile responsiveness

**Phase 11: Testing & CI/CD**
- Expand test coverage to >80%
- Set up CI/CD pipeline (GitHub Actions)
- Automated deployments to staging/production
- Integration with code quality tools (PHPStan, Psalm)

---

## 11. Critical Success Factors

1. **Preserve Business Logic:** Selection and Assignment algorithms must produce identical results
2. **Data Integrity:** Zero data loss during CSV → SQLite migration
3. **Incremental Migration:** Parallel operation ensures safe transition
4. **Comprehensive Testing:** Unit, integration, and API tests validate correctness
5. **User Acceptance:** Get feedback during parallel operation phase before cutover

---

## 12. Dependencies

### Composer Packages
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
    "phpunit/phpunit": "^10.0"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

### System Requirements
- PHP 8.1+
- PDO SQLite extension
- Composer
- Git
- AWS Lightsail (or any PHP hosting environment)

---

## 13. Deployment Notes (AWS Lightsail)

### File Permissions
```bash
# After uploading jaws.db
chgrp www-data /var/www/html/database/jaws.db
chmod 664 /var/www/html/database/jaws.db
chgrp www-data /var/www/html/database
chmod 775 /var/www/html/database
```

### Composer
```bash
cd /var/www/html
composer install --no-dev --optimize-autoloader
```

### Environment Variables
Create `.env`:
```
DB_PATH=/var/www/html/database/jaws.db
SES_REGION=ca-central-1
SES_SMTP_USERNAME=your_username
SES_SMTP_PASSWORD=your_password
```

---

## Summary

This plan provides a comprehensive roadmap to refactor JAWS from a traditional PHP application to clean architecture with:

- **4-layer architecture** (Domain, Application, Infrastructure, Presentation)
- **SQLite database** (with migration path to PostgreSQL)
- **REST API** following implementation plan endpoints
- **Preserved business logic** (Selection and Assignment algorithms intact)
- **12-week timeline** with incremental migration and parallel operation
- **Comprehensive testing** at all layers
- **Risk mitigation** for critical areas

The refactoring maintains all proven functionality while establishing a modern, maintainable, testable architecture that supports future enhancements.
