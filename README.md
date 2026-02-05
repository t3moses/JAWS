# JAWS - Social Day Cruising Program Management System

JAWS is a front-end website and REST API for managing the Social Day Cruising program at Nepean Sailing Club. It handles boat fleet management, crew registration, and intelligent assignment of crew members to boats for seasonal sailing events using sophisticated optimization algorithms.

## Quick Start

Get up and running in 5 steps:

```bash
# 1. Install dependencies
composer install

# 2. Initialize database
vendor/bin/phinx migrate

# 3. Configure environment
cp .env.example .env
# Edit .env and set JWT_SECRET (minimum 32 characters)

# 4. Start development server
php -S localhost:8000 -t public

# 5. Test the API
curl http://localhost:8000/api/events
```

## Key Features

**Intelligent Crew Assignment**
- Multi-dimensional ranking system for boats and crews
- Constraint-based optimization algorithm
- Deterministic behavior ensures reproducible results

**Flexible Registration**
- Boat owners register boats with capacity and requirements
- Crew members register with skill level and preferences
- "Flex" members can be both boat owners and crew

**Automated Matching**
- Capacity matching handles three scenarios (too few crews, too many crews, perfect fit)
- Assignment optimization minimizes six rule violations (ASSIST, WHITELIST, HIGH_SKILL, LOW_SKILL, PARTNER, REPEAT)
- Real-time updates after every registration change

**Smart Features**
- Blackout windows prevent changes during event hours
- History tracking adjusts rankings based on past participation
- Partner preferences keep requested partnerships together
- Skill distribution balances experience across boats

## Technology Stack

- **Language:** PHP 8.1+
- **Database:** SQLite (with migration path to PostgreSQL)
- **Architecture:** Clean Architecture (Hexagonal/Ports and Adapters)
- **API Style:** REST/JSON with JWT authentication
- **Testing:** PHPUnit for unit and integration tests
- **Migrations:** Phinx for database schema management

## Documentation

### Getting Started

📘 **[Setup Guide](docs/SETUP.md)** - Installation, configuration, and first run
- Prerequisites and dependencies
- Local development setup
- Environment configuration
- Database initialization
- Verification steps

📱 **[Frontend Setup](docs/FRONTEND_SETUP.md)** - Integrating the frontend application
- Directory structure
- API integration
- Authentication flow
- Client-side routing

### For Developers

🛠️ **[Developer Guide](docs/DEVELOPER_GUIDE.md)** - Architecture, development workflow, and testing
- Clean Architecture overview
- Project structure and layer responsibilities
- Daily development workflow
- Testing guide (unit, integration, API)
- Database schema changes with Phinx
- Common patterns and best practices
- Critical algorithms (Selection, Assignment, Ranking)

📡 **[API Reference](docs/API.md)** - Complete API endpoint documentation
- Authentication (JWT tokens)
- Public endpoints (events)
- Authenticated endpoints (availability, assignments, profile)
- Admin endpoints (notifications, configuration)
- Error handling and status codes
- Request/response examples

🤝 **[Contributing Guide](docs/CONTRIBUTING.md)** - Code style, Git workflow, PR process
- Branch naming conventions
- Commit message format (Conventional Commits)
- Code style standards (PSR-12)
- Testing requirements
- Documentation requirements
- Pull request process

### For Operators

🚀 **[Deployment Guide](docs/DEPLOYMENT.md)** - Production deployment and monitoring
- Pre-deployment checklist
- AWS Lightsail deployment steps
- Environment configuration for production
- Database management (migrations, backups, restore)
- Monitoring and health checks
- Rollback procedures

💾 **[Database Management](database/README.md)** - Migrations, backups, and queries
- Phinx migration workflow
- Creating and applying migrations
- Backup and restore procedures
- Querying the database

### Additional Resources

☁️ **[LocalStack Setup](LocalStack/LOCALSTACK_SETUP.md)** - Testing AWS SES locally
- Docker setup for LocalStack
- Email verification
- Testing notifications without AWS costs

🤖 **[CLAUDE.md](CLAUDE.md)** - AI assistant project guide
- Complete technical specifications
- Project structure and patterns
- Development commands and workflows
- Critical implementation details

## Architecture

JAWS follows **Clean Architecture** with strict dependency rules. Dependencies point inward, from outer to inner layers:

```
Presentation → Infrastructure → Application → Domain
  (HTTP)      (Database/AWS)   (Use Cases)   (Business Logic)
```

### The Four Layers

**Domain Layer** (`src/Domain/`)
- Core business logic and rules
- **NO** external dependencies (pure PHP)
- Contains: Entities, Value Objects, Enums, Domain Services, Collections

**Application Layer** (`src/Application/`)
- Use cases and application orchestration
- Depends on: Domain layer only
- Contains: Use Cases, Ports (interfaces), DTOs, Exceptions

**Infrastructure Layer** (`src/Infrastructure/`)
- External service adapters
- Depends on: Application + Domain layers
- Contains: Repositories (SQLite), Email Service (AWS SES), Calendar Service, Time Service

**Presentation Layer** (`src/Presentation/`)
- HTTP/REST API
- Depends on: Application layer
- Contains: Controllers, Middleware, Router, Response formatters

### Why Clean Architecture?

- **Testability**: Unit test business logic without database or HTTP
- **Flexibility**: Swap SQLite for PostgreSQL without changing business logic
- **Maintainability**: Clear boundaries and single responsibilities
- **Algorithm Preservation**: Critical selection/assignment algorithms isolated and preserved

📖 **Learn more:** [Developer Guide - Clean Architecture](docs/DEVELOPER_GUIDE.md#clean-architecture-overview)

## The Assignment Pipeline

After every user input (registration or availability update), JAWS runs this pipeline:

1. **Selection Phase**: Rank and select available boats/crews using multi-dimensional criteria
2. **Consolidation Phase**: Form selected entities into a structured "flotilla"
3. **Assignment Optimization**: For the next event, perform constraint-based crew swapping
4. **Persistence**: Save updated data and flotilla assignments
5. **Output Generation**: Render tables and generate calendar files

**Key Insight:** The system uses deterministic shuffling (seeded by `crc32($eventId)`) to ensure the same inputs always produce identical assignments.

## Project Status

✅ **Phase 7 Complete** - Clean Architecture refactoring finished
- Legacy codebase preserved in `legacy/` folder
- Selection and Assignment algorithms preserved character-for-character
- Comprehensive test suite (unit + integration)
- REST API with JWT authentication
- Database migrations with Phinx

🔄 **Current**: Production deployment on AWS Lightsail

📋 **Planned Enhancements**:
- Phase 8: PostgreSQL migration for production scalability
- Phase 9: Enhanced authentication system
- Phase 10: Modern SPA frontend (React/Vue)
- Phase 11: Expanded testing and CI/CD

## Development Commands

### Install Dependencies
```bash
composer install
```

### Database Operations
```bash
# Run migrations
vendor/bin/phinx migrate

# Create new migration
vendor/bin/phinx create MyMigrationName

# Rollback last migration
vendor/bin/phinx rollback

# Check migration status
vendor/bin/phinx status

# Seed test data
vendor/bin/phinx seed:run
```

### Testing
```bash
# All tests
./vendor/bin/phpunit

# Unit tests only
./vendor/bin/phpunit tests/Unit

# Integration tests only
./vendor/bin/phpunit tests/Integration

# Specific test file
./vendor/bin/phpunit tests/Unit/Domain/SelectionServiceTest.php

# With coverage report
./vendor/bin/phpunit --coverage-html coverage
```

### Development Server
```bash
# Start on port 8000
php -S localhost:8000 -t public

# Or use a different port
php -S localhost:3000 -t public
```

### API Testing
```bash
# Run simple API test script
php Tests/Integration/api_test.php

# Or use Postman collection
# Import Tests/JAWS_API.postman_collection.json
```

## Quick Links

**For New Users:**
→ Start with [Setup Guide](docs/SETUP.md) to get JAWS running locally

**For Developers:**
→ Read [Developer Guide](docs/DEVELOPER_GUIDE.md) to understand the architecture

**For API Consumers:**
→ Check [API Reference](docs/API.md) for complete endpoint documentation

**For Operators:**
→ Follow [Deployment Guide](docs/DEPLOYMENT.md) to deploy to production

## Environment Variables

Key environment variables (set in `.env` file):

```bash
# Database
DB_PATH=database/jaws.db

# JWT Authentication (REQUIRED)
JWT_SECRET=your-secret-key-minimum-32-characters-long
JWT_EXPIRATION_MINUTES=60

# AWS SES (Email Service)
SES_REGION=ca-central-1
SES_SMTP_USERNAME=your_smtp_username
SES_SMTP_PASSWORD=your_smtp_password
EMAIL_FROM=noreply@nsc-sdc.ca

# Application
APP_DEBUG=true
APP_ENV=development
APP_TIMEZONE=America/Toronto

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

📖 **Complete configuration:** [Setup Guide - Environment Configuration](docs/SETUP.md#environment-configuration)

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Follow our [code style](docs/CONTRIBUTING.md#code-style) (PSR-12)
4. Write tests for your changes
5. Ensure all tests pass: `./vendor/bin/phpunit`
6. Use [conventional commits](docs/CONTRIBUTING.md#commit-message-format): `feat: add feature description`
7. Create a Pull Request

📖 **Read the full guide:** [Contributing Guide](docs/CONTRIBUTING.md)

## Support

**Documentation:**
- 📁 `/docs` folder contains all user documentation
- 🤖 `CLAUDE.md` contains technical specifications for AI assistants

**Questions:**
- Open an issue on GitHub for bugs or feature requests
- Check documentation for setup and development questions

## License

Proprietary - Nepean Sailing Club

## Credits

Developed for Nepean Sailing Club's Social Day Cruising Program.

Refactored to Clean Architecture in 2026, preserving all original business logic while improving maintainability, testability, and flexibility.

---

**Project History:** The original codebase has been preserved in the `legacy/` folder. The refactoring to Clean Architecture maintained identical behavior for all critical algorithms (Selection, Assignment, Ranking) while modernizing the codebase structure. See `docs/archive/REFACTOR_HISTORY.md` for details.
