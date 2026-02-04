<?php

session_start();

// A library of functions to handle user authentication.

require_once __DIR__ . '/../../Database/src/Database.php';

function registerUser($_entity_key, $password, $accountType) {

    $db = getDatabase();
    
    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $db->prepare("
            INSERT INTO authn (entity_key, password, account_type) 
            VALUES (:entity_key, :password, :account_type)
        ");
        
        $stmt->execute([
            ':entity_key' => $_entity_key,
            ':password' => $passwordHash,
            ':account_type' => $accountType
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Log the actual error for debugging
        error_log($e->getMessage());
        return false;
    }
}

function loginUser($_entity_key, $password) {
    $db = getDatabase();
    
    $stmt = $db->prepare("
        SELECT id, entity_key, password, account_type, is_active 
        FROM authn 
        WHERE entity_key = :entity_key
    ");
    
    $stmt->execute([':entity_key' => $_entity_key]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_active'] == 1) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['entity_key'] = $user['entity_key'];
            $_SESSION['account_type'] = $user['account_type'];
            return true;
        }
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: account.html');
        exit;
    }
}

function logout() {
    session_destroy();
    header('Location: index.html');
    exit;
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'entity_key' => $_SESSION['entity_key'],
            'account_type' => $_SESSION['account_type']
        ];
    }
    return null;
}
?>
