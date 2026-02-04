# Legacy JAWS Application

This directory contains the original JAWS application files before the clean architecture refactoring.

**IMPORTANT:** These files are preserved for reference and comparison during the migration. They should NOT be modified and will be removed after the migration is complete and verified.

## Contents

### Libraries/
Original business logic libraries organized by domain:
- **Assignment/** - Crew-to-boat assignment optimization algorithm
- **Boat/** - Boat entity and logic
- **Calendar/** - iCalendar (.ics) file generation
- **Config/** - Configuration constants (Rank dimensions)
- **Crew/** - Crew entity and logic
- **Csv/** - CSV file handling utilities
- **Event/** - Event entity and flotilla management
- **Fleet/** - Fleet collection and CSV persistence
- **Html/** - HTML table rendering
- **Mail/** - SMTP email via AWS SES
- **Name/** - Input sanitization utilities
- **Season/** - Season configuration and time logic
- **Selection/** - Boat/crew selection and ranking algorithm
- **Squad/** - Squad collection and CSV persistence

### PHP Entry Points
- **account_access.php** - Checks blackout window, routes to account or locked page
- **account_boat_data_form.php** - Boat registration form
- **account_boat_data_update.php** - Boat registration handler
- **account_boat_availability_form.php** - Boat availability form
- **account_boat_availability_update.php** - Boat availability handler
- **account_boat_exists.php** - Check if boat exists
- **account_crew_data_form.php** - Crew registration form
- **account_crew_data_update.php** - Crew registration handler
- **account_crew_availability_form.php** - Crew availability form
- **account_crew_availability_update.php** - Crew availability handler
- **account_crew_exists.php** - Check if crew exists
- **admin_update.php** - Update season configuration
- **season_update.php** - Main orchestration pipeline (CRITICAL)
- **temp.php** - Program display page

## Critical Files for Migration

### Must Preserve Algorithms

**Libraries/Selection/src/Selection.php**
- Contains proven ranking and sorting algorithms
- Multi-dimensional rank comparison (lexicographic)
- Deterministic shuffle using CRC32 seeding
- Capacity matching (3 cases: too few, too many, perfect)
- **ACTION:** Copy character-for-character to `src/Domain/Service/SelectionService.php`

**Libraries/Assignment/src/Assignment.php**
- Contains proven crew-to-boat optimization algorithm
- 6 rules: ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT
- Loss/gradient calculations and greedy swapping
- **ACTION:** Copy character-for-character to `src/Domain/Service/AssignmentService.php`

**season_update.php**
- Main orchestration pipeline
- Event processing flow
- **ACTION:** Logic migrated to `src/Application/UseCase/Season/ProcessSeasonUpdateUseCase.php`

### Data Persistence Patterns

**Libraries/Fleet/src/Fleet.php** and **Libraries/Squad/src/Squad.php**
- CSV data format (semicolon-delimited arrays)
- Array indices mapping to events
- Key generation patterns
- **ACTION:** Patterns documented, migrated to repository implementations

## Migration Strategy

1. **Phase 1:** Set up new architecture (COMPLETED)
2. **Phase 2:** Migrate domain entities and services
   - Copy Selection and Assignment algorithms exactly
   - Write unit tests to verify identical behavior
   - Compare outputs with legacy system
3. **Phase 3:** Implement new persistence layer (SQLite)
4. **Phase 4:** Implement use cases
5. **Phase 5:** Implement REST API
6. **Phase 6:** Run legacy and new systems in parallel
7. **Phase 7:** Cut over to new system, archive this directory

## Testing Against Legacy

During parallel operation, use these files to verify new system produces identical results:
- Compare flotilla assignments
- Compare ranking calculations
- Compare assignment optimization
- Compare capacity matching

## Data Files

The original CSV data files remain in:
- `Libraries/Fleet/data/fleet_data.csv`
- `Libraries/Squad/data/squad_data.csv`
- `Libraries/Season/data/config.json`

These will be migrated to SQLite during Phase 3.

## DO NOT MODIFY

These files are read-only references. Any changes should be made in the new clean architecture codebase under `/src`.
