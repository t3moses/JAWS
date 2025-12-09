
<?php

use nsc\sdc\name as name;
use nsc\sdc\boat as boat;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Boat/src/Boat.php';

/*

ARRIVE HERE ONLY IF THE BOAT DOES NOT CURRENTLY HAVE AN ACCOUNT.

The get url query string contains the boat name provided by the user.
This is converted to a key.
An empty form is displayed and the ueser input is posted to account_boat_data_update.php"

*/


function boat_name_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_boat_name = name\safe($_GET['bname']) ?? '';

        // Validate the data
        if (empty($_boat_name)) {
            return null;
        }
        else {
            return $_boat_name;
        }
    }
}

$_user_boat_name = boat_name_from_get_url();
$_boat_key = name\key_from_string( $_user_boat_name );
$_display_name = name\display_name_from_string( $_user_boat_name );
$_boat = new boat\Boat();
$_boat->set_default();
$_boat->set_key( $_boat_key );
$_boat->set_display_name( $_display_name );

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <div>
            <a href='/../../../program.html'>
                <img src='/./Libraries/Html/data/NSC-SDC_logo.png' alt='Program page' width = '100'>
            </a>
        </div>
        <p class = "p_class" >Boat name: <?php echo $_boat->get_display_name()?></p>
        <form method="post" action="account_boat_data_update.php">

            <input class = "hidden_class" type="text" id="boat_key" name="boat_key" value="<?php echo $_boat->get_key(); ?>"required>

            <input class = "hidden_class" type="text" id="display_name" name="display_name" value="<?php echo $_boat->get_display_name(); ?>"required>

            <label class = "label_class" for="owner_first_name">Owner's first name:</label>
            <input class = "text_class" type="text" id="owner_first_name" name="owner_first_name" value="<?php echo $_boat->get_owner_first_name(); ?>"required></br>

            <label class = "label_class" for="owner_last_name">Owner's last name:</label>
            <input class = "text_class" type="text" id="owner_last_name" name="owner_last_name" value="<?php echo $_boat->get_owner_last_name(); ?>"required></br>

            <label class = "label_class" for="email_address">Email address:</label>
            <input class = "text_class" type="email" id="owner_email" name="owner_email" value="<?php echo $_boat->get_owner_email(); ?>"required></br>

            <label class = "label_class" for="mobile_number">Mobile number:</label>
            <input class = "text_class" type="tel" id="owner_mobile" name="owner_mobile" value="<?php echo $_boat->get_owner_mobile();?>"></br>

            <label class = "label_class" for="min_berths">Min occupancy:</label>
            <select class = "select_class" name="min_berths" id="min_berths">
                <option value="1" <?php if($_boat->get_min_berths() == '1') echo 'selected'; ?>>1</option>
                <option value="2" <?php if($_boat->get_min_berths() == '2') echo 'selected'; ?>>2</option>
                <option value="3" <?php if($_boat->get_min_berths() == '3') echo 'selected'; ?>>3</option>
            </select></br>

            <label class = "label_class" for="max_berths">Max occupancy:</label>
            <select class = "select_class" name="max_berths" id="max_berths">
                <option value="1" <?php if($_boat->get_max_berths() == '1') echo 'selected'; ?>>1</option>
                <option value="2" <?php if($_boat->get_max_berths() == '2') echo 'selected'; ?>>2</option>
                <option value="3" <?php if($_boat->get_max_berths() == '3') echo 'selected'; ?>>3</option>
                <option value="4" <?php if($_boat->get_max_berths() == '4') echo 'selected'; ?>>4</option>
                <option value="5" <?php if($_boat->get_max_berths() == '5') echo 'selected'; ?>>5</option>
                <option value="6" <?php if($_boat->get_max_berths() == '6') echo 'selected'; ?>>6</option>
            </select></br>

            <label class = "label_class" for="assistance_required">Request assistance:</label>
            <select class = "select_class" name="assistance_required" id="assistance_required">
                <option value="Yes" <?php if($_boat->get_assistance_required() == 'Yes') echo 'selected'; ?>>Yes</option>
                <option value="No" <?php if($_boat->get_assistance_required() == 'No') echo 'selected'; ?>>No</option>
            </select></br>

            <input class = "button_class" type="submit" value="Next">
        </form>
    </body>
</html>
