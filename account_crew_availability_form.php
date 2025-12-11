<?php

use nsc\sdc\squad as squad;
use nsc\sdc\season as season;

// Prevent caching of this page

/*

ARRIVE HERE ONLY IF THE CREW MEMBER DOES CURRENTLY HAVE AN ACCOUNT.

*/

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';

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

$_user_crew_key = crew_key_from_get_url();

$_squad = new squad\Squad();
$_crew = $_squad->get_crew( $_user_crew_key );

$_display_name = $_crew->get_display_name();

$_available = $_crew->get_all_available();

$_season = new season\Season();
$_event_ids = $_season->get_future_events();

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <div>
            <a href='/../../../program.html'>
            <img src='/./Libraries/Html/data/NSC-SDC_logo.png' alt='Program page' width = '100'>
        </a>
        </div>
        <p class = "p_class" >Username: <?php echo $_crew->get_display_name(); ?></p>
        <form method="get" action="account_crew_availability_update.php">
            <input class = "hidden_class" type="text" id="key" name="key" value="<?php echo $_user_crew_key; ?>"required>


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

                            <option value = "N" <?php if( $_available[ $_event_id  ] === 'N' ) { echo ' selected'; } ?>>No</option>
                            <option value = "Y" <?php if( $_available[ $_event_id  ] !== 'N' ) { echo ' selected'; } ?>>Yes</option>

                        </select></br>
                    </div>
                </div>
            <?php } ?>

            <input class = "button_class" type="submit" value="Next"> 
        </form>
    </body>
</html>
