<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';
require_once 'names.php';
require_once 'arrays.php';

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

// Get the query string from the get URL.
$_user_availability_str = string_from_get_url();

// Trim the prefix from the query string.
if ( str_starts_with( $_user_availability_str, "key=" )) {
    $_user_availability_str = substr( $_user_availability_str, strlen( "key=" ));
}

// Convert the result into an indexed array.  The first element is the crew key.
// The remaining elements are the user's availabilities for each event.
// Convert the result into a comma-separated string.
$_user_availability_arr = explode( "&avail=", $_user_availability_str );
replace_csv_row( $_user_availability_arr, 'crews_availability_file' );
$_user_availability_str = implode( ",", $_user_availability_arr );
$_user_crew_key = array_shift( $_user_availability_arr );

// Now get the display name associated with the crew key for display at the top of the page.
$_db_crews_lst_asa = lst_asa_from_file( 'crews_data_file' );
$_first_name = subject_attribute_from_file( $_user_crew_key, 'first_name', $_db_crews_lst_asa );
$_last_name = subject_attribute_from_file( $_user_crew_key, 'last_name', $_db_crews_lst_asa );
$_display_name = display_name_from_names( $_first_name, $_last_name );

// Get the event dates and the number of events from the database.
$_event_ids = event_ids();
$_number_of_events = number_of_events();

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <div>
            <p class = "p_class" ><?php echo $_display_name; ?>'s availability has been updated</p>
        </div>
<!--

Loop through the list of events, displaying the event value.

-->
        <?php for ( $_index = 0; $_index < $_number_of_events; $_index++ ) { ?>
            <div class='flex-container'>
                <div class='column'><p class = "p_class" > <?php echo $_event_ids[ $_index ]; ?></p></div>
                <div class='column'><p class = "p_class" > <?php echo $_user_availability_arr[ $_index ]; ?></p></div>
                </div>
            </div>
        <?php } ?>
        <div>
            <button type = "button" class = "button_class" onclick = "window.location.href='/program.html'">Done</button>
        </div>
    </body>
</html>
