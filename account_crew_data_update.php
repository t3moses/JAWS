<?php

use nsc\sdc\name as name;
use nsc\sdc\mail as mail;
use nsc\sdc\config\rank as rank;
use nsc\sdc\season as season;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Mail/src/Mail.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Config/src/Rank.php';
require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Database/src/Database.php';
require_once __DIR__ . '/Libraries/Authn/src/Authn.php';

// Get the crew data from the submitted form.

$_first_name = name\safe($_POST['first_name']) ?? '';
$_last_name = name\safe($_POST['last_name']) ?? '';
$_membership_number = name\safe($_POST['membership_number']) ?? '';
$_password = $_POST['password'] ?? '';
$_crew_key = name\key_from_strings( $_first_name, $_last_name ) ?? '';
$_email = name\safe($_POST['email']) ?? '';
$_mobile = name\safe($_POST['mobile']) ?? '';
$_social_preference = name\safe($_POST['social_preference']) ?? '';
$_notification_preference = name\safe($_POST['notification_preference']) ?? '';
$_skill = name\safe($_POST['skill']) ?? '';
$_experience = name\safe($_POST['experience']) ?? '';

$db = getDatabase();
$stmt = $db->prepare("SELECT COUNT(*) FROM squad WHERE entity_key = :entity_key");
$stmt->execute([':entity_key' => $_crew_key]);
$exists = $stmt->fetchColumn() > 0;
if ( $exists ) {
    header('Location: /account_crew_login.php?state=2');
    exit();
}

registerUser($_crew_key, $_password, 'crew');

season\Season::load_season_data();
$_number_of_events = season\Season::get_event_count();
$_event_ids = season\Season::get_event_ids();

$_partner_key = '';
$_display_name = name\display_name_from_strings( $_first_name, $_last_name );

$_available = [];
$_history = [];
foreach( $_event_ids as $_event_id ) {
    $_available [] = '0';
    $_history[] = '';
}
$_available = implode( ';', $_available );
$_history = implode( ';', $_history );

// Set crew rank.

$_rank = [];

$_rank[ rank\Rank::CREW_RANK_COMMITMENT_DIMENSION ] = rank\Rank::UNAVAILABLE;

// If the crew is also a boat owner, set both the crew and boat flexibility rank accordingly.

$_boat_key = boat_owner_as_crew( $_crew_key );
if( $_boat_key !== false ) {
    $_flex = true;
    $_rank[ rank\Rank::CREW_RANK_FLEXIBILITY_DIMENSION ] = rank\Rank::FLEXIBLE;
    update_rank( 'fleet', $_boat_key, rank\Rank::BOAT_RANK_FLEXIBILITY_DIMENSION, rank\Rank::FLEXIBLE );
}
else {
    $_flex = false;
    $_rank[ rank\Rank::CREW_RANK_FLEXIBILITY_DIMENSION ] = rank\Rank::INFLEXIBLE;
}

if( is_member( $_membership_number ) ) {
    $_rank[ rank\Rank::CREW_RANK_MEMBERSHIP_DIMENSION ] = rank\Rank::MEMBER;
}
else {
    $_rank[ rank\Rank::CREW_RANK_MEMBERSHIP_DIMENSION ] = rank\Rank::NON_MEMBER;
}

$_rank[ rank\Rank::CREW_RANK_ABSENCE_DIMENSION ] = 0;

$_rank = implode( ';', $_rank );

// Add all registered boats to the crew whitelist, separated by ';' and omiting any leading semicolon.

$query = "SELECT GROUP_CONCAT(entity_key, ';') AS entity_keys FROM fleet";
$result = $db->query($query);
$row = $result->fetch(PDO::FETCH_ASSOC);
$_whitelist = $row['entity_keys'] ?? '';

registerCrew($_crew_key, $_display_name, $_first_name, $_last_name, $_partner_key, 
            $_email, $_mobile, $_social_preference, $_notification_preference, $_rank,
            $_membership_number, $_skill, $_experience, $_available, $_history, $_whitelist);

if( $_skill === "0" ) $_skill_text = "I am new to sailing";
else if ( $_skill === "1" ) $_skill_text = "I am a capable crew member";
else $_skill_text = "I am a capable first mate";

mail\Mail::send_new_crew_email( $_display_name, $_membership_number,
        $_skill, $_email, $_mobile, $_social_preference,
        $_notification_preference );


function is_member( $_membership_number ) {

// A valid membership number is between 4 and 9 digits long.
// Non-digit characters are ignored.

    $digits = preg_replace('/\D/', '', $_membership_number);

    if ( strlen($digits) > 3 && strlen($digits) < 10 ) {
        return true;
    }
    return false;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/css/styles.css?v=14">
    </head>
    <body>
        <div>
            <a href='/program.html'>
                <img src='/Libraries/Html/data/NSC-SDC_logo.png' alt='Program page' width = '100'>
            </a>
        </div>
        <div>
            <p class = "p_class" >Username: <?php echo $_display_name; ?></p></br>
        </div>
        <div>
            
            <p class = "p_class" ><?php if ( $_flex ) { echo 'You are also registered as a boat owner'; } ?></p></br>

            <p class = "p_class" >Email address: <?php echo $_email; ?></p></br>
            <p class = "p_class" >Mobile number: <?php echo $_mobile; ?></p></br>
            <p class = "p_class" >Membership number: <?php echo $_membership_number; ?></p></br>
            <p class = "p_class" >WhatsApp: <?php echo $_social_preference; ?></p></br>
            <p class = "p_class" >Notifications: <?php echo $_notification_preference; ?></p></br>
            <p class = "p_class" >Skill: <?php echo $_skill_text; ?></p></br>
            <p class = "p_class" >Experience: <?php echo name\unsafe( $_experience); ?></p></br>
        </div>
        <div>
            <button class = "button_class" type="button" onclick="window.location.href='/account_crew_login.php?state=3'">Next</button>
        </div>
    </body>
</html>
