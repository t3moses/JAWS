<?php

use nsc\sdc\season as season;

/*

ARRIVE HERE ONLY IF THE CREW MEMBER DOES CURRENTLY HAVE AN ACCOUNT.

Prevent caching of this page

*/

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Authn/src/Authn.php';

function crew_key_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_crew_key = $_GET['ckey'] ?? '';

        // Validate the data
        if (empty($_crew_key)) {
            return null;
        }
        else {
            return $_crew_key;
        }
    }
}

requireLogin();
$_user_crew_key = $_SESSION['entity_key'];
$db = getDatabase();
season\Season::load_season_data();

$stmt = $db->prepare("SELECT display_name, available FROM squad WHERE entity_key = :entity_key");
$stmt->execute([':entity_key' => $_user_crew_key]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$_display_name = $row['display_name'];
$_available = $row['available'] ?? null;

$_available = explode( ";", $_available );
$_event_ids = season\Season::get_future_events();
$_available = array_slice($_available, count($_available) - count($_event_ids));
$_available = array_combine($_event_ids, $_available);

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
        <p class = "p_class" >Crew name: <?php echo $_display_name; ?></p>
        <form method="get" action="/account_crew_availability_update.php">

<!--
Lopp through the list of events, displaying the event value and
offering a choice betweenAvailable and not available.  

-->

            <?php foreach( $_event_ids as $_event_id ) { ?>
                
                <div class='flex-container'>
                    <div class='column'>
                        <p class = "p_class" ><?php echo $_event_id; ?></p>
                    </div>
                    <div class='column'>
                        <p class = "p_class" >I am available</p>
                    </div>
                    <div class='column'>
                        <select class = select_class name=avail id=avail>

                            <option class = "option_class" value = "0" <?php if( $_available[ $_event_id  ] === '0' ) { echo ' selected'; } ?>>No</option>
                            <option class = "option_class" value = "2" <?php if( $_available[ $_event_id  ] !== '0' ) { echo ' selected'; } ?>>Yes</option>

                        </select></br>
                    </div>
                </div>
            <?php } ?>

            <input class = "button_class" type="submit" value="Next"> 
        </form>
    </body>
</html>
