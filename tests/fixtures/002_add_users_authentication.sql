-- JAWS Clean Architecture - User Authentication Migration
-- Migration: 002_add_users_authentication.sql
-- Purpose: Add users table and link to crews/boats for JWT authentication

-- =======================
-- Users Table
-- =======================
-- Stores user authentication credentials and account information
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    account_type TEXT NOT NULL CHECK(account_type IN ('crew', 'boat_owner')),
    is_admin BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_account_type ON users(account_type);

-- =======================
-- Link Users to Crews
-- =======================
-- Add user_id foreign key to crews table (nullable during migration)
ALTER TABLE crews ADD COLUMN user_id INTEGER REFERENCES users(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS idx_crews_user_id ON crews(user_id);

-- =======================
-- Link Users to Boats
-- =======================
-- Add owner_user_id foreign key to boats table (nullable during migration)
ALTER TABLE boats ADD COLUMN owner_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS idx_boats_owner_user_id ON boats(owner_user_id);

-- =======================
-- Triggers
-- =======================
-- Trigger for users.updated_at timestamp maintenance
CREATE TRIGGER IF NOT EXISTS users_updated_at
AFTER UPDATE ON users
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
