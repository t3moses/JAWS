<?php

/**
 * Email to Users Migration Script
 *
 * This script migrates existing crew and boat owner emails to the users table
 * and links the user_id foreign keys in crews and boats tables.
 *
 * Migration Strategy:
 * 1. Extract unique emails from crews and boats tables
 * 2. Create user accounts for each unique email
 * 3. Link crews.user_id to corresponding user records
 * 4. Link boats.owner_user_id to corresponding user records
 * 5. Handle "flex users" who are both crew members and boat owners
 *
 * Usage:
 *   php database/migrate_emails_to_users.php
 *
 * Prerequisites:
 *   - Database migration 002_add_users_authentication.sql must be applied first
 *   - Backup your database before running this script
 *
 * IMPORTANT: This script generates random temporary passwords for migrated users.
 * Users will need to reset their passwords via the password reset flow.
 */

$dbPath = __DIR__ . '/jaws.db';

echo "\n";
echo "======================================\n";
echo "JAWS Email-to-Users Migration\n";
echo "======================================\n";
echo "\n";

// Check if database exists
if (!file_exists($dbPath)) {
    echo "ERROR: Database not found at: $dbPath\n";
    echo "Please ensure the database exists before running this migration.\n";
    exit(1);
}

// Check for --yes flag (non-interactive mode)
$forceYes = in_array('--yes', $argv ?? []) || in_array('-y', $argv ?? []);

// Display migration information
echo "This script will:\n";
echo "  1. Extract unique emails from crews and boats tables\n";
echo "  2. Create user accounts with temporary passwords\n";
echo "  3. Link crew records to user accounts (crews.user_id)\n";
echo "  4. Link boat records to user accounts (boats.owner_user_id)\n";
echo "  5. Handle flex users (both crew and boat owners)\n";
echo "\n";
echo "Database: $dbPath\n";
echo "\n";

// Create database backup
$backupPath = $dbPath . '.backup.' . date('YmdHis');
echo "Creating backup: $backupPath\n";
if (!copy($dbPath, $backupPath)) {
    echo "ERROR: Failed to create backup!\n";
    exit(1);
}
echo "Backup created successfully.\n";
echo "\n";

// Confirm migration
if (!$forceYes) {
    echo "Do you want to continue with the migration? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'yes') {
        echo "Migration cancelled.\n";
        exit(0);
    }
    echo "\n";
}

try {
    // Connect to database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    echo "Starting migration...\n";
    echo "\n";

    // Begin transaction
    $pdo->beginTransaction();

    // =========================================================================
    // Step 1: Verify required tables and columns exist
    // =========================================================================
    echo "[1/6] Verifying database schema...\n";

    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('users', $tables)) {
        throw new Exception("Users table not found! Please apply migration 002_add_users_authentication.sql first.");
    }

    // Check for user_id column in crews table
    $crewColumns = $pdo->query("PRAGMA table_info(crews)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('user_id', $crewColumns)) {
        throw new Exception("Column 'user_id' not found in crews table! Please apply migration 002_add_users_authentication.sql first.");
    }

    // Check for owner_user_id column in boats table
    $boatColumns = $pdo->query("PRAGMA table_info(boats)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('owner_user_id', $boatColumns)) {
        throw new Exception("Column 'owner_user_id' not found in boats table! Please apply migration 002_add_users_authentication.sql first.");
    }

    echo "  ✓ Required tables and columns exist\n";
    echo "\n";

    // =========================================================================
    // Step 2: Extract unique emails from crews and boats
    // =========================================================================
    echo "[2/6] Extracting unique emails...\n";

    // Get all crew emails
    $crewEmailsStmt = $pdo->query("
        SELECT DISTINCT
            LOWER(TRIM(email)) as email,
            first_name,
            last_name
        FROM crews
        WHERE email IS NOT NULL AND email != ''
    ");
    $crewEmails = $crewEmailsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all boat owner emails
    $boatEmailsStmt = $pdo->query("
        SELECT DISTINCT
            LOWER(TRIM(owner_email)) as email,
            owner_first_name as first_name,
            owner_last_name as last_name
        FROM boats
        WHERE owner_email IS NOT NULL AND owner_email != ''
    ");
    $boatEmails = $boatEmailsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge and deduplicate emails
    $emailMap = [];
    $flexEmails = []; // Emails that appear in both crews and boats

    foreach ($crewEmails as $row) {
        $emailMap[$row['email']] = [
            'email' => $row['email'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'is_crew' => true,
            'is_boat_owner' => false,
        ];
    }

    foreach ($boatEmails as $row) {
        if (isset($emailMap[$row['email']])) {
            // This email exists in both crews and boats - it's a flex user
            $emailMap[$row['email']]['is_boat_owner'] = true;
            $flexEmails[] = $row['email'];
        } else {
            $emailMap[$row['email']] = [
                'email' => $row['email'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'is_crew' => false,
                'is_boat_owner' => true,
            ];
        }
    }

    echo "  ✓ Found " . count($crewEmails) . " unique crew emails\n";
    echo "  ✓ Found " . count($boatEmails) . " unique boat owner emails\n";
    echo "  ✓ Identified " . count($flexEmails) . " flex users (both crew and boat owner)\n";
    echo "  ✓ Total unique emails to migrate: " . count($emailMap) . "\n";
    echo "\n";

    // =========================================================================
    // Step 3: Create user accounts for each unique email
    // =========================================================================
    echo "[3/6] Creating user accounts...\n";

    $insertUserStmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, account_type, is_admin, created_at)
        VALUES (:email, :password_hash, :account_type, 0, CURRENT_TIMESTAMP)
    ");

    $createdUsers = 0;
    $skippedUsers = 0;

    foreach ($emailMap as $email => $data) {
        // Check if user already exists
        $existingUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $existingUser->execute([$email]);
        if ($existingUser->fetch()) {
            $skippedUsers++;
            continue;
        }

        // Determine account type
        // For flex users, we'll set account_type as 'crew' since they participate as crew
        // They can still own boats through the same account
        if ($data['is_crew']) {
            $accountType = 'crew';
        } else {
            $accountType = 'boat_owner';
        }

        // Generate a temporary password hash
        // Users will need to reset their password on first login
        $tempPassword = bin2hex(random_bytes(16)); // Generate random 32-character password
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // Create user account
        $insertUserStmt->execute([
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':account_type' => $accountType,
        ]);

        $createdUsers++;

        // Store the generated password for display (in production, this would trigger a password reset email)
        $emailMap[$email]['temp_password'] = $tempPassword;
        $emailMap[$email]['user_id'] = $pdo->lastInsertId();
    }

    echo "  ✓ Created $createdUsers new user accounts\n";
    if ($skippedUsers > 0) {
        echo "  ✓ Skipped $skippedUsers existing users\n";
    }
    echo "\n";

    // =========================================================================
    // Step 4: Link crew records to user accounts
    // =========================================================================
    echo "[4/6] Linking crew records to user accounts...\n";

    $updateCrewStmt = $pdo->prepare("
        UPDATE crews
        SET user_id = :user_id
        WHERE LOWER(TRIM(email)) = :email
    ");

    $linkedCrews = 0;
    foreach ($emailMap as $email => $data) {
        if ($data['is_crew']) {
            $updateCrewStmt->execute([
                ':user_id' => $data['user_id'],
                ':email' => $email,
            ]);
            $linkedCrews += $updateCrewStmt->rowCount();
        }
    }

    echo "  ✓ Linked $linkedCrews crew records to user accounts\n";
    echo "\n";

    // =========================================================================
    // Step 5: Link boat records to user accounts
    // =========================================================================
    echo "[5/6] Linking boat records to user accounts...\n";

    $updateBoatStmt = $pdo->prepare("
        UPDATE boats
        SET owner_user_id = :user_id
        WHERE LOWER(TRIM(owner_email)) = :email
    ");

    $linkedBoats = 0;
    foreach ($emailMap as $email => $data) {
        if ($data['is_boat_owner']) {
            $updateBoatStmt->execute([
                ':user_id' => $data['user_id'],
                ':email' => $email,
            ]);
            $linkedBoats += $updateBoatStmt->rowCount();
        }
    }

    echo "  ✓ Linked $linkedBoats boat records to user accounts\n";
    echo "\n";

    // =========================================================================
    // Step 6: Verify migration results
    // =========================================================================
    echo "[6/6] Verifying migration results...\n";

    // Count crews without user_id
    $unlinkedCrews = $pdo->query("SELECT COUNT(*) FROM crews WHERE user_id IS NULL")->fetchColumn();
    if ($unlinkedCrews > 0) {
        echo "  ⚠ WARNING: $unlinkedCrews crew records are not linked to user accounts (likely missing/invalid emails)\n";
    } else {
        echo "  ✓ All crew records are linked to user accounts\n";
    }

    // Count boats without owner_user_id
    $unlinkedBoats = $pdo->query("SELECT COUNT(*) FROM boats WHERE owner_user_id IS NULL")->fetchColumn();
    if ($unlinkedBoats > 0) {
        echo "  ⚠ WARNING: $unlinkedBoats boat records are not linked to user accounts (likely missing/invalid emails)\n";
    } else {
        echo "  ✓ All boat records are linked to user accounts\n";
    }

    // Count total users
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "  ✓ Total user accounts: $totalUsers\n";

    echo "\n";

    // Commit transaction
    $pdo->commit();

    echo "======================================\n";
    echo "Migration completed successfully!\n";
    echo "======================================\n";
    echo "\n";

    // Display summary
    echo "Summary:\n";
    echo "  Users created: $createdUsers\n";
    echo "  Crews linked: $linkedCrews\n";
    echo "  Boats linked: $linkedBoats\n";
    echo "  Flex users: " . count($flexEmails) . "\n";
    echo "\n";

    if ($unlinkedCrews > 0 || $unlinkedBoats > 0) {
        echo "Action Required:\n";
        if ($unlinkedCrews > 0) {
            echo "  - Review $unlinkedCrews crew records without user accounts\n";
            echo "    Query: SELECT id, first_name, last_name, email FROM crews WHERE user_id IS NULL\n";
        }
        if ($unlinkedBoats > 0) {
            echo "  - Review $unlinkedBoats boat records without user accounts\n";
            echo "    Query: SELECT id, display_name, owner_first_name, owner_last_name, owner_email FROM boats WHERE owner_user_id IS NULL\n";
        }
        echo "\n";
    }

    echo "Next Steps:\n";
    echo "  1. All migrated users have temporary random passwords\n";
    echo "  2. Implement password reset flow to allow users to set their own passwords\n";
    echo "  3. Send password reset emails to all migrated users\n";
    echo "\n";

    echo "Database backup saved at:\n";
    echo "  $backupPath\n";
    echo "\n";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "\n";
    echo "======================================\n";
    echo "Migration failed!\n";
    echo "======================================\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\n";
    echo "The database has been rolled back to its original state.\n";
    echo "Backup is available at: $backupPath\n";
    echo "\n";
    exit(1);
}
