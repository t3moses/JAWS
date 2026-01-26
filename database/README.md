# JAWS Database

This directory contains the SQLite database and migration scripts for the JAWS application.

## Database Initialization

To create and initialize the database, run:

```bash
php database/init_database.php
```

This will:
1. Create `database/jaws.db` (SQLite database file)
2. Apply the initial schema migration (`migrations/001_initial_schema.sql`)
3. Verify all tables, indexes, and triggers were created
4. Initialize the season_config table with default values

**Note:** If the database already exists, the script will prompt you to confirm before recreating it. A backup will be created automatically.

## Database Structure

The database contains the following tables:

### Core Entities
- **boats** - Boat information, owner details, berth capacity, ranking data
- **crews** - Crew member information, skills, preferences, ranking data
- **events** - Event schedule and metadata
- **season_config** - Season-wide configuration (singleton table)

### Relationships
- **boat_availability** - Berths offered per boat per event
- **crew_availability** - Crew availability status per event
- **boat_history** - Boat participation history
- **crew_history** - Crew assignment history (which boat)
- **crew_whitelist** - Crew preferences for specific boats

### Generated Data
- **flotillas** - Generated flotilla assignments (JSON)

## Migrations

Migration files are stored in `database/migrations/` and should be named with a numeric prefix:

- `001_initial_schema.sql` - Initial database schema
- `002_feature_name.sql` - Future migrations...

Each migration should be idempotent (use `IF NOT EXISTS` clauses).

## File Permissions (AWS Lightsail)

After uploading the database to production:

```bash
chgrp www-data /var/www/html/database/jaws.db
chmod 664 /var/www/html/database/jaws.db
chgrp www-data /var/www/html/database
chmod 775 /var/www/html/database
```

## Backup and Restore

### Backup
```bash
sqlite3 database/jaws.db .dump > database/backup_$(date +%Y%m%d_%H%M%S).sql
# Or simply copy the file
cp database/jaws.db database/backup_$(date +%Y%m%d_%H%M%S).db
```

### Restore
```bash
sqlite3 database/jaws.db < database/backup_YYYYMMDD_HHMMSS.sql
# Or copy the backup file
cp database/backup_YYYYMMDD_HHMMSS.db database/jaws.db
```

## CSV Migration

To migrate data from the legacy CSV files to SQLite, run:

```bash
php database/migrate_from_csv.php
```

**Important:** Backup CSV files before migration:
```bash
cp Libraries/Fleet/data/fleet_data.csv Libraries/Fleet/data/fleet_data.backup.csv
cp Libraries/Squad/data/squad_data.csv Libraries/Squad/data/squad_data.backup.csv
```

## Development vs Production

The database uses the same schema in development and production. The `season_config.source` field controls time behavior:

- `production` - Uses real system time
- `simulated` - Uses `season_config.simulated_date` for testing

## Foreign Keys

Foreign key constraints are enabled by default. All cascade deletes are configured:

- Deleting a boat removes its availability, history, and references
- Deleting a crew removes its availability, history, and whitelist entries
- Deleting an event removes all associated availability and history records

## Query Examples

```sql
-- Get all available boats for an event
SELECT b.* FROM boats b
JOIN boat_availability ba ON b.id = ba.boat_id
WHERE ba.event_id = 'Fri May 29' AND ba.berths > 0;

-- Get all available crews for an event
SELECT c.* FROM crews c
JOIN crew_availability ca ON c.id = ca.crew_id
WHERE ca.event_id = 'Fri May 29' AND ca.status IN (1, 2);

-- Get crew assignment history
SELECT c.display_name, ch.event_id, ch.boat_key
FROM crews c
JOIN crew_history ch ON c.id = ch.crew_id
WHERE c.key = 'johndoe'
ORDER BY ch.event_id;

-- Get boat's crew assignments for an event
SELECT c.display_name, c.skill
FROM crews c
JOIN crew_history ch ON c.id = ch.crew_id
WHERE ch.event_id = 'Fri May 29' AND ch.boat_key = 'sailaway';
```
