# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

JAWS is a PHP-based web application for managing the Social Day Cruising program at Nepean Sailing Club. It handles boat fleet management, crew registration, and intelligent assignment of crew members to boats for seasonal sailing events. The system optimizes crew-to-boat matching based on multiple constraints including skill levels, availability, preferences, and historical participation.

## Development Commands

### Install Dependencies
```bash
composer install
```

### Deploy to AWS Lightsail
Upload files via SFTP:
```bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15
cd /./var/www/html
put path/to/filename
bye
```

Then set permissions:
```bash
chgrp www-data path/to/filename
chmod 770 path/to/filename
```

### Download Data from Production
```bash
ssh-add LightsailDefaultKey-ca-central-1.pem
sftp bitnami@16.52.222.15
cd var/www/html/Libraries/Fleet/data
get fleet_data.csv
cd /../../Squad/data
get squad_data.csv
bye
```

## Architecture Overview

### Core Processing Flow

The application follows a layered architecture with a central orchestration script:

1. **Web Layer** - HTML forms and PHP handlers for user input
2. **Orchestration Layer** - `season_update.php` (main entry point)
3. **Business Logic** - Libraries with specialized responsibilities
4. **Data Layer** - CSV file persistence

### The Season Update Pipeline

`season_update.php` is invoked after every user input (registration or availability update). It executes this pipeline for each future event:

1. **Selection** - Ranks and sorts boats/crews by multi-dimensional criteria
2. **Event Consolidation** - Forms boats and crews into a structured "flotilla"
3. **Assignment Optimization** - For the next event only, swaps crew between boats to minimize 6 rule violations
4. **Persistence** - Saves updated fleet/squad data to CSV files
5. **Output Generation** - Renders flotilla tables to HTML

### Key Libraries and Responsibilities

**Domain Models:**
- `Boat` - Represents boats with berth capacity, assistance requirements, availability, ranking
- `Crew` - Represents crew members with skill level, preferences, availability, whitelist
- `Event` - Consolidates boats and crews into flotillas for specific events
- `Season` - Manages season configuration, event dates, time logic, flotilla storage

**Core Processing:**
- `Selection` - Implements ranking system and capacity matching (handles too few/many/perfect crew scenarios)
- `Assignment` - Optimizes crew placement using iterative swapping to minimize 6 rule violations:
  - ASSIST: Boats requiring assistance get appropriate crew
  - WHITELIST: Crew assigned to boats they've whitelisted
  - HIGH_SKILL/LOW_SKILL: Balance skill distribution
  - PARTNER: Keep requested partnerships together
  - REPEAT: Minimize crew repeating same boat
- `Fleet` - Collection management and CSV I/O for boats
- `Squad` - Collection management and CSV I/O for crew

**Supporting:**
- `Rank` - Constants defining 8 ranking dimensions used for prioritization
- `Calendar` - Generates iCalendar (.ics) files with event details
- `Mail` - Sends SMTP notifications via AWS SES
- `Html` - Renders flotillas to HTML tables
- `Name` - Sanitizes input (escapes commas, newlines)

### Multi-Dimensional Ranking System

The system uses rank tensors for prioritization:

- **Boats**: `[flexibility, absence]` (2D)
- **Crews**: `[commitment, flexibility, membership, absence]` (4D)

Rankings are compared lexicographically during bubble sort. Lower rank = higher priority.

**Rank Components:**
- `flexibility` - Whether owner is also registered as crew (boats) or owns a boat (crew)
- `absence` - Count of past no-shows (updated dynamically)
- `commitment` - Crew's availability for the next scheduled event
- `membership` - Valid NSC membership number status

Ties are broken using deterministic shuffling seeded by event_id, ensuring reproducible results.

### Assignment Optimization

For the next event only, `Assignment::assign()` performs constraint-based optimization:

1. Calculate "Loss" (violations) and "Grad" (mitigation capacity) for each crew on each rule
2. Iterate through 6 rules in priority order
3. For each rule, identify highest-loss crew and best-grad swap candidate
4. Swap if it reduces total violations and doesn't create other conflicts
5. Mark swapped crew as "locked" for that rule to prevent thrashing

This greedy approach balances optimality with computational efficiency.

## Data Persistence

### CSV Files
- `/Libraries/Fleet/data/fleet_data.csv` - All boat data
- `/Libraries/Squad/data/squad_data.csv` - All crew data

### Configuration
- `/Libraries/Season/data/config.json` - Season settings:
  ```json
  {
    "config": {
      "source": "simulated|production",  // Time source
      "year": "2026",
      "month/day": "Jan/01",
      "start_time": "12 45 00",          // Event start time
      "finish_time": "17 00 00",         // Event end time
      "blackout_from": "10 00 00",       // Registration blackout start
      "blackout_to": "18 00 00",         // Registration blackout end
      "event_ids": ["Fri May 29", ...]   // List of event dates
    }
  }
  ```

### Generated Output
- `/Libraries/Html/data/page.html` - Flotilla assignment tables
- `/Libraries/Calendar/data/*.ics` - iCalendar files for program and participants

## Important Architectural Concepts

### Flex Concept
Boat owners can also register as crew, and crew can own boats. This "flex" status affects ranking calculations and prevents double-counting in capacity matching.

### Deterministic Behavior
The system uses seeded randomization (by event_id) to ensure the same inputs always produce identical assignments. This is critical for reproducibility and user trust.

### Capacity Matching (3 Cases)
`Selection::cut()` handles three scenarios:
1. **Too few crews** - Leave boats partially crewed, rest go to waitlist
2. **Too many crews** - Fill all boats, excess crews go to waitlist
3. **Perfect fit** - All boats perfectly crewed

### History Tracking
Boats and crews maintain participation history arrays. The `absence` rank dimension dynamically updates based on past no-shows, deprioritizing unreliable participants.

### Availability States
Crew availability has 4 states defined in `Rank.php`:
- `UNAVAILABLE` (0) - Cannot participate
- `AVAILABLE` (1) - Can participate
- `GUARANTEED` (2) - Selected for event
- `WITHDRAWN` (3) - Explicitly withdrawn

## Entry Points and User Workflows

### Public Flows
- `/index.html` → `/temp.php` - Program display page
- `/account_access.php` - Checks blackout window, routes to account or locked page
- `/account.html` - Choose boat or crew registration
- `/account_boat_data_form.php` - Boat registration form → `account_boat_data_update.php`
- `/account_crew_data_form.php` - Crew registration form → `account_crew_data_update.php`
- `/account_boat_availability_form.php` - Per-event berth selection → `account_boat_availability_update.php`
- `/account_crew_availability_form.php` - Per-event availability → `account_crew_availability_update.php`

All data update handlers terminate by calling `season_update.php`.

### Admin Flow
- `/admin.html` → `/admin_update.php` - Update season configuration (date/time source)

### Blackout Logic
During event days (10:00-18:00 by default), registration is blocked to prevent mid-event changes.

## Testing

Test cases are documented in `/Tests/Test cases.numbers` (Apple Numbers spreadsheet). No automated test framework is currently in place.

## Dependencies

**Composer Packages:**
- `phpmailer/phpmailer` ^7.0 - Email notifications via AWS SES
- `eluceo/ical` ^2.13 - iCalendar file generation

**Environment Variables (for production):**
- `SES_REGION` - AWS SES region
- `SES_SMTP_USERNAME` - SMTP credentials
- `SES_SMTP_PASSWORD` - SMTP credentials

## Development Notes

### Adding New Ranking Criteria
1. Add constant to `/Libraries/Config/src/Rank.php`
2. Update `Boat` or `Crew` rank array size
3. Implement calculation logic in respective class
4. Update `Selection::bubble()` if comparison logic changes

### Adding New Assignment Rules
1. Add enum value to `Assignment::Rule`
2. Implement `crew_loss()` logic for the new rule
3. Implement `crew_grad()` logic for the new rule
4. Add rule to priority order in `Assignment::assign()`

### Modifying Event Logic
The `Season` class provides time-aware methods:
- `get_past_events()` - Events before current time
- `get_future_events()` - Events after current time
- `get_next_event()` - The immediate next event (receives optimization)
- `get_last_event()` - The most recent past event

Use these rather than manually filtering event arrays.

### CSV Data Format
Both CSV files use commas as delimiters with escape logic in `Name::safe()` and `Name::display()`. Never manually edit CSV files without proper escaping.

## Common Patterns

### Loading and Saving Data
```php
$_fleet = new fleet\Fleet();  // Auto-loads fleet_data.csv
$_squad = new squad\Squad();  // Auto-loads squad_data.csv

// Make changes...

$_fleet->save();  // Persists to fleet_data.csv
$_squad->save();  // Persists to squad_data.csv
```

### Accessing Entities
```php
$boat = $_fleet->get_boat($key);
$crew = $_squad->get_crew($key);

$available_boats = $_fleet->get_available_boats($event_id);
$available_crews = $_squad->get_available_crews($event_id);
```

### Working with Events
```php
$_season->load_season_data();  // Load config.json
$future_events = $_season->get_future_events();

foreach ($future_events as $event_id) {
    $flotilla = $_season->get_flotilla($event_id);
    // Process flotilla...
}
```
