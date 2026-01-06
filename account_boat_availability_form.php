<?php

use nsc\sdc\fleet as fleet;
use nsc\sdc\season as season;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
/*

ARRIVE HERE ONLY IF THE BOAT DOES CURRENTLY HAVE AN ACCOUNT.

*/

function boat_key_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_boat_key = $_GET['bkey'] ?? '';

        // Validate the data
        if (empty($_boat_key)) {
            return null;
        }
        else {
            return $_boat_key;
        }
    }
}

$_fleet = new fleet\Fleet();

// Get the boat key provided through the get url query string.
// Get the boat name corresponding to the boat key for display at the top of the html form.

$_user_boat_key = boat_key_from_get_url();
$_boat = $_fleet->get_boat( $_user_boat_key );
$_display_name = $_boat->get_display_name();
$_berths = $_boat->get_all_berths();
season\Season::load_season_data();
$_event_ids = season\Season::get_future_events();

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
        <p class = "p_class" >Boat name: <?php echo $_display_name; ?></p>
        <form method="get" action="/account_boat_availability_update.php">
            <input class = "hidden_class" type="text" id="key" name="key" value="<?php echo $_user_boat_key; ?>"required>

<!--

Lopp through the list of events, displaying the event value and
offering a choice of the number of availablr spaces.

-->

            <?php foreach( $_event_ids as $_event_id ) { ?>
                
                <div class='flex-container'>
                    <div class='column'>
                        <p class = "p_class" ><?php echo $_event_id; ?></p>
                    </div>
                    <div class='column'>
                        <select class = select_class name=avail id=avail>

                            <option class = "option_class" value = '0' <?php if($_berths[ $_event_id ] === '0' ) { echo ' selected'; } ?>>0</option>
                            <option class = "option_class" value = '1' <?php if($_berths[ $_event_id ] === '1' ) { echo ' selected'; } ?>>1</option>
                            <option class = "option_class" value = '2' <?php if($_berths[ $_event_id ] === '2' ) { echo ' selected'; } ?>>2</option>
                            <option class = "option_class" value = '3' <?php if($_berths[ $_event_id ] === '3' ) { echo ' selected'; } ?>>3</option>
                            <option class = "option_class" value = '4' <?php if($_berths[ $_event_id ] === '4' ) { echo ' selected'; } ?>>4</option>
                            <option class = "option_class" value = '5' <?php if($_berths[ $_event_id ] === '5' ) { echo ' selected'; } ?>>5</option>
                            <option class = "option_class" value = '6' <?php if($_berths[ $_event_id ] === '6' ) { echo ' selected'; } ?>>6</option>

                        </select></br>
                    </div>
                </div>
            <?php } ?>

            <input class = "button_class" type="submit" value="Next">
        </form>
    </body>
</html>
