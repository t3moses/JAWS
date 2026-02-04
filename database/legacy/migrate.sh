#!/bin/bash
set -e

DB_PATH="./database/jaws.db"
MIGRATIONS_DIR="./database/migrations"

# Create DB and tracking table
mkdir -p $(dirname "$DB_PATH")
sqlite3 "$DB_PATH" "CREATE TABLE IF NOT EXISTS migration_history (version TEXT PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP);"

for file in $(ls "$MIGRATIONS_DIR"/*.sql | sort); do
    filename=$(basename "$file")

    # Check if already applied
    is_done=$(sqlite3 "$DB_PATH" "SELECT version FROM migration_history WHERE version='$filename';")

    if [ -z "$is_done" ]; then
        echo "--> Applying: $filename"

        # Execute migration and history update inside a single transaction
        # If the SQL file fails, 'set -e' will stop the script here
        sqlite3 "$DB_PATH" <<EOF
BEGIN;
$(cat "$file")
INSERT INTO migration_history (version) VALUES ('$filename');
COMMIT;
EOF
    else
        echo "--> Skipping: $filename"
    fi
done
