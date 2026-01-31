<?php

require_once __DIR__ . '/../../Database/src/Database.php';

function registerCrew($_key, $_display_name, $_first_name, $_last_name, $_partner_key,
            $_email, $_mobile, $_social_preference, $_notification_preference, $_rank,
            $_membership_number, $_skill, $_experience, $_available, $_history, $_whitelist) {

    $db = getDatabase();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO squad (entity_key,display_name,first_name,last_name,partner_key,email,mobile,
            social_preference,notification_preference,membership_number,rank,skill,experience,available,history,whitelist) 
            VALUES (:entity_key,:display_name,:first_name,:last_name,:partner_key,:email,:mobile,
            :social_preference,:notification_preference,:membership_number,:rank,:skill,:experience,:available,:history,:whitelist)
        ");
        
        $stmt->execute([
            ':entity_key' => $_key,
            ':display_name' => $_display_name,
            ':first_name' => $_first_name,
            ':last_name' => $_last_name,
            ':partner_key' => $_partner_key,
            ':email' => $_email,
            ':mobile' => $_mobile,
            ':social_preference' => $_social_preference,
            ':notification_preference' => $_notification_preference,
            ':membership_number' => $_membership_number,
            ':rank' => $_rank,
            ':skill' => $_skill,
            ':experience' => $_experience,
            ':available' => $_available,
            ':history' => $_history,
            ':whitelist' => $_whitelist 
        ]);
        
        return true;
    } catch (PDOException $e) {
        die('Insert failed: ' . $e->getMessage());
    }
}

function boat_owner_as_crew( $_crew_key ) {

    // Return the boat key if the crew member is a boat owner,
    // or false if the crew member is not a boat owner.

    $db = getDatabase();
    $stmt = $db->prepare("SELECT entity_key FROM fleet WHERE owner_key = :owner_key LIMIT 1");
    $stmt->execute([':owner_key' => $_crew_key]);
    return $stmt->fetchColumn();
}

?>