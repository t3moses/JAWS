<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

Get $_user_boat_name using the user's posted form data.
Check if $_user_boat_key exists in the fleet.
If it exists, load account_boat_availabiity_form.php.
If it does not exist, load <account_boat_data_form class="ph".

*/

require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
require_once __DIR__ . '/Libraries/Name/src/Name.php';


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

$_user_boat_name = boat_name_from_post();
$_user_boat_key = key_from_string( $_user_boat_name );

$_fleet = new Fleet();

if ( $_fleet->contains( $_user_boat_key )) {
    header("Location: /account_boat_availability_form.php?bkey=" . $_user_boat_key);
    exit;
} else {
    header("Location: /account_boat_data_form.php?bname=" . $_user_boat_name);
    exit;
}

?>
