<?php

use nsc\sdc\season as season;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Authn/src/Authn.php';

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

requireLogin();
$_crew_key = $_SESSION['entity_key'];
$db = getDatabase();
season\Season::load_season_data();

$_user_str = string_from_get_url();

$_user_str = substr( $_user_str, strlen( "avail=" ));
$_available = explode( "&avail=", $_user_str );
$_available_str = implode( ';', $_available );

$stmt = $db->prepare("SELECT display_name FROM squad WHERE entity_key = :entity_key");
$stmt->execute([':entity_key' => $_crew_key]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$_display_name = $row['display_name'];

$stmt = $db->prepare("UPDATE squad SET available = :available WHERE entity_key = :entity_key");
$stmt->execute([
    ':available' => $_available_str,
    ':entity_key' => $_crew_key
]);

$_event_ids = season\Season::get_future_events();
$_available = array_slice($_available, count($_available) - count($_event_ids));
$_available = array_combine($_event_ids, $_available);

/*
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

calendar\crew( $_crew );
*/


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
            <p class = "p_class" ><?php echo $_display_name; ?>'s availability has been updated</p>
        </div>
<!--

Loop through the list of events, displaying the event value.

-->
        <?php foreach( $_event_ids as $_event_id ) { ?>
 
            <div class='flex-container'>
                <div class='column'><p class = "p_class" > <?php echo $_event_id; ?></p></div>
                <div class='column'><p class = "p_class" > <?php if( $_available[ $_event_id ] === '0' ) {
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

<!--
        <script>

            const registrationDownloadBtn = document.getElementById('registrationDownloadBtn');

            registrationDownloadBtn.addEventListener('click', async () => {

                const register_response = await fetch('/Libraries/Calendar/data/register.ics');
                const register_blob = await register_response.blob();

                const register_url = URL.createObjectURL(register_blob);
                const register_a = document.createElement('a');
                register_a.href = register_url;
                register_a.download = 'nsc-sdc-register.ics';
                register_a.click();
                URL.revokeObjectURL(register_url);

            });

            const cancellationDownloadBtn = document.getElementById('cancellationDownloadBtn');

            cancellationDownloadBtn.addEventListener('click', async () => {

                const cancel_response = await fetch('/Libraries/Calendar/data/cancel.ics');
                const cancel_blob = await cancel_response.blob();

                const cancel_url = URL.createObjectURL(cancel_blob);
                const cancel_a = document.createElement('a');
                cancel_a.href = cancel_url;
                cancel_a.download = 'nsc-sdc-cancel.ics';
                cancel_a.click();
                URL.revokeObjectURL(cancel_url);

            });
/*
                const update_response = await fetch('/Libraries/Calendar/data/update.ics');
                const update_blob = await update_response.blob();

                const update_url = URL.createObjectURL(update_blob);
                const update_a = document.createElement('a');
                update_a.href = update_url;
                update_a.download = 'nsc-sdc-update.ics';
                update_a.click();
                URL.revokeObjectURL(update_url);
*/

        </script>
-->
    </body>
</html>
