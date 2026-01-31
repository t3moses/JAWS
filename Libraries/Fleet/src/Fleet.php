<?php

require_once __DIR__ . '/../../Database/src/Database.php';

function registerBoat($_entity_key, $_display_name, $_owner_key,
            $_owner_email, $_owner_mobile, $_min_berths, $_max_berths,
            $_assistance_required, $_social_preference, $_rank,
            $_occupied_berths, $_berths, $_history) {

    $db = getDatabase();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO fleet (entity_key, display_name, owner_key,
            owner_email, owner_mobile, min_berths, max_berths,
            assistance_required, social_preference, rank,
            occupied_berths, berths, history) 
            VALUES (:entity_key, :display_name, :owner_key,
            :owner_email, :owner_mobile, :min_berths, :max_berths,
            :assistance_required, :social_preference, :rank,
            :occupied_berths, :berths, :history)
        ");
        
        $stmt->execute([
            ':entity_key' => $_entity_key,
            ':display_name' => $_display_name,
            ':owner_key' => $_owner_key,
            ':owner_email' => $_owner_email,
            ':owner_mobile' => $_owner_mobile,
            ':min_berths' => $_min_berths,
            ':max_berths' => $_max_berths,
            ':assistance_required' => $_assistance_required,
            ':social_preference' => $_social_preference,
            ':rank' => $_rank,
            ':occupied_berths' => $_occupied_berths,
            ':berths' => $_berths,
            ':history' => $_history
        ]);
        
        return true;
    } catch (PDOException $e) {
        die('Insert failed: ' . $e->getMessage());
    }
}

function crew_as_boat_owner( $_boat_key ) {

    // Return the crew key for the owner of the boat,
    // or false if the owner is not registered as crew.

    $db = getDatabase();
    $stmt = $db->prepare("SELECT owner_key FROM fleet WHERE entity_key = :boat_key LIMIT 1");
    $stmt->execute([':boat_key' => $_boat_key]);
    $_owner = $stmt->fetchColumn();
    $stmt = $db->prepare("SELECT entity_key FROM squad WHERE entity_key = :owner_key LIMIT 1");
    $stmt->execute([':owner_key' => $_owner]);
    return $stmt->fetchColumn();
}

?>