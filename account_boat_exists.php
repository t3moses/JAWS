<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';
require_once 'names.php';

/*

Get $_user_boat_name using the user's posted form data,
Convert it to $_user_boat_key.
Check if $_user_boat_key exists in boats_data.csv.
If it exists, load account_boat_availabiity_form.php and post the boat key.
If it does not exist, load account_boat_data_form.php and post the boat name.

*/

function boat_name_from_post() {

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
$_user_boat_name = boat_name_from_post();
$_user_boat_key = key_from_name( $_user_boat_name );

$_db_boats_lst_asa = lst_asa_from_file( 'boats_data_file' );

$_record_exists = false;

foreach ( $_db_boats_lst_asa as $_db_boat_asa ) {

    if ( $_db_boat_asa [ 'key' ] === $_user_boat_key ) {

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
