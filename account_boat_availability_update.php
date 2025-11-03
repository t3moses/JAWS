<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

The query string consists of the boat key and a list of the boat's available spaces; one number for each event.
These were captured from the user by account_boat_availability_form.php.

This must be formed into an array and then a comma-separated string.

The file boats_availability.csv contains an entry for the boat identified in the query string.
This entry has to be relaced by the one formed from the query string.
THen the result has to be written back to boats_availability.csv file.

*/

function string_from_get() {

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
$_user_str = string_from_get();

// Trim the prefix from the query string.

if ( str_starts_with( $_user_str, "key=" )) {
    $_user_str = substr( $_user_str, strlen( "key=" ));
}

// Convert the result into an array.  The first element is the boat key.
// The remaining elements are the boat's availability numbers; one for each event.
$_user_arr = explode( "&avail=", $_user_str );
$_user_boat_key = $_user_arr[ 0 ];

// Now convert the query array back into a comma-separated string.
$_user_str = implode( ",", $_user_arr );

// Now read the boats availability file as a string.
$_db_boats_availability_str = file_get_contents('boats_availability.csv');

// And explode it into an array of strings; one string for each boat.
$_db_boats_availability_arr_str = explode( "\n", $_db_boats_availability_str );

// Get the event dates from the first row of the boats_availability.csv file.
$_header_arr = explode(",", $_db_boats_availability_arr_str[ 0 ] );
$_number_of_events = count( $_header_arr );

// Now build the updated file.
$_boats_availability_updated_str = '';

// Copy the original file to the updated file,
// replacing the entry for the boat with the user-provided values.

$_number_of_rows = count( $_db_boats_availability_arr_str );

for ( $_index = 0; $_index < $_number_of_rows; $_index++ ) {

    if ( $_index !== 0 ){
        $_boats_availability_updated_str .= chr(0x0a);
    }

    $_db_boat_availability_str = $_db_boats_availability_arr_str[ $_index ];
    $_db_boat_availability_arr = explode( ',', $_db_boat_availability_str );

    if ( $_db_boat_availability_arr[ 0 ] === $_user_boat_key ) {
        $_boats_availability_updated_str .= $_user_str;
    }
    else {
        $_boats_availability_updated_str .= $_db_boat_availability_str;
    }
}

// Finally, rewrite the boats_availability.csv file.
file_put_contents( 'boats_availability.csv', $_boats_availability_updated_str );

?>

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <body>
        <div>
            <p class = "p_class" >Your account has been updated</p>
        </div>
<!--

Loop through the list of events, displaying the event value.

-->
        <?php for ( $_index = 1; $_index < $_number_of_events; $_index++ ) { ?>
            <div class='flex-container'>
                <div class='column'><p class = "p_class" > <?php echo $_header_arr[ $_index ]; ?></p></div>
                <div class='column'><p class = "p_class" > <?php echo $_user_arr[ $_index ]; ?></p></div>
                </div>
            </div>
        <?php } ?>
        <div>
            <button type = "button" class = "button_class" onclick = "window.location.href='/program.html'">Done</button>
        </div>
    </body>
</html>
