<?php

/**
 * CSV to SQLite Migration Script
 *
 * This script migrates data from the legacy CSV files to the SQLite database.
 *
 * Usage:
 *   php database/migrate_from_csv.php
 *
 * IMPORTANT: This will backup your CSV files automatically before migration.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\CSV\CsvMigration;
use App\Infrastructure\Persistence\SQLite\Connection;

echo "\n";
echo "======================================\n";
echo "JAWS CSV to SQLite Migration\n";
echo "======================================\n";
echo "\n";

// Set database path
$dbPath = __DIR__ . '/jaws.db';
Connection::setDatabasePath($dbPath);

echo "Database: {$dbPath}\n";
echo "\n";

// Check if database exists
if (!file_exists($dbPath)) {
    echo "ERROR: Database not found at {$dbPath}\n";
    echo "Please run 'php database/init_database.php' first to create the database.\n";
    exit(1);
}

// Confirm migration
echo "This will migrate data from CSV files to the SQLite database.\n";
echo "CSV backups will be created automatically.\n";
echo "\n";
echo "Do you want to continue? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "Migration cancelled.\n";
    exit(0);
}

echo "\n";
echo "Starting migration...\n";
echo "\n";

try {
    $migration = new CsvMigration();
    $result = $migration->migrate(backup: true);

    echo "\n";

    if ($result['success']) {
        echo "======================================\n";
        echo "Migration completed successfully!\n";
        echo "======================================\n";
        echo "\n";
        echo "Summary:\n";
        echo "  Events migrated: {$result['events']}\n";
        echo "  Boats migrated: {$result['boats']}\n";
        echo "  Crews migrated: {$result['crews']}\n";
        echo "\n";
        echo "CSV backups created in:\n";
        echo "  - legacy/Libraries/Fleet/data/fleet_data.csv.backup.*\n";
        echo "  - legacy/Libraries/Squad/data/squad_data.csv.backup.*\n";
        echo "\n";
    } else {
        echo "======================================\n";
        echo "Migration failed!\n";
        echo "======================================\n";
        echo "\n";
        echo "Error: {$result['error']}\n";
        echo "\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n";
    echo "======================================\n";
    echo "Migration failed with exception!\n";
    echo "======================================\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\n";
    exit(1);
}
