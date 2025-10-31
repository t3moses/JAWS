<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

Get $_user_boat_name using the user's posted form data,
Convert it to $_user_boat_key.
Check if $_boat_key exists in boat_data.csv.
If it exists, load account_boat_availabiity_form.php.
If it does not exist, load account_boat_data_form.php?$_boat_key.
In either case, post $_boat_key?$_boat_key.
Use:

The target files can then use $_GET to retrieve the boat key.

*/

function key_from_name( $_name ) {
    // Create a sanitized database key from a name.
    return trim( strtolower( htmlspecialchars( $_name )));
}

function boat_name_from_form() {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Retrieve form data
        $_boat_name = $_POST['bname'] ?? '';

        // Validate the data
        if (empty($_boat_name)) {
            return null;
        }
        else {
            return $_boat_name;
        }
    }
}

// Convert the boat name supplied by the user into a key.
$_user_boat_name = boat_name_from_form();
$_user_boat_key = key_from_name( $_user_boat_name );

// Read the boat data file into an array of boat strings.
$_db_boats_str = file_get_contents('boat_data.csv');
$_db_boats_arr = explode( "\n", $_db_boats_str );

$_header_row = $_db_boats_arr[0];

$_record_exists = false;

foreach ( $_db_boats_arr as $_db_boat_str ) {

    $_db_boat_arr = explode( ',', $_db_boat_str );

    if ( $_db_boat_arr[ 0 ] === $_user_boat_key ) {

        $_record_exists = true;
        break;

    }
}

if ( $_record_exists ) {
    header("Location: /account_boat_availability_form.php?bkey=" . $_user_boat_key);
    exit;
} else {
    header("Location: /account_boat_data_form.php?bname=" . $_user_boat_name);
    exit;
}

?>
