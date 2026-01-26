-- JAWS Clean Architecture - Initial Database Schema
-- SQLite Database Schema for Social Day Cruising Program
-- Migration: 001_initial_schema.sql

-- Table: boats
-- Stores boat information including owner details, berth capacity, and ranking data
CREATE TABLE IF NOT EXISTS boats (
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

CREATE INDEX IF NOT EXISTS idx_boats_key ON boats(key);
CREATE INDEX IF NOT EXISTS idx_boats_owner ON boats(owner_first_name, owner_last_name);

-- Table: crews
-- Stores crew member information including skills, preferences, and ranking data
CREATE TABLE IF NOT EXISTS crews (
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

CREATE INDEX IF NOT EXISTS idx_crews_key ON crews(key);
CREATE INDEX IF NOT EXISTS idx_crews_name ON crews(first_name, last_name);
CREATE INDEX IF NOT EXISTS idx_crews_partner ON crews(partner_key);

-- Table: events
-- Stores event schedule information
CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT NOT NULL UNIQUE,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    finish_time TIME NOT NULL,
    status TEXT CHECK(status IN ('upcoming', 'past', 'cancelled')) DEFAULT 'upcoming',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date);
CREATE INDEX IF NOT EXISTS idx_events_event_id ON events(event_id);

-- Table: boat_availability
-- Tracks how many berths each boat offers for each event
CREATE TABLE IF NOT EXISTS boat_availability (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    boat_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    berths INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(boat_id, event_id)
);

CREATE INDEX IF NOT EXISTS idx_boat_availability_boat ON boat_availability(boat_id);
CREATE INDEX IF NOT EXISTS idx_boat_availability_event ON boat_availability(event_id);

-- Table: crew_availability
-- Tracks crew availability status for each event
-- status: 0=UNAVAILABLE, 1=AVAILABLE, 2=GUARANTEED, 3=WITHDRAWN
CREATE TABLE IF NOT EXISTS crew_availability (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crew_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    status INTEGER CHECK(status IN (0, 1, 2, 3)) DEFAULT 0,
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(crew_id, event_id)
);

CREATE INDEX IF NOT EXISTS idx_crew_availability_crew ON crew_availability(crew_id);
CREATE INDEX IF NOT EXISTS idx_crew_availability_event ON crew_availability(event_id);
CREATE INDEX IF NOT EXISTS idx_crew_availability_status ON crew_availability(status);

-- Table: boat_history
-- Tracks boat participation history for each event
-- participated: 'Y' or '' (empty string)
CREATE TABLE IF NOT EXISTS boat_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    boat_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    participated TEXT CHECK(participated IN ('Y', '')) DEFAULT '',
    FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(boat_id, event_id)
);

CREATE INDEX IF NOT EXISTS idx_boat_history_boat ON boat_history(boat_id);
CREATE INDEX IF NOT EXISTS idx_boat_history_event ON boat_history(event_id);

-- Table: crew_history
-- Tracks which boat each crew member was assigned to for each event
-- boat_key: empty string if not assigned, otherwise the boat's key
CREATE TABLE IF NOT EXISTS crew_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crew_id INTEGER NOT NULL,
    event_id TEXT NOT NULL,
    boat_key TEXT DEFAULT '',
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    UNIQUE(crew_id, event_id)
);

CREATE INDEX IF NOT EXISTS idx_crew_history_crew ON crew_history(crew_id);
CREATE INDEX IF NOT EXISTS idx_crew_history_event ON crew_history(event_id);
CREATE INDEX IF NOT EXISTS idx_crew_history_boat ON crew_history(boat_key);

-- Table: crew_whitelist
-- Stores crew preferences for specific boats (whitelisted boats)
CREATE TABLE IF NOT EXISTS crew_whitelist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    crew_id INTEGER NOT NULL,
    boat_key TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crew_id) REFERENCES crews(id) ON DELETE CASCADE,
    UNIQUE(crew_id, boat_key)
);

CREATE INDEX IF NOT EXISTS idx_crew_whitelist_crew ON crew_whitelist(crew_id);
CREATE INDEX IF NOT EXISTS idx_crew_whitelist_boat ON crew_whitelist(boat_key);

-- Table: season_config
-- Stores season-wide configuration (singleton table with id=1)
-- source: 'simulated' or 'production' (time source for testing vs production)
CREATE TABLE IF NOT EXISTS season_config (
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

-- Insert default configuration
INSERT OR IGNORE INTO season_config (id, year, source, start_time, finish_time, blackout_from, blackout_to)
VALUES (1, 2026, 'production', '12:45:00', '17:00:00', '10:00:00', '18:00:00');

-- Table: flotillas
-- Stores generated flotilla assignments for each event (serialized JSON)
CREATE TABLE IF NOT EXISTS flotillas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id TEXT NOT NULL UNIQUE,
    flotilla_data TEXT NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_flotillas_event ON flotillas(event_id);

-- Triggers for updated_at timestamp maintenance
CREATE TRIGGER IF NOT EXISTS boats_updated_at
AFTER UPDATE ON boats
BEGIN
    UPDATE boats SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS crews_updated_at
AFTER UPDATE ON crews
BEGIN
    UPDATE crews SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS season_config_updated_at
AFTER UPDATE ON season_config
BEGIN
    UPDATE season_config SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
