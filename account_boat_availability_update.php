<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';
require_once 'names.php';

/*

The query string contains the boat key and a list of the boat's available spaces; one number for each event.
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

// Get the query string from the get URL.
$_user_str = string_from_get_url();

// Trim the prefix from the query string.
if ( str_starts_with( $_user_str, "key=" )) {
    $_user_str = substr( $_user_str, strlen( "key=" ));
}

// Convert the result into an indexed array.  The first element is the boat key.
// The remaining elements are the boat's availability numbers; one for each event.
$_user_arr = explode( "&avail=", $_user_str );

// Now convert the query array back into a comma-separated string.
// And isolate the boat key.
$_user_str = implode( ",", $_user_arr );

$_user_boat_key = array_shift( $_user_arr );

// Now read the boats availability file from the database as a string.
$_db_boats_availability_str = str_from_file( 'boats_availability_file' );

// And explode it into a list of strings; one string for each boat.
$_db_boats_availability_lst_str = explode( "\n", $_db_boats_availability_str );

// Get the event ids and the number of events from the database.
$_event_ids = event_ids();
$_number_of_events = number_of_events();

// Now build the updated file.
$_boats_availability_updated_str = '';

// Copy the original file to the updated file,
// replacing the entry for the boat with the user-provided values.

$_number_of_boats = count( $_db_boats_availability_lst_str );

for ( $_index = 0; $_index < $_number_of_boats; $_index++ ) {

    if ( $_index !== 0 ){
        $_boats_availability_updated_str .= chr(0x0a); // new line except at the very beginning.
    }

    $_db_boat_availability_str = $_db_boats_availability_lst_str[ $_index ];
    $_db_boat_availability_arr = explode( ',', $_db_boat_availability_str );

    if ( $_db_boat_availability_arr[ 0 ] === $_user_boat_key ) {
        $_boats_availability_updated_str .= $_user_str;
    }
    else {
        $_boats_availability_updated_str .= $_db_boat_availability_str;
    }
}

// Rewrite the boats_availability.csv file.
file_from_str( 'boats_availability_file', $_boats_availability_updated_str );

// Get the boat name associated with the boat key to display at the top of the page.
$_db_boats_data_lst_asa = lst_asa_from_file( 'boats_data_file' );
$_boat_name = subject_attribute_from_file( $_user_boat_key, "display_name", $_db_boats_data_lst_asa );


?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <div>
            <p class = "p_class" ><?php echo $_boat_name; ?>'s availability has been updated</p>
        </div>
<!--

Loop through the list of events, displaying the event value.

-->
        <?php for ( $_index = 0; $_index < $_number_of_events; $_index++ ) { ?>
            <div class='flex-container'>
                <div class='column'><p class = "p_class" > <?php echo $_event_ids[ $_index ]; ?></p></div>
                <div class='column'><p class = "p_class" > <?php echo $_user_arr[ $_index ]; ?></p></div>
                </div>
            </div>
        <?php } ?>
        <div>
            <button type = "button" class = "button_class" onclick = "window.location.href='/program.html'">Done</button>
        </div>
    </body>
</html>
