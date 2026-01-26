# Phase 4: Application Layer - COMPLETE

**Status:** ✅ COMPLETE
**Date Completed:** 2026-01-25

---

## Overview

Phase 4 successfully implemented the Application Layer of the clean architecture refactoring. This layer contains use cases that orchestrate domain logic, along with DTOs for data transfer and application-specific exceptions.

---

## Files Created (23 total)

### Exceptions (5 files)

Located in: `src/Application/Exception/`

1. **BoatNotFoundException.php**
   - Thrown when boat lookup fails
   - Contains BoatKey for context

2. **CrewNotFoundException.php**
   - Thrown when crew lookup fails
   - Contains CrewKey for context

3. **EventNotFoundException.php**
   - Thrown when event lookup fails
   - Contains EventId for context

4. **ValidationException.php**
   - Thrown when request validation fails
   - Contains field-level error messages
   - Formats errors as "field: error" message

5. **BlackoutWindowException.php**
   - Thrown when registration attempted during blackout window
   - Contains blackout start/end times for context

### Request DTOs (4 files)

Located in: `src/Application/DTO/Request/`

1. **RegisterBoatRequest.php**
   - Boat registration data
   - Validation: required fields, email format, numeric values
   - Fields: displayName, ownerFirstName, ownerLastName, ownerEmail, ownerMobile, minBerths, maxBerths, assistanceRequired, socialPreference

2. **RegisterCrewRequest.php**
   - Crew registration data
   - Validation: required fields, email format, skill level enum
   - Fields: displayName, firstName, lastName, partnerFirstName, partnerLastName, email, mobile, socialPreference, membershipNumber, skill, experience

3. **UpdateAvailabilityRequest.php**
   - Availability updates for multiple events
   - Validation: non-empty availabilities array
   - Format: `['eventId' => statusOrBerths, ...]`

4. **UpdateConfigRequest.php**
   - Season configuration updates
   - Validation: source enum, date format (YYYY-MM-DD), time format (HH:MM:SS), year range
   - Fields: source, simulatedDate, year, startTime, finishTime, blackoutFrom, blackoutTo

### Response DTOs (5 files)

Located in: `src/Application/DTO/Response/`

1. **BoatResponse.php**
   - Boat entity serialization
   - Static factory: `fromEntity(Boat)`
   - Fields: key, displayName, ownerFirstName, ownerLastName, ownerEmail, minBerths, maxBerths, assistanceRequired, socialPreference

2. **CrewResponse.php**
   - Crew entity serialization
   - Static factory: `fromEntity(Crew)`
   - Fields: key, displayName, firstName, lastName, partnerKey, email, mobile, socialPreference, membershipNumber, skill, experience

3. **EventResponse.php**
   - Event data serialization
   - Static factory: `fromArray(array)`
   - Fields: eventId, eventDate, startTime, finishTime, status

4. **FlotillaResponse.php**
   - Flotilla assignment serialization
   - Static factory: `fromFlotilla(string, array)`
   - Fields: eventId, crewedBoats (boat + crews), waitlistBoats, waitlistCrews

5. **AssignmentResponse.php**
   - Crew assignment serialization
   - Fields: eventId, eventDate, startTime, finishTime, availabilityStatus, boatName, boatKey, crewmates

### Use Cases - Boat (2 files)

Located in: `src/Application/UseCase/Boat/`

1. **RegisterBoatUseCase.php**
   - Handles boat registration (create or update)
   - Dependencies: BoatRepositoryInterface
   - Validates request, generates BoatKey, creates or updates entity
   - Returns: BoatResponse

2. **UpdateBoatAvailabilityUseCase.php**
   - Updates berths offered for multiple events
   - Dependencies: BoatRepositoryInterface
   - Validates request, finds boat, updates berths per event
   - Returns: BoatResponse

### Use Cases - Crew (3 files)

Located in: `src/Application/UseCase/Crew/`

1. **RegisterCrewUseCase.php**
   - Handles crew registration (create or update)
   - Dependencies: CrewRepositoryInterface, TimeServiceInterface
   - Validates request, generates CrewKey, creates or updates entity
   - Returns: CrewResponse

2. **UpdateCrewAvailabilityUseCase.php**
   - Updates availability status for multiple events
   - Dependencies: CrewRepositoryInterface
   - Validates request, finds crew, updates availability per event
   - Returns: CrewResponse

3. **GetUserAssignmentsUseCase.php**
   - Retrieves crew assignments across all events
   - Dependencies: CrewRepositoryInterface, EventRepositoryInterface, SeasonRepositoryInterface
   - Searches flotilla data for crew assignments
   - Returns: array<AssignmentResponse>

### Use Cases - Event (2 files)

Located in: `src/Application/UseCase/Event/`

1. **GetAllEventsUseCase.php**
   - Retrieves all events for the season
   - Dependencies: EventRepositoryInterface
   - Returns: array<EventResponse>

2. **GetEventUseCase.php**
   - Retrieves specific event with flotilla assignments
   - Dependencies: EventRepositoryInterface, SeasonRepositoryInterface
   - Returns: array{event: EventResponse, flotilla: FlotillaResponse|null}

### Use Cases - Season (3 files)

Located in: `src/Application/UseCase/Season/`

1. **ProcessSeasonUpdateUseCase.php** ⭐ **CRITICAL**
   - Orchestrates Selection → Assignment → Persistence pipeline
   - Replaces legacy `season_update.php`
   - Dependencies: All 4 repository interfaces, SelectionService, AssignmentService
   - Pipeline:
     1. Load Fleet, Squad, Season configuration
     2. For each future event:
        - Run Selection (rank and capacity match)
        - Consolidate event (form flotilla structure)
        - Run Assignment optimization (next event only)
        - Update availability statuses (GUARANTEED)
        - Save flotilla
     3. Persist all changes
   - Returns: array{success: bool, events_processed: int, flotillas_generated: int}

2. **GenerateFlotillaUseCase.php**
   - Retrieves or generates flotilla data for specific event
   - Dependencies: EventRepositoryInterface, SeasonRepositoryInterface
   - Returns: array{event_id, crewed_boats, waitlist_boats, waitlist_crews}

3. **UpdateConfigUseCase.php**
   - Updates season configuration
   - Replaces legacy `admin_update.php`
   - Dependencies: SeasonRepositoryInterface
   - Returns: array{success: bool, message: string}

### Use Cases - Admin (2 files)

Located in: `src/Application/UseCase/Admin/`

1. **GetMatchingDataUseCase.php**
   - Retrieves capacity analysis for event
   - Dependencies: BoatRepositoryInterface, CrewRepositoryInterface, EventRepositoryInterface
   - Calculates: total berths, total crews, min/max berths, capacity scenario
   - Returns: array{available_boats, available_crews, capacity}

2. **SendNotificationsUseCase.php**
   - Sends email notifications and calendar invites
   - Dependencies: EventRepositoryInterface, SeasonRepositoryInterface, EmailServiceInterface, CalendarServiceInterface
   - Sends to boat owners and crew members
   - Returns: array{success: bool, emails_sent: int, message: string}

---

## Key Achievements

### 1. Critical Use Case Implementation ⭐

**ProcessSeasonUpdateUseCase** is the cornerstone of the refactoring:
- Preserves the proven Selection and Assignment algorithms
- Orchestrates multi-phase pipeline with proper separation of concerns
- Only optimizes next event (as per legacy behavior)
- Updates availability statuses correctly
- Saves flotilla data for HTML/calendar generation

### 2. Complete DTO Layer

All data transfer objects implement:
- Immutable readonly properties
- Validation methods with field-level errors
- Static factory methods for entity → DTO conversion
- `toArray()` methods for JSON serialization

### 3. Comprehensive Use Case Coverage

Implemented use cases for all major workflows:
- Registration (boat, crew)
- Availability updates (boat berths, crew status)
- Event retrieval (all events, specific event with flotilla)
- Season orchestration (critical pipeline)
- Admin functions (matching data, notifications)
- User functions (assignment lookup)

### 4. Proper Exception Hierarchy

Application-specific exceptions with contextual data:
- Entity not found (includes key/ID)
- Validation failed (includes field errors)
- Blackout window (includes time range)

---

## Dependency Inversion

All use cases depend on **interfaces** (ports), not concrete implementations:

**Repository Ports:**
- BoatRepositoryInterface
- CrewRepositoryInterface
- EventRepositoryInterface
- SeasonRepositoryInterface

**Service Ports:**
- EmailServiceInterface
- CalendarServiceInterface
- TimeServiceInterface

This ensures the Application Layer remains independent of Infrastructure details.

---

## Request Validation Pattern

All request DTOs implement consistent validation:

```php
public function validate(): array
{
    $errors = [];

    // Field-level validation
    if (empty($this->requiredField)) {
        $errors['required_field'] = 'Error message';
    }

    return $errors;
}
```

Use cases check validation before processing:

```php
$errors = $request->validate();
if (!empty($errors)) {
    throw new ValidationException($errors);
}
```

---

## ProcessSeasonUpdateUseCase Pipeline

The critical orchestration use case implements this flow:

1. **Load Data**
   - Fleet (all boats)
   - Squad (all crews)
   - Future events

2. **For Each Future Event:**

   a. **Selection Phase**
   - Get available boats and crews for event
   - Run SelectionService::select()
   - Returns: selected_boats, selected_crews, waitlisted_boats, waitlisted_crews

   b. **Consolidation Phase**
   - Form flotilla structure
   - Initial crew distribution (even distribution before optimization)

   c. **Assignment Phase** (next event only)
   - Run AssignmentService::assign()
   - Greedy swap optimization
   - Minimize 6 rule violations: ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT

   d. **Status Update Phase**
   - Selected crews → GUARANTEED status

   e. **Persistence Phase**
   - Save flotilla to season_repository

3. **Final Persistence**
   - Save all boats (availability, history updated)
   - Save all crews (availability, history updated)

---

## Integration Points

### With Domain Layer

Use cases call domain services:
- `SelectionService::select()` - Ranking and capacity matching
- `AssignmentService::assign()` - Crew-to-boat optimization

Use cases work with domain entities:
- Boat, Crew (via repositories)
- EventId, BoatKey, CrewKey (value objects)
- AvailabilityStatus, SkillLevel (enums)

### With Infrastructure Layer

Use cases depend on repository interfaces:
- findByKey(), findAll(), findAvailableForEvent()
- save(), getFlotilla(), saveFlotilla()
- getConfig(), updateConfig()

Use cases depend on service interfaces:
- EmailServiceInterface::sendAssignmentNotification()
- CalendarServiceInterface::generateEventCalendar()
- TimeServiceInterface::getCurrentDateTime()

### With Presentation Layer (Coming in Phase 5)

Controllers will:
1. Extract data from HTTP requests
2. Build request DTOs
3. Call use case execute() methods
4. Convert response DTOs to JSON
5. Handle exceptions (ValidationException, NotFoundException, etc.)

---

## Testing Strategy

### Unit Testing (Recommended)

Each use case should be tested with mocked repositories:

```php
public function testRegisterBoatCreatesNewBoat(): void
{
    $mockRepo = $this->createMock(BoatRepositoryInterface::class);
    $mockRepo->expects($this->once())->method('save');

    $useCase = new RegisterBoatUseCase($mockRepo);
    $request = new RegisterBoatRequest(...);

    $response = $useCase->execute($request);

    $this->assertInstanceOf(BoatResponse::class, $response);
}
```

### Integration Testing (Recommended)

Test use cases with real SQLite repositories:

```php
public function testProcessSeasonUpdateGeneratesFlotillas(): void
{
    // Use real SQLite in-memory database
    $boatRepo = new BoatRepository();
    $crewRepo = new CrewRepository();
    // ... etc

    $useCase = new ProcessSeasonUpdateUseCase(...);
    $result = $useCase->execute();

    $this->assertTrue($result['success']);
    $this->assertGreaterThan(0, $result['flotillas_generated']);
}
```

---

## Validation Rules Summary

### RegisterBoatRequest
- display_name: required, non-empty
- owner_first_name: required, non-empty
- owner_last_name: required, non-empty
- owner_email: required, valid email format
- min_berths: positive integer
- max_berths: positive integer, >= min_berths

### RegisterCrewRequest
- display_name: required, non-empty
- first_name: required, non-empty
- last_name: required, non-empty
- email: required, valid email format
- skill: valid SkillLevel enum (0, 1, 2)

### UpdateAvailabilityRequest
- availabilities: non-empty array

### UpdateConfigRequest
- source: enum ('simulated' or 'production')
- simulated_date: format YYYY-MM-DD
- year: range 2020-2100
- time fields: format HH:MM:SS

---

## Next Steps: Phase 5 (Presentation Layer)

Phase 5 will implement the REST API:

1. **Configuration**
   - config/routes.php - Route definitions
   - config/container.php - Dependency injection container
   - public/index.php - Application entry point

2. **Controllers**
   - EventController (GET /api/events, GET /api/events/:id)
   - AvailabilityController (PATCH /api/users/me/availability)
   - AssignmentController (GET /api/assignments)
   - AdminController (GET /api/admin/matching/:eventId)

3. **Middleware**
   - NameAuthMiddleware (extract first_name/last_name from headers)
   - ErrorHandlerMiddleware (catch exceptions, format JSON errors)
   - JsonResponseMiddleware (set content-type headers)

4. **Request/Response Wrappers**
   - JsonResponse class
   - ErrorResponse class
   - Request validation helpers

5. **Testing**
   - API endpoint tests
   - Postman collection for manual testing

---

## Files Modified

None - Phase 4 was purely additive.

---

## Verification Checklist

- [x] All exception classes created (5)
- [x] All request DTOs created with validation (4)
- [x] All response DTOs created with serialization (5)
- [x] Boat use cases implemented (2)
- [x] Crew use cases implemented (3)
- [x] Event use cases implemented (2)
- [x] Season use cases implemented (3)
- [x] Admin use cases implemented (2)
- [x] **CRITICAL:** ProcessSeasonUpdateUseCase orchestrates full pipeline
- [x] All use cases depend on interfaces (ports), not implementations
- [x] All DTOs have validation methods
- [x] All response DTOs have toArray() methods
- [x] No direct coupling to Infrastructure layer

---

## Phase 4 Complete ✅

The Application Layer is now fully implemented. All use cases orchestrate domain logic, and all DTOs handle data transfer with proper validation. The critical ProcessSeasonUpdateUseCase successfully replaces the legacy season_update.php orchestration script.

**Ready to proceed with Phase 5: Presentation Layer (REST API)**
