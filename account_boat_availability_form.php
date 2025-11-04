<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'names.php';

/*

Get $_boat_name using the user's posted form data,
Convert it to $_boat_key.
Check if $_boat_key exists in boats_data.csv.
If it exists, load account_boat_availabiity_form.php.
If it does not exist, load account_boat_data_form.php.
In either case, post $_boat_key.
Use:

The target files can then use $_GET to retrieve the boat key.

*/


function boat_key_from_form() {

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

// Get the boat key from the redirect and look up its name in boats_data.csv.
$_user_boat_key = boat_key_from_form();
$_db_boat_data = file_get_contents('boats_data.csv');
$_display_name = subject_attribute_from_file( $_user_boat_key, "display name", $_db_boat_data );

// Read the boat availability file into an array of boat strings.
$_db_boats_availability_str = file_get_contents('boats_availability.csv');
$_db_boats_availability_arr_str = explode( "\n", $_db_boats_availability_str );

$_header_arr = explode( ",", $_db_boats_availability_arr_str[ 0 ] );

foreach ( $_db_boats_availability_arr_str as $_db_boat_availability_str ) {

    $_db_boat_availability_arr = explode( ',', $_db_boat_availability_str );

    if ( $_db_boat_availability_arr[ 0 ] === $_user_boat_key) {

        break; // with $_db_boat_availability_arr containing the target boat availability.

    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <p class = "p_class" >Boat name: <?php echo $_display_name; ?></p>
        <form method="get" action="account_boat_availability_update.php">
            <input class = "hidden_class" type="text" id="key" name="key" value="<?php echo $_user_boat_key; ?>"required>


<!--

Lopp through the list of events, displaying the event value and
offering a choice betweenAvailable and not available.  

-->

            <?php for ( $_index = 1; $_index < count( $_db_boat_availability_arr ); $_index++ ) { ?>
                
                <div class='flex-container'>
                    <div class='column'>
                        <p class = "p_class" ><?php echo $_header_arr[ $_index ]; ?></p>
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
