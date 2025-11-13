
<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';
require_once 'arrays.php';
require_once 'names.php';

/*

ARRIVE HERE ONLY IF THE BOAT DOES NOT CURRENTLY HAVE AN ACCOUNT.

The get url query string contains the boat name provided by the user.
This is converted to a key.
An empty form is displayed and the ueser input is posted to account_boat_data_update.php"

*/


function boat_name_from_get_url() {

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
$_user_boat_name = boat_name_from_get_url();
$_user_boat_key = key_from_name( $_user_boat_name );

$_boat = asa_from_default_boat();
$_boat[ 'key' ] =  $_user_boat_key ;
$_boat[ 'display_name' ] = $_user_boat_name;

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <p class = "p_class" >Boat name: <?php echo $_boat[ 'display_name' ]?></p>
        <form method="post" action="account_boat_data_update.php">

            <input class = "hidden_class" type="text" id="boat_key" name="boat_key" value="<?php echo $_boat[ 'key' ]; ?>"required>

            <input class = "hidden_class" type="text" id="display_name" name="display_name" value="<?php echo $_boat[ 'display_name']; ?>"required>

            <label class = "label_class" for="owner_first_name">Owner's first name:</label>
            <input class = "text_class" type="text" id="owner_first_name" name="owner_first_name" value="<?php echo $_boat[ 'owner_first_name' ]; ?>"required></br>

            <label class = "label_class" for="owner_last_name">Owner's last name:</label>
            <input class = "text_class" type="text" id="owner_last_name" name="owner_last_name" value="<?php echo $_boat[ 'owner_last_name' ]; ?>"required></br>

            <label class = "label_class" for="email_address">Email address:</label>
            <input class = "text_class" type="email" id="email_address" name="email_address" value="<?php echo $_boat[ 'email_address' ]; ?>"required></br>

            <label class = "label_class" for="mobile_number">Mobile number:</label>
            <input class = "text_class" type="tel" id="mobile_number" name="mobile_number" value="<?php echo $_boat[ 'mobile_number' ];?>"></br>

            <label class = "label_class" for="min_occupancy">Min occupancy:</label>
            <select class = "select_class" name="min_occupancy" id="max_occupancy">
                <option value="1" <?php if($_boat[ 'min_occupancy' ] == '1') echo 'selected'; ?>>1</option>
                <option value="2" <?php if($_boat[ 'min_occupancy' ] == '2') echo 'selected'; ?>>2</option>
                <option value="3" <?php if($_boat[ 'min_occupancy' ] == '3') echo 'selected'; ?>>3</option>
            </select></br>

            <label class = "label_class" for="max_occupancy">Max occupancy:</label>
            <select class = "select_class" name="max_occupancy" id="max_occupancy">
                <option value="1" <?php if($_boat[ 'max_occupancy' ] == '1') echo 'selected'; ?>>1</option>
                <option value="2" <?php if($_boat[ 'max_occupancy' ] == '2') echo 'selected'; ?>>2</option>
                <option value="3" <?php if($_boat[ 'max_occupancy' ] == '3') echo 'selected'; ?>>3</option>
                <option value="4" <?php if($_boat[ 'max_occupancy' ] == '4') echo 'selected'; ?>>4</option>
                <option value="5" <?php if($_boat[ 'max_occupancy' ] == '5') echo 'selected'; ?>>5</option>
                <option value="6" <?php if($_boat[ 'max_occupancy' ] == '6') echo 'selected'; ?>>6</option>
            </select></br>

            <label class = "label_class" for="assistance">Request assistance:</label>
            <select class = "select_class" name="assistance" id="assistance">
                <option value="Yes" <?php if($_boat[ 'assistance' ] == 'Yes') echo 'selected'; ?>>Yes</option>
                <option value="No" <?php if($_boat[ 'assistance' ] == 'No') echo 'selected'; ?>>No</option>
            </select></br>

            <input class = "button_class" type="submit" value="Next">
        </form>
    </body>
</html>
