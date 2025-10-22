<?php

function boat_from_key( $_boat_key, $_boats ) {

    // Return the element array corresponding to the boat key.

    foreach($_boats as $_boat){
        if ( $_boat[0] === $_boat_key) {
            return $_boat;
        }
    }
}

function key_from_name() {

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $bname = $_POST['bname'] ?? '';
        
        // Validate the data
        if (empty($bname)) {
            return null;
        }
        
        // Sanitize data to prevent security issues
        $bname = htmlspecialchars($bname);
        
        return (trim(strtolower($bname)));
    }
}

// Convert the boat name supplied by the user into a key.
$_boat_key = key_from_name();

$_boats = file('boat_data.csv');

foreach ( $_boats as $_line ) {
    $_boat = str_getcsv( $_line );
    if ( $_boat[0] === $_boat_key ) {
        $_boat_key = $_boat[0];
        $_owner_key = $_boat[1];
        $_display_name = $_boat[2];
        $_email_address = $_boat[3];
        $_mobile_number = $_boat[4];
        $_min_occupancy = $_boat[5];
        $_max_occupancy = $_boat[6];
        $_assistance = $_boat[7];
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <style>
            .hidden {
                display: none;
            }
            label {
                display: inline-block;
                width: 150px;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <p>Boat name: <?php echo $_display_name?></p>

        <form method="post" action="account_boat_form_update.php">

            <label class = "hidden" for="boat_key">Boat key:</label>
            <input class = "hidden" type="text" id="boat_key" name="boat_key" value="<?php echo $_boat_key?>"required>

            <label class = "hidden" for="owner_key">Owner key:</label>
            <input class = "hidden" type="text" id="owner_key" name="owner_key" value="<?php echo $_owner_key?>"required>

            <label class = "hidden" for="display_name">Display name:</label>
            <input class = "hidden" type="text" id="display_name" name="display_name" value="<?php echo $_display_name?>"required>

            <label for="email_address">Email address:</label>
            <input type="text" id="email_address" name="email_address" value="<?php echo $_email_address?>"required></br>

            <label for="mobile_number">Mobile number:</label>
            <input type="text" id="mobile_number" name="mobile_number" value="<?php echo $_mobile_number?>"></br>

            <label for="min_occupancy">Min occupancy:</label>
            <input type="text" id="min_occupancy" name="min_occupancy" value="<?php echo $_min_occupancy?>"required></br>

            <label for="max_occupancy">Max occupancy:</label>
            <input type="text" id="max_occupancy" name="max_occupancy" value="<?php echo $_max_occupancy?>"required></br>

            <label for="assistance">Request assistance:</label>
            <input type="text" id="assistance" name="assistance" value="<?php echo $_assistance?>"required></br>

            <input type="submit" value="Submit"> 
        </form>
    </body>
</html>
