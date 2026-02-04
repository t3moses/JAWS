<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

use nsc\sdc\name as name;
use nsc\sdc\mail as mail;
use nsc\sdc\season as season;
use nsc\sdc\config\rank as rank;
use nsc\sdc\fleet as fleet;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Config/src/Rank.php';
require_once __DIR__ . '/Libraries/Mail/src/Mail.php';
require_once __DIR__ . '/Libraries/Database/src/Database.php';
require_once __DIR__ . '/Libraries/Authn/src/Authn.php';

// Get the boat data from the submitted form.

$_boat_name = name\safe($_POST['boat_name']) ?? '';
$_boat_key = name\key_from_string( $_boat_name ) ?? '';
$_owner_first_name = name\safe($_POST['owner_first_name']) ?? '';
$_owner_last_name = name\safe($_POST['owner_last_name']) ?? '';
$_owner_key = name\key_from_strings( $_owner_first_name, $_owner_last_name ) ?? '';
$_password = $_POST['password'] ?? '';
$_owner_email = name\safe($_POST['owner_email']) ?? '';
$_owner_mobile = name\safe($_POST['owner_mobile']) ?? '';
$_min_berths = name\safe($_POST['min_berths']) ?? '';
$_max_berths = name\safe($_POST['max_berths']) ?? '';
$_assistance_required = name\safe($_POST['assistance_required']) ?? '';
$_social_preference = name\safe($_POST['social_preference']) ?? '';

// Check if the boat is already registeres.
// If it is, redirect to the boat login page with state=2 (boat exists).

$db = getDatabase();
$stmt = $db->prepare("SELECT COUNT(*) FROM fleet WHERE entity_key = :entity_key");
$stmt->execute([':entity_key' => $_boat_key]);
$exists = $stmt->fetchColumn() > 0;
if ( $exists ) {
    header('Location: /account_boat_login.php?state=2');
    exit();
}

// Save the boat password.

registerUser($_boat_key, $_password, 'boat');

// Calculate and save all the boat data.

season\Season::load_season_data();
$_number_of_events = season\Season::get_event_count();
$_event_ids = season\Season::get_event_ids();

$_occupied_berths = '';

// Set inital values for available berths and participation history.

$_berths = [];
$_history = [];
foreach( $_event_ids as $_event_id ) {
    $_berths[] = $_max_berths;
    $_history[] = '';
}
$_berths = implode( ';', $_berths );
$_history = implode( ';', $_history );

// Set the initial value for boat rank.
// Absence value is zero.
// Flexibility value is FLEXIBLE if the owner is also registered as crew,

$_crew_key = fleet\Fleet::crew_as_boat_owner( $_owner_key );
$_rank = [];
if( $_crew_key !== false ) {
    $_flex = true;
    $_rank[ rank\Rank::BOAT_RANK_FLEXIBILITY_DIMENSION ] = rank\Rank::FLEXIBLE;
    update_rank( 'squad', $_crew_key, rank\Rank::CREW_RANK_FLEXIBILITY_DIMENSION, rank\Rank::FLEXIBLE );
}
else {
    $_flex = false;
    $_rank[ rank\Rank::BOAT_RANK_FLEXIBILITY_DIMENSION ] = rank\Rank::INFLEXIBLE;
}

$_rank[ rank\Rank::BOAT_RANK_ABSENCE_DIMENSION ] = 0;
$_rank = implode( ';', $_rank );

fleet\Fleet::registerBoat($_boat_key, $_boat_name, $_owner_key,
    $_owner_email, $_owner_mobile, $_min_berths, $_max_berths,
    $_assistance_required, $_social_preference, $_rank,
    $_occupied_berths, $_berths, $_history);

// Add the boat to all registered crew whitelists, separated by ';'.

$stmt = $db->prepare("UPDATE squad SET whitelist = whitelist || ';' || :boat_key WHERE whitelist IS NOT NULL AND is_active = 1");
$stmt->execute([':boat_key' => $_boat_key]);
$stmt = $db->prepare("UPDATE squad SET whitelist = whitelist || :boat_key WHERE whitelist IS NULL AND is_active = 1");
$stmt->execute([':boat_key' => $_boat_key]);

// Send a confirmation email to the admin.

mail\Mail::send_new_boat_email( $_boat_name, $_owner_email, $_owner_mobile, $_social_preference );

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
            <p class = "p_class" ><?php echo $_boat_name ?>'s account has been created</p>
        </div>
        <div>

            <p class = "p_class" ><?php if ( $_flex ) { echo 'You are also registered as a crew member'; } ?></p></br>

            <p class = "p_class" >Boat name: <?php echo $_boat_name?></p></br>
            <p class = "p_class" >Owner first name: <?php echo $_owner_first_name ?></p></br>
            <p class = "p_class" >Owner last name: <?php echo $_owner_last_name ?></p></br>
            <p class = "p_class" >Email address: <?php echo $_owner_email ?></p></br>
            <p class = "p_class" >Mobile number: <?php echo $_owner_mobile ?></p></br>
            <p class = "p_class" >Min berths: <?php echo $_min_berths ?></p></br>
            <p class = "p_class" >Max berths: <?php echo $_max_berths ?></p></br>
            <p class = "p_class" >Assistance: <?php echo $_assistance_required ?></p></br>
            <p class = "p_class" >WhatsApp: <?php echo $_social_preference ?></p>
        </div>
        <div>
            <button class="button_class" type="button" onclick="window.location.href='/account_boat_login.php?state=3'">Next</button>
        </div>
    </body>
</html>
