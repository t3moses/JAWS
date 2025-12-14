<?php

use nsc\sdc\fleet as fleet;
use nsc\sdc\season as season;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
require_once __DIR__ . '/Libraries/Season/src/Season.php';
/*

The get url query string contains the boat key and a list of the boat's available spaces; one number for each event.
These were captured from the user by account_boat_availability_form.php.

This must be formed into an array and then a comma-separated string.

The boats_availability_file contains an entry for the boat identified in the query string.
This entry has to be replaced by the one formed from the query string.
THen the result has to be written back to boats_availability_file.

*/

function string_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve get data
        $_user_str = $_SERVER['QUERY_STRING'];

        // Validate the data
        if (empty( $_user_str )) {
            return null;
        }
        else {
            return $_user_str;
        }
    }
}

$_fleet = new fleet\Fleet();
$_season = new season\Season();

$_user_str = string_from_get_url();

// Convert the query array back into a comma-separated string

if ( str_starts_with( $_user_str, "key=" )) {
    $_user_str = substr( $_user_str, strlen( "key=" ));
}
$_user_arr = explode( "&avail=", $_user_str );
$_user_boat_key = array_shift( $_user_arr );
$_event_ids = $_season->get_future_events();
$_boat = $_fleet->get_boat( $_user_boat_key );
$_berths = array_combine( $_event_ids, $_user_arr );
foreach ( $_event_ids as $_event_id ){
    $_boat->set_berths( $_event_id, $_berths[ $_event_id ] );
}
$_fleet->set_boat( $_boat );
$_fleet->save();

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
        <div>
            <p class = "p_class" ><?php echo $_boat->get_display_name(); ?>'s availability has been updated</p>
        </div>
<!--

Loop through the list of events, displaying the event value.

-->
            <?php foreach( $_event_ids as $_event_id ) { ?>
 
            <div class='flex-container'>
                <div class='column'><p class = "p_class" > <?php echo $_event_id; ?></p></div>
                <div class='column'><p class = "p_class" > <?php echo $_boat->get_berths( $_event_id ); ?></p></div>
                </div>
            </div>
        <?php } ?>
        <div>
            <button type = "button" class = "button_class" onclick = "window.location.href='/season_update.php'">Next</button>
        </div>
    </body>
</html>
