<?php
/**
 * Database Initialization Script
 *
 * This script creates the SQLite database and applies the initial schema migration.
 * Run this script once to set up the database before using the application.
 *
 * Usage:
 *   php database/init_database.php           # Interactive mode (prompts for confirmation)
 *   php database/init_database.php --yes     # Non-interactive mode (auto-confirms)
 */

$dbPath = __DIR__ . '/jaws.db';
$migrationFile = __DIR__ . '/migrations/001_initial_schema.sql';

// Check for --yes flag (non-interactive mode)
$forceYes = in_array('--yes', $argv ?? []) || in_array('-y', $argv ?? []);

echo "JAWS Database Initialization\n";
echo "============================\n\n";

// Check if database already exists
if (file_exists($dbPath)) {
    echo "WARNING: Database already exists at: $dbPath\n";

    if ($forceYes) {
        $line = 'yes';
    } else {
        echo "Do you want to recreate it? This will delete all existing data! (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
    }

    if ($line !== 'yes') {
        echo "Aborted.\n";
        exit(0);
    }

    echo "Backing up existing database...\n";
    $backupPath = $dbPath . '.backup.' . date('YmdHis');
    copy($dbPath, $backupPath);
    echo "Backup created: $backupPath\n";

    unlink($dbPath);
    echo "Existing database deleted.\n\n";
}

// Check if migration file exists
if (!file_exists($migrationFile)) {
    echo "ERROR: Migration file not found: $migrationFile\n";
    exit(1);
}

echo "Creating new database at: $dbPath\n";

try {
    // Create database connection
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Enable foreign keys
    $pdo->exec('PRAGMA foreign_keys = ON');

    echo "Reading migration file...\n";
    $sql = file_get_contents($migrationFile);

    echo "Applying schema migration...\n";
    $pdo->exec($sql);

    echo "Schema applied successfully!\n\n";

    // Verify tables were created
    echo "Verifying database structure:\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $expectedTables = [
        'boat_availability',
        'boat_history',
        'boats',
        'crew_availability',
        'crew_history',
        'crew_whitelist',
        'crews',
        'events',
        'flotillas',
        'season_config'
    ];

    echo "\nTables created:\n";
    foreach ($tables as $table) {
        $check = in_array($table, $expectedTables) ? '✓' : '?';
        echo "  $check $table\n";
    }

    $missing = array_diff($expectedTables, $tables);
    if (!empty($missing)) {
        echo "\nWARNING: Missing tables: " . implode(', ', $missing) . "\n";
    }

    // Verify indexes
    echo "\nVerifying indexes...\n";
    $stmt = $pdo->query("SELECT name, tbl_name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_%' ORDER BY tbl_name, name");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Indexes created: " . count($indexes) . "\n";
    foreach ($indexes as $index) {
        echo "  ✓ {$index['tbl_name']}.{$index['name']}\n";
    }

    // Verify triggers
    echo "\nVerifying triggers...\n";
    $stmt = $pdo->query("SELECT name, tbl_name FROM sqlite_master WHERE type='trigger' ORDER BY tbl_name, name");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Triggers created: " . count($triggers) . "\n";
    foreach ($triggers as $trigger) {
        echo "  ✓ {$trigger['tbl_name']}.{$trigger['name']}\n";
    }

    // Verify season_config initialization
    echo "\nVerifying season configuration...\n";
    $stmt = $pdo->query("SELECT * FROM season_config WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($config) {
        echo "  ✓ Season config initialized:\n";
        echo "    - Year: {$config['year']}\n";
        echo "    - Source: {$config['source']}\n";
        echo "    - Event time: {$config['start_time']} - {$config['finish_time']}\n";
        echo "    - Blackout window: {$config['blackout_from']} - {$config['blackout_to']}\n";
    } else {
        echo "  ✗ WARNING: Season config not initialized!\n";
    }

    // Set database file permissions (if on Unix-like system)
    if (PHP_OS_FAMILY !== 'Windows') {
        chmod($dbPath, 0664);
        echo "\nDatabase file permissions set to 0664\n";
    }

    echo "\n============================\n";
    echo "Database initialization complete!\n";
    echo "Database: $dbPath\n";
    echo "Size: " . filesize($dbPath) . " bytes\n";

} catch (PDOException $e) {
    echo "\nERROR: Database initialization failed!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
