# Phase 5: Presentation Layer - COMPLETE

**Status:** ✅ COMPLETE
**Date Completed:** 2026-01-25

---

## Overview

Phase 5 successfully implemented the Presentation Layer (REST API) of the clean architecture refactoring. This layer includes HTTP controllers, middleware, routing, and the application entry point. The API is now fully functional and ready for testing.

---

## Files Created (14 total)

### Configuration (3 files)

Located in: `config/`

1. **config.php**
   - Central application configuration
   - Database path, email settings, timezone, CORS
   - Environment variable support

2. **container.php**
   - Dependency injection container
   - Configures all service dependencies following Dependency Inversion Principle
   - Maps interfaces to concrete implementations
   - Wires up all use cases with their dependencies

3. **routes.php**
   - Route definitions for all API endpoints
   - Maps HTTP methods + paths → controller actions
   - Specifies authentication requirements per route

### Controllers (4 files)

Located in: `src/Presentation/Controller/`

1. **EventController.php**
   - Handles public event endpoints
   - Methods:
     - `getAll()` - GET /api/events
     - `getOne($params)` - GET /api/events/{id}

2. **AvailabilityController.php**
   - Handles registration and availability updates
   - Methods:
     - `registerBoat($body)` - POST /api/boats/register
     - `updateBoatAvailability($body, $auth)` - PATCH /api/boats/availability
     - `registerCrew($body)` - POST /api/crews/register
     - `updateCrewAvailability($body, $auth)` - PATCH /api/users/me/availability
   - **CRITICAL:** Triggers ProcessSeasonUpdateUseCase after every update

3. **AssignmentController.php**
   - Handles assignment lookups
   - Methods:
     - `getUserAssignments($auth)` - GET /api/assignments

4. **AdminController.php**
   - Handles administrative endpoints
   - Methods:
     - `getMatchingData($params)` - GET /api/admin/matching/{eventId}
     - `sendNotifications($params, $body)` - POST /api/admin/notifications/{eventId}
     - `updateConfig($body)` - PATCH /api/admin/config

### Middleware (3 files)

Located in: `src/Presentation/Middleware/`

1. **NameAuthMiddleware.php**
   - Extracts `first_name` and `last_name` from HTTP headers
   - Headers: `X-User-FirstName`, `X-User-LastName`
   - Returns auth data or null if authentication fails
   - Creates 401 response for failed authentication

2. **ErrorHandlerMiddleware.php**
   - Catches all exceptions and formats as JSON responses
   - Maps exception types to HTTP status codes:
     - ValidationException → 400 Bad Request
     - NotFoundException → 404 Not Found
     - BlackoutWindowException → 403 Forbidden
     - Generic Exception → 500 Internal Server Error
   - Logs unexpected errors
   - Respects debug mode for error details

3. **CorsMiddleware.php**
   - Handles Cross-Origin Resource Sharing headers
   - Allows frontend applications to access API
   - Supports preflight OPTIONS requests
   - Configurable allowed origins, methods, headers

### Routing (1 file)

Located in: `src/Presentation/`

1. **Router.php**
   - Simple pattern-matching router
   - Converts route paths to regex patterns
   - Extracts route parameters from URL
   - Methods:
     - `match($method, $path)` - Find matching route
     - `convertRouteToRegex($path)` - Convert {param} to regex
     - `notFound()` - 404 response
     - `methodNotAllowed()` - 405 response

### Response (1 file)

Located in: `src/Presentation/Response/`

1. **JsonResponse.php**
   - Standardizes API responses
   - Static factory methods:
     - `success($data, $statusCode)` - Success response
     - `error($message, $statusCode, $details)` - Error response
     - `notFound($message)` - 404 response
     - `serverError($message)` - 500 response
   - Sets JSON content-type header

### Entry Point (1 file)

Located in: `public/`

1. **index.php**
   - Main application entry point
   - Bootstrap flow:
     1. Load autoloader
     2. Load configuration
     3. Set timezone and error reporting
     4. Load container and routes
     5. Initialize middleware
     6. Apply CORS
     7. Match route
     8. Check authentication
     9. Parse request body
     10. Resolve controller from container
     11. Call controller action
     12. Send JSON response
     13. Handle exceptions

### Web Server Configuration (1 file)

Located in: `public/`

1. **.htaccess**
   - Apache rewrite rules
   - Routes all requests to index.php
   - Prevents directory listing
   - Sets UTF-8 charset

### Testing (2 files)

Located in: `tests/`

1. **api_test.php**
   - Simple PHP-based API test script
   - Tests:
     - GET /api/events (public)
     - GET /api/events/{id} (public)
     - POST /api/crews/register (authenticated)
     - PATCH /api/users/me/availability (authenticated)
     - GET /api/assignments (authenticated)
     - Authentication failure (401)
     - Non-existent route (404)
   - Run with: `php tests/api_test.php`

2. **JAWS_API.postman_collection.json**
   - Postman collection for manual testing
   - Organized into folders:
     - Public Endpoints
     - Registration
     - Availability
     - Assignments
     - Admin
   - Variables: baseUrl, firstName, lastName

---

## API Endpoints

### Public Endpoints

**GET /api/events**
- Returns all events for the season
- No authentication required
- Response: `{success: true, data: {events: [...]}}`

**GET /api/events/{id}**
- Returns specific event with flotilla assignments
- No authentication required
- Response: `{success: true, data: {event: {...}, flotilla: {...}}}`

### Authenticated Endpoints (Name-Based)

**POST /api/boats/register**
- Register a boat (create or update)
- Headers: `X-User-FirstName`, `X-User-LastName`
- Body: boat registration data
- Triggers season update
- Response: `{success: true, data: {boat: {...}, message: "..."}}`

**PATCH /api/boats/availability**
- Update boat availability (berths per event)
- Headers: `X-User-FirstName`, `X-User-LastName`
- Body: `{boat_name: "...", availabilities: {"eventId": berths, ...}}`
- Triggers season update
- Response: `{success: true, data: {boat: {...}, message: "..."}}`

**POST /api/crews/register**
- Register a crew member (create or update)
- Headers: `X-User-FirstName`, `X-User-LastName`
- Body: crew registration data
- Triggers season update
- Response: `{success: true, data: {crew: {...}, message: "..."}}`

**PATCH /api/users/me/availability**
- Update crew availability (status per event)
- Headers: `X-User-FirstName`, `X-User-LastName`
- Body: `{availabilities: {"eventId": status, ...}}`
- Triggers season update
- Response: `{success: true, data: {crew: {...}, message: "..."}}`

**GET /api/assignments**
- Returns user's assignments across all events
- Headers: `X-User-FirstName`, `X-User-LastName`
- Response: `{success: true, data: {assignments: [...]}}`

### Admin Endpoints

**GET /api/admin/matching/{eventId}**
- Returns capacity analysis for event
- Headers: `X-User-FirstName`, `X-User-LastName`
- Response: `{success: true, data: {available_boats: [...], available_crews: [...], capacity: {...}}}`

**POST /api/admin/notifications/{eventId}**
- Sends email notifications and calendar invites
- Headers: `X-User-FirstName`, `X-User-LastName`
- Body: `{include_calendar: true}`
- Response: `{success: true, data: {emails_sent: N, message: "..."}}`

**PATCH /api/admin/config**
- Updates season configuration
- Headers: `X-User-FirstName`, `X-User-LastName`
- Body: `{source: "simulated|production", simulated_date: "...", ...}`
- Response: `{success: true, data: {message: "..."}}`

---

## Dependency Injection Container

The container wires up all dependencies following the Dependency Inversion Principle:

### Infrastructure Layer (Concrete Implementations)

**Repositories:**
- BoatRepositoryInterface → BoatRepository
- CrewRepositoryInterface → CrewRepository
- EventRepositoryInterface → EventRepository
- SeasonRepositoryInterface → SeasonRepository

**Services:**
- EmailServiceInterface → AwsSesEmailService
- CalendarServiceInterface → ICalendarService
- TimeServiceInterface → SystemTimeService

### Domain Layer (Services)

- RankingService (depends on TimeServiceInterface)
- FlexService (no dependencies)
- SelectionService (depends on RankingService, FlexService)
- AssignmentService (no dependencies)

### Application Layer (Use Cases)

All use cases are wired with their required repository and service dependencies.

### Presentation Layer (Controllers)

All controllers are wired with their required use case dependencies.

---

## Request/Response Flow

1. **HTTP Request** arrives at `public/index.php`

2. **Bootstrap**
   - Load autoloader, config, container, routes
   - Initialize middleware

3. **CORS Middleware**
   - Apply CORS headers
   - Handle preflight OPTIONS requests

4. **Routing**
   - Extract method and path from request
   - Match route using Router::match()
   - Return 404 if no match

5. **Authentication** (if required)
   - NameAuthMiddleware extracts headers
   - Return 401 if authentication fails

6. **Request Parsing**
   - Parse JSON body for POST/PUT/PATCH

7. **Controller Resolution**
   - Resolve controller from container
   - Inject dependencies automatically

8. **Controller Action**
   - Build request DTO
   - Call use case execute()
   - Use case orchestrates domain logic
   - Use case returns response DTO

9. **Response**
   - Controller returns JsonResponse
   - JsonResponse::send() outputs JSON

10. **Error Handling**
    - ErrorHandlerMiddleware catches exceptions
    - Maps to appropriate HTTP status code
    - Returns formatted JSON error

---

## Key Features

### 1. Name-Based Authentication

Simple authentication for initial implementation:
- Headers: `X-User-FirstName`, `X-User-LastName`
- No passwords or JWT in this phase
- Suitable for trusted internal use
- **Future:** Replace with JWT/password authentication

### 2. Automatic Season Update

After every registration or availability update:
- AvailabilityController triggers `ProcessSeasonUpdateUseCase`
- Orchestrates Selection → Assignment → Persistence
- Ensures flotillas are always up-to-date
- **Critical:** Preserves legacy `season_update.php` behavior

### 3. Consistent Error Handling

All exceptions mapped to appropriate HTTP responses:
- ValidationException → 400 + field errors
- NotFoundException → 404
- BlackoutWindowException → 403
- Generic Exception → 500 (logged)

### 4. CORS Support

Enables frontend applications to access API:
- Configurable allowed origins
- Preflight request handling
- Credential support

### 5. Dependency Injection

All dependencies resolved via container:
- Controllers don't know about concrete implementations
- Easy to swap implementations (e.g., SQLite → PostgreSQL)
- Easy to mock dependencies for testing

---

## Testing

### Automated Testing

Run the PHP test script:
```bash
php tests/api_test.php
```

Tests cover:
- Public endpoints (events)
- Authentication (success and failure)
- Registration (boat, crew)
- Availability updates
- Assignment lookups
- Error handling (404, 401)

### Manual Testing

Import Postman collection:
1. Open Postman
2. Import `tests/JAWS_API.postman_collection.json`
3. Set variables: `baseUrl`, `firstName`, `lastName`
4. Execute requests

### Testing with PHP Built-in Server

Start local server:
```bash
cd public
php -S localhost:8000
```

Then run tests:
```bash
php tests/api_test.php
```

Or use Postman with `baseUrl = http://localhost:8000/api`

---

## Apache Configuration

### Requirements

- Apache with mod_rewrite enabled
- PHP 8.1+
- PDO SQLite extension

### .htaccess

The `.htaccess` file routes all requests to `index.php`:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Virtual Host (Recommended)

```apache
<VirtualHost *:80>
    ServerName jaws.local
    DocumentRoot "/path/to/JAWS/public"

    <Directory "/path/to/JAWS/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Environment Variables

Optional `.env` file (not included, create if needed):

```env
# Database
DB_PATH=/path/to/database/jaws.db

# AWS SES (Email)
SES_REGION=ca-central-1
SES_SMTP_USERNAME=your_username
SES_SMTP_PASSWORD=your_password
EMAIL_FROM=noreply@nepean-sailing.ca
EMAIL_FROM_NAME=JAWS - Nepean Sailing Club

# Application
APP_DEBUG=false
APP_TIMEZONE=America/Toronto
APP_URL=http://localhost

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080
```

---

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    // Response data here
  }
}
```

### Error Response

```json
{
  "success": false,
  "error": "Error message here",
  "details": {
    // Optional field-level errors (ValidationException)
  }
}
```

---

## Security Considerations

### Current Implementation

- **Name-based authentication:** Simple but not secure
- **No rate limiting:** Vulnerable to abuse
- **No CSRF protection:** Not needed for API (no cookies)
- **No input sanitization beyond validation:** Relies on PDO prepared statements

### Recommended Improvements (Future Phases)

1. **JWT Authentication**
   - Replace name-based headers with JWT tokens
   - Add password hashing (bcrypt)
   - Add refresh token support

2. **Rate Limiting**
   - Limit requests per IP/user
   - Prevent brute force attacks

3. **Admin Role**
   - Add role-based access control
   - Separate admin endpoints from user endpoints

4. **HTTPS**
   - Enforce HTTPS in production
   - Add HSTS headers

5. **Input Validation**
   - Sanitize HTML output (XSS prevention)
   - Validate all input lengths and formats

---

## Integration with Legacy System

### Migration Path

1. **Phase 6: Parallel Operation**
   - Keep legacy PHP forms operational
   - Both systems use SQLite database
   - Run comparison tests

2. **Phase 7: Cutover**
   - Redirect legacy entry points to API
   - Archive legacy code
   - Update documentation

### Legacy Compatibility

The API preserves legacy behavior:
- ProcessSeasonUpdateUseCase replicates season_update.php
- Selection and Assignment algorithms preserved exactly
- Flotilla structure unchanged (for HTML/Calendar generation)

---

## Next Steps: Phase 6 (Parallel Operation)

Phase 6 will run legacy and new systems side-by-side:

1. **Comparison Logging**
   - Log flotilla outputs from both systems
   - Compare results for discrepancies

2. **Bug Fixes**
   - Investigate any differences
   - Fix implementation bugs

3. **Performance Testing**
   - Monitor API response times
   - Optimize database queries if needed

4. **User Acceptance Testing**
   - Get feedback from users
   - Validate correctness of assignments

---

## Files Modified

None - Phase 5 was purely additive.

---

## Verification Checklist

- [x] Configuration files created (config.php, container.php, routes.php)
- [x] All controllers implemented (4)
- [x] All middleware implemented (3)
- [x] Router implemented with pattern matching
- [x] JsonResponse wrapper created
- [x] Entry point (public/index.php) created
- [x] Apache .htaccess configured
- [x] API test script created
- [x] Postman collection created
- [x] Dependency injection container wires all dependencies
- [x] All routes defined and mapped to controllers
- [x] Authentication middleware extracts name headers
- [x] Error middleware catches and formats exceptions
- [x] CORS middleware handles cross-origin requests
- [x] ProcessSeasonUpdateUseCase triggered after updates

---

## Phase 5 Complete ✅

The Presentation Layer (REST API) is now fully implemented. The API provides clean HTTP endpoints for all major workflows, with proper authentication, error handling, and CORS support. The system is ready for testing and parallel operation with the legacy system.

**Ready to proceed with Phase 6: Parallel Operation**
