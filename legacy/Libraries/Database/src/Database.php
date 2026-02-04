<?php

function getDatabase() {

    static $db = null;
    
    // Return existing connection if already created (singleton pattern)

    if ($db !== null) {
        return $db;
    }
    
    try {
        define('DB_PATH', __DIR__ . '/../../../../database/program.db');
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Initialize tables
        initializeTables($db);
        
        return $db;
    }
    catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

function initializeTables($db) {

    // Create user authentication table

    $db->exec("CREATE TABLE IF NOT EXISTS
        authn (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_key TEXT NOT NULL,
            password TEXT NOT NULL,
            account_type TEXT NOT NULL,
            is_active INTEGER DEFAULT 1
        )"
    );
    
    // Create fleet table

    $db->exec("CREATE TABLE IF NOT EXISTS
        fleet (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_key TEXT NOT NULL,
            display_name TEXT NOT NULL,
            owner_key TEXT NOT NULL,
            owner_email TEXT NOT NULL,
            owner_mobile TEXT NOT NULL,
            min_berths INTEGER NOT NULL,
            max_berths INTEGER NOT NULL,
            assistance_required TEXT NOT NULL,
            social_preference TEXT NOT NULL,
            rank TEXT NOT NULL,
            occupied_berths TEXT NOT NULL,
            berths TEXT NOT NULL,
            history TEXT NOT NULL,
            is_active INTEGER DEFAULT 1
        )"
    );

    // Create squad table

    $db->exec("CREATE TABLE IF NOT EXISTS
        squad (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity_key TEXT NOT NULL,
            display_name TEXT NOT NULL,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            partner_key TEXT,
            email TEXT,
            mobile TEXT,
            social_preference TEXT NOT NULL,
            notification_preference TEXT NOT NULL,
            membership_number TEXT,
            rank TEXT NOT NULL,
            skill TEXT NOT NULL,
            experience TEXT,
            available TEXT NOT NULL,
            whitelist TEXT DEFAULT NULL,
            history TEXT NOT NULL,
            is_active INTEGER DEFAULT 1
        )"
    );

}

function update_rank( $_table, $_entity_key, $_rank_dimension, $_rank_value ) {

    // Whitelist allowed tables

    $allowed_tables = ['fleet', 'squad'];
    if (!in_array($_table, $allowed_tables)) {
        throw new Exception("Invalid table name");
    }

    $db = getDatabase();
    try {
        $stmt = $db->prepare("SELECT rank FROM $_table WHERE entity_key = :entity_key");
        $stmt->execute([':entity_key' => $_entity_key]);
        $_rank = $stmt->fetchColumn();
        $_rank_array = explode( ';', $_rank );
        $_rank_array[ $_rank_dimension ] = $_rank_value;
        $_new_rank = implode( ';', $_rank_array );
        $stmt = $db->prepare("UPDATE $_table SET rank = :new_rank WHERE entity_key = :entity_key");
        $stmt->execute([
            ':new_rank' => $_new_rank,
            ':entity_key' => $_entity_key
        ]);
    } catch (PDOException $e) {
        die('Update failed: ' . $e->getMessage());
    }
}


?>