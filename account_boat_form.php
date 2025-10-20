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
        $_email = $_boat[3];
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <p>Your email address is <?php echo $_email; ?></p>
    </body>
</html>
