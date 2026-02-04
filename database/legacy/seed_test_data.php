<?php
/**
 * Test Data Seeding Script for CI/CD
 *
 * This script populates the database with minimal test data for running API tests.
 * Unlike migrate_from_csv.php, this script runs non-interactively and is designed
 * specifically for CI/CD pipelines.
 *
 * Usage:
 *   php database/seed_test_data.php
 */

$dbPath = __DIR__ . '/jaws.db';

echo "JAWS Test Data Seeding\n";
echo "======================\n\n";

// Check if database exists
if (!file_exists($dbPath)) {
    echo "ERROR: Database not found at: $dbPath\n";
    echo "Please run 'php database/init_database.php' first to create the database.\n";
    exit(1);
}

try {
    // Create database connection
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Enable foreign keys
    $pdo->exec('PRAGMA foreign_keys = ON');

    echo "Connected to database: $dbPath\n\n";

    // Clear existing test data (for idempotency)
    echo "Clearing existing data...\n";
    $pdo->exec('DELETE FROM flotillas');
    $pdo->exec('DELETE FROM crew_whitelist');
    $pdo->exec('DELETE FROM crew_history');
    $pdo->exec('DELETE FROM boat_history');
    $pdo->exec('DELETE FROM crew_availability');
    $pdo->exec('DELETE FROM boat_availability');
    $pdo->exec('DELETE FROM crews');
    $pdo->exec('DELETE FROM boats');
    $pdo->exec('DELETE FROM events');
    echo "  ✓ Existing data cleared\n\n";

    // Seed events
    echo "Seeding events...\n";
    $events = [
        ['event_id' => 'Fri May 29', 'event_date' => '2026-05-29', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
        ['event_id' => 'Fri Jun 05', 'event_date' => '2026-06-05', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
        ['event_id' => 'Fri Jun 12', 'event_date' => '2026-06-12', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
        ['event_id' => 'Fri Jun 19', 'event_date' => '2026-06-19', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
        ['event_id' => 'Fri Jun 26', 'event_date' => '2026-06-26', 'start_time' => '12:45:00', 'finish_time' => '17:00:00', 'status' => 'upcoming'],
    ];

    $stmt = $pdo->prepare('INSERT INTO events (event_id, event_date, start_time, finish_time, status) VALUES (?, ?, ?, ?, ?)');
    foreach ($events as $event) {
        $stmt->execute([
            $event['event_id'],
            $event['event_date'],
            $event['start_time'],
            $event['finish_time'],
            $event['status']
        ]);
        echo "  ✓ {$event['event_id']} ({$event['event_date']})\n";
    }

    $eventCount = count($events);
    echo "  Total: {$eventCount} events seeded\n\n";

    // Seed sample boats (optional - for more comprehensive testing)
    echo "Seeding sample boats...\n";
    $boats = [
        ['key' => 'sailaway', 'display_name' => 'Sailaway', 'owner_first_name' => 'Alice', 'owner_last_name' => 'Johnson', 'owner_email' => 'alice@example.com', 'owner_mobile' => '555-0001', 'min_berths' => 2, 'max_berths' => 4, 'assistance_required' => 'No', 'social_preference' => 'Yes'],
        ['key' => 'windchaser', 'display_name' => 'Windchaser', 'owner_first_name' => 'Bob', 'owner_last_name' => 'Smith', 'owner_email' => 'bob@example.com', 'owner_mobile' => '555-0002', 'min_berths' => 2, 'max_berths' => 3, 'assistance_required' => 'Yes', 'social_preference' => 'No'],
    ];

    $stmt = $pdo->prepare('INSERT INTO boats (key, display_name, owner_first_name, owner_last_name, owner_email, owner_mobile, min_berths, max_berths, assistance_required, social_preference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($boats as $boat) {
        $stmt->execute([
            $boat['key'],
            $boat['display_name'],
            $boat['owner_first_name'],
            $boat['owner_last_name'],
            $boat['owner_email'],
            $boat['owner_mobile'],
            $boat['min_berths'],
            $boat['max_berths'],
            $boat['assistance_required'],
            $boat['social_preference']
        ]);
        echo "  ✓ {$boat['display_name']} (owner: {$boat['owner_first_name']} {$boat['owner_last_name']})\n";
    }

    $boatCount = count($boats);
    echo "  Total: {$boatCount} boats seeded\n\n";

    // Seed sample crews (optional - for more comprehensive testing)
    echo "Seeding sample crews...\n";
    $crews = [
        ['key' => 'jane_doe', 'display_name' => 'Jane Doe', 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com', 'mobile' => '555-1001', 'skill' => 1, 'membership_number' => 'NSC001'],
        ['key' => 'mike_wilson', 'display_name' => 'Mike Wilson', 'first_name' => 'Mike', 'last_name' => 'Wilson', 'email' => 'mike@example.com', 'mobile' => '555-1002', 'skill' => 2, 'membership_number' => 'NSC002'],
    ];

    $stmt = $pdo->prepare('INSERT INTO crews (key, display_name, first_name, last_name, email, mobile, skill, membership_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($crews as $crew) {
        $stmt->execute([
            $crew['key'],
            $crew['display_name'],
            $crew['first_name'],
            $crew['last_name'],
            $crew['email'],
            $crew['mobile'],
            $crew['skill'],
            $crew['membership_number']
        ]);
        echo "  ✓ {$crew['display_name']} (skill: {$crew['skill']})\n";
    }

    $crewCount = count($crews);
    echo "  Total: {$crewCount} crews seeded\n\n";

    // Seed boat availability (boats offering berths for events)
    echo "Seeding boat availability...\n";
    $stmt = $pdo->prepare('
        INSERT INTO boat_availability (boat_id, event_id, berths)
        SELECT b.id, e.event_id, b.max_berths
        FROM boats b
        CROSS JOIN events e
    ');
    $stmt->execute();
    $boatAvailCount = $stmt->rowCount();
    echo "  ✓ {$boatAvailCount} boat availability records created\n\n";

    // Seed crew availability (crews marked as available for events)
    echo "Seeding crew availability...\n";
    $stmt = $pdo->prepare('
        INSERT INTO crew_availability (crew_id, event_id, status)
        SELECT c.id, e.event_id, 1
        FROM crews c
        CROSS JOIN events e
    ');
    $stmt->execute();
    $crewAvailCount = $stmt->rowCount();
    echo "  ✓ {$crewAvailCount} crew availability records created (status: AVAILABLE)\n\n";

    // Verify seeded data
    echo "Verifying seeded data...\n";
    $stmt = $pdo->query('SELECT COUNT(*) FROM events');
    $eventCount = $stmt->fetchColumn();
    echo "  ✓ Events: {$eventCount}\n";

    $stmt = $pdo->query('SELECT COUNT(*) FROM boats');
    $boatCount = $stmt->fetchColumn();
    echo "  ✓ Boats: {$boatCount}\n";

    $stmt = $pdo->query('SELECT COUNT(*) FROM crews');
    $crewCount = $stmt->fetchColumn();
    echo "  ✓ Crews: {$crewCount}\n";

    $stmt = $pdo->query('SELECT COUNT(*) FROM boat_availability');
    $boatAvailCount = $stmt->fetchColumn();
    echo "  ✓ Boat availability records: {$boatAvailCount}\n";

    $stmt = $pdo->query('SELECT COUNT(*) FROM crew_availability');
    $crewAvailCount = $stmt->fetchColumn();
    echo "  ✓ Crew availability records: {$crewAvailCount}\n";

    echo "\n======================\n";
    echo "Test data seeding complete!\n";
    echo "Database: $dbPath\n";

} catch (PDOException $e) {
    echo "\nERROR: Test data seeding failed!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
