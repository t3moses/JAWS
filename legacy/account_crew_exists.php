<?php

use nsc\sdc\name as name;
use nsc\sdc\squad as squad;

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

require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Name/src/Name.php';

function crew_name_from_post() {

// Get the first and last names entered by the user and form them into an array.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Retrieve form data
        $_cname[ 'fname' ] = $_POST['fname'] ?? '';
        $_cname[ 'lname' ] = $_POST['lname'] ?? '';

        // Validate the data
        if (empty( $_cname[ 'fname' ]) && empty( $_cname[ 'lname' ])) {
            return null;
        }
        else {
            return $_cname;
        }
    }
}

$_squad = new squad\Squad;

// Convert the first and last names entered by the user into a key.
$_user_name_arr = crew_name_from_post();
$_user_crew_key = name\key_from_strings( $_user_name_arr[ 'fname' ], $_user_name_arr[ 'lname' ] );

$_query_string = http_build_query( $_user_name_arr );

// Pass on the key or name to the next step.

if ( $_squad->contains( $_user_crew_key ) ) {
    header( "Location: /account_crew_availability_form.php?ckey=" . $_user_crew_key );
    exit;
} else {
    header( "Location: /account_crew_data_form.php?" . $_query_string );
    exit;
}

?>
