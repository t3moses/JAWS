<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

ARRIVE HERE ONLY IF THE BOAT OWNER DOES NOT HAVE AN ACCOUNT.

*/

function key_from_name( $_name ) {
    // Create a sanitized database key from a name.
    return trim( strtolower( htmlspecialchars( $_name )));
}

function index_from_array( $_array, $_element ) {
    // Return the index of the element in the array, or -1 if not found.
    foreach ( $_array as $_index => $_item ) {
        if ( $_item === $_element ) {
            return $_index;
        }
    }
    return -1;
}

function index_from_row( $_row, $_element ) {
    // Return the index of the element in the row.
    $_array = str_getcsv( $_row );
    $_index = index_from_array( $_array, $_element );
    return $_index;
}

function boat_name_from_form() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_boat_name = $_GET['bname'] ?? '';

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

// Read the boat data file into an array of boat records.
$_db_boats = file_get_contents('boats_data.csv');
$_db_boats = explode( "\n", $_db_boats );

$_header_row = $_db_boats[0];

$_record_exists = false;

foreach ( $_db_boats as $_db_boat ) {

    $_db_boat = explode( ',', $_db_boat );

    if ( $_db_boat[ 0 ] === $_user_boat_key ) {

        $_boat_key = $_user_boat_key;
        $_owner_first_name = $_db_boat[ index_from_row( $_header_row, 'owner first name' )];
        $_owner_last_name = $_db_boat[ index_from_row( $_header_row, 'owner last name' )];
        $_display_name = $_db_boat[ index_from_row( $_header_row, 'display name' )];
        $_email_address = $_db_boat[ index_from_row( $_header_row, 'email address' )];
        $_mobile_number = $_db_boat[ index_from_row( $_header_row, 'mobile' )];
        $_min_occupancy = $_db_boat[ index_from_row( $_header_row, 'min occupancy' )];
        $_max_occupancy = $_db_boat[ index_from_row( $_header_row, 'max occupancy' )];
        $_assistance = $_db_boat[ index_from_row( $_header_row, 'assistance' )];

        $_record_exists = true;
        break;

    }
}

if ( !$_record_exists ) {
    // If no record exists for this boat, set default values.
    $_display_name = $_user_boat_name;
    $_owner_first_name = '';
    $_owner_last_name = '';
    $_email_address = '';
    $_mobile_number = '';
    $_min_occupancy = '';
    $_max_occupancy = '';
    $_assistance = '';
}

?>

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <body>
        <p class = "p_class" >Boat name: <?php echo $_display_name?></p>
        <form method="post" action="account_boat_data_update.php">

            <input class = "hidden_class" type="text" id="boat_key" name="boat_key" value="<?php echo $_user_boat_key?>"required>

            <input class = "hidden_class" type="text" id="display_name" name="display_name" value="<?php echo $_display_name?>"required>

            <label class = "label_class" for="owner_first_name">Owner's first name:</label>
            <input class = "text_class" type="text" id="owner_first_name" name="owner_first_name" value="<?php echo $_owner_first_name?>"required></br>

            <label class = "label_class" for="owner_last_name">Owner's last name:</label>
            <input class = "text_class" type="text" id="owner_last_name" name="owner_last_name" value="<?php echo $_owner_last_name?>"required></br>

            <label class = "label_class" for="email_address">Email address:</label>
            <input class = "text_class" type="email" id="email_address" name="email_address" value="<?php echo $_email_address?>"required></br>

            <label class = "label_class" for="mobile_number">Mobile number:</label>
            <input class = "text_class" type="tel" id="mobile_number" name="mobile_number" value="<?php echo $_mobile_number?>"></br>

            <label class = "label_class" for="min_occupancy">Min occupancy:</label>
            <select class = "select_class" name="min_occupancy" id="max_occupancy">
                <option value="1" <?php if($_min_occupancy == '1') echo 'selected'; ?>>1</option>
                <option value="2" <?php if($_min_occupancy == '2') echo 'selected'; ?>>2</option>
                <option value="3" <?php if($_min_occupancy == '3') echo 'selected'; ?>>3</option>
            </select></br>

            <label class = "label_class" for="max_occupancy">Max occupancy:</label>
            <select class = "select_class" name="max_occupancy" id="max_occupancy">
                <option value="1" <?php if($_max_occupancy == '1') echo 'selected'; ?>>1</option>
                <option value="2" <?php if($_max_occupancy == '2') echo 'selected'; ?>>2</option>
                <option value="3" <?php if($_max_occupancy == '3') echo 'selected'; ?>>3</option>
                <option value="4" <?php if($_max_occupancy == '4') echo 'selected'; ?>>4</option>
                <option value="5" <?php if($_max_occupancy == '5') echo 'selected'; ?>>5</option>
                <option value="6" <?php if($_max_occupancy == '6') echo 'selected'; ?>>6</option>
            </select></br>

            <label class = "label_class" for="assistance">Request assistance:</label>
            <select class = "select_class" name="assistance" id="assistance">
                <option value="Yes" <?php if($_assistance == 'Yes') echo 'selected'; ?>>Yes</option>
                <option value="No" <?php if($_assistance == 'No') echo 'selected'; ?>>No</option>
            </select></br>

            <input class = "button_class" type="submit" value="Next">
        </form>
    </body>
</html>
