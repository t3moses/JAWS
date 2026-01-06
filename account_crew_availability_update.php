<?php

use nsc\sdc\squad as squad;
use nsc\sdc\season as season;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Season/src/Season.php';

/*

The get query string consists of the crew key and a list of the crew's available codes; one code for each event.

This must be formed into an array and then a comma-separated string.

The file crews_availability.csv contains an entry for the crew member identified in the query string.
This entry has to be replaced by the one formed from the query string.
Then the result has to be written back to crews_availability.csv file.

*/


function string_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve get data
        $_user_availability_str = $_SERVER['QUERY_STRING'];

        // Validate the data
        if (empty( $_user_availability_str )) {
            return null;
        }
        else {
            return $_user_availability_str;
        }
    }
}


$_user_str = string_from_get_url();

if ( str_starts_with( $_user_str, "key=" )) {
    $_user_str = substr( $_user_str, strlen( "key=" ));
}
$_user_arr = explode( "&avail=", $_user_str );
$_user_crew_key = array_shift( $_user_arr );

$_squad = new squad\Squad();

season\Season::load_season_data();

$_event_ids = season\Season::get_future_events();
$_crew = $_squad->get_crew( $_user_crew_key );

for( $i = 0; $i < count( $_user_arr ); $i++ ) {
    $_crew->set_available( $_event_ids[ $i ], $_user_arr[ $i ] );
}

$_squad->set_crew( $_crew );
$_squad->save();


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
            <p class = "p_class" ><?php echo $_crew->get_display_name(); ?>'s availability has been updated</p>
        </div>
<!--

Loop through the list of events, displaying the event value.

-->
        <?php for ( $i = 0; $i < count($_event_ids); $i++ ) { ?>
            <div class='flex-container'>
                <div class='column'><p class = "p_class" > <?php echo $_event_ids[ $i ]; ?></p></div>
                <div class='column'><p class = "p_class" > <?php if( $_crew->get_available( $_event_ids[ $i ]) === '0' ) {
                echo 'I am not available'; }
                else {
                echo 'I am available'; }
                ?></p></div>
                </div>
            </div>
        <?php } ?>
        <div>
            <button type = "button" class = "button_class" onclick = "window.location.href='/season_update.php'">Next</button>
        </div>
    </body>
</html>
