<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

Get $_user_crew_name using the user's posted form data,
Convert it to $_user_crew_key.
Check if $_user_crew_key exists in crews_data.csv.
If it exists, load account_crew_availabiity_form.php.
If it does not exist, load account_crew_data_form.php?$_user_crew_key.
In either case, post $_crew_key?$_user_crew_key.
Use:

The target files can then use $_GET to retrieve the crew key.

*/

require_once 'database.php';
require_once 'names.php';

function crew_name_from_post() {

// Get the first and last names entered by the user and form them into an array.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Retrieve form data
        $_crew_name_arr[ 0 ] = $_POST['fname'] ?? '';
        $_crew_name_arr[ 1 ] = $_POST['lname'] ?? '';

        // Validate the data
        if (empty( $_crew_name_arr[ 0 ]) && empty( $_crew_name_arr[ 1 ])) {
            return null;
        }
        else {
            return $_crew_name_arr;
        }
    }
}

// Convert the first and last names entered by the user into a key.
$_user_name_arr = crew_name_from_post();
$_user_crew_key = key_from_strings( $_user_name_arr );

$_cname = [
    'fname' => $_user_name_arr[ 0 ],
    'lname' => $_user_name_arr[ 1 ]
];

$_query_string = http_build_query( $_cname );

$_db_crews_lst_asa = lst_asa_from_file( 'crews_data_file' );

$_record_exists = false;

foreach ( $_db_crews_lst_asa as $_db_crew_asa ) {

    if ( $_db_crew_asa [ 'key' ] === $_user_crew_key ) {

        $_record_exists = true;
        break;

    }
}

// Pass on the key or name array to the next step.

if ( $_record_exists ) {
    header( "Location: /account_crew_availability_form.php?ckey=" . $_user_crew_key );
    exit;
} else {
    header( "Location: /account_crew_data_form.php?" . $_query_string );
    exit;
}

?>
