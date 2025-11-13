<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';
require_once 'names.php';

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

// Get the boat key provided through the get url query string.
// Get the boat name corresponding to the boat key for display at the top of the html form.
$_user_boat_key = boat_key_from_get_url();
$_db_boats_data_lst_asa = lst_asa_from_file( 'boats_data_file' );

$_boat_name = subject_attribute_from_file( $_user_boat_key, 'display_name', $_db_boats_data_lst_asa );

// Get the database boats availability as a list of strings.
$_db_boats_availability_str = str_from_file( 'boats_availability_file' );
$_db_boats_availability_lst_str = explode( "\n", $_db_boats_availability_str );

// Find the list entry that corresponds to $_user_boat_key.
// Isolate the key.
$_record_exists = false;
foreach ( $_db_boats_availability_lst_str as $_db_boat_availability_str ) {
    $_db_boat_availability_arr = explode( ',', $_db_boat_availability_str );
    if ( $_db_boat_availability_arr[ 0 ] === $_user_boat_key ) {
        $_record_exists = true;
        $_db_boat_key = array_shift( $_db_boat_availability_arr );
        break; // with $_db_boat_availability_arr containing the target boat availability array.
    }
}
if ( $_record_exists === false ) { die( 'Unable to find boat record' ); }

// Get the event ids and the number of events from the database.
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
        <p class = "p_class" >Boat name: <?php echo $_boat_name; ?></p>
        <form method="get" action="account_boat_availability_update.php">
            <input class = "hidden_class" type="text" id="key" name="key" value="<?php echo $_user_boat_key; ?>"required>

<!--

Lopp through the list of events, displaying the event value and
offering a choice of the number of availablr spaces.

-->

            <?php for ( $_index = 0; $_index < $_number_of_events ; $_index++ ) { ?>
                
                <div class='flex-container'>
                    <div class='column'>
                        <p class = "p_class" ><?php echo $_event_ids[ $_index ]; ?></p>
                    </div>
                    <div class='column'>
                        <select class = select_class name=avail id=avail>

                            <option value = 0 <?php if($_db_boat_availability_arr[ $_index ] === '0' ) { echo ' selected'; } ?>>0</option>
                            <option value = 1 <?php if($_db_boat_availability_arr[ $_index ] === '1' ) { echo ' selected'; } ?>>1</option>
                            <option value = 2 <?php if($_db_boat_availability_arr[ $_index ] === '2' ) { echo ' selected'; } ?>>2</option>
                            <option value = 3 <?php if($_db_boat_availability_arr[ $_index ] === '3' ) { echo ' selected'; } ?>>3</option>
                            <option value = 4 <?php if($_db_boat_availability_arr[ $_index ] === '4' ) { echo ' selected'; } ?>>4</option>
                            <option value = 5 <?php if($_db_boat_availability_arr[ $_index ] === '5' ) { echo ' selected'; } ?>>5</option>
                            <option value = 6 <?php if($_db_boat_availability_arr[ $_index ] === '6' ) { echo ' selected'; } ?>>6</option>

                        </select></br>
                    </div>
                </div>
            <?php } ?>

            <input class = "button_class" type="submit" value="Next">
        </form>
    </body>
</html>
