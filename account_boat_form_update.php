<?php

function boat_from_form() {
    // Make the $_boat array to hold posted boat data.

    // Check if the form was podted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $_boat[0] = $_POST['boat_key'] ?? '';
        $_boat[1] = $_POST['owner_key'] ?? '';
        $_boat[2] = $_POST['display_name'] ?? '';
        $_boat[3] = $_POST['email_address'] ?? '';
        $_boat[4] = $_POST['mobile_number'] ?? '';
        $_boat[5] = $_POST['min_occupancy'] ?? '';
        $_boat[6] = $_POST['max_occupancy'] ?? '';
        $_boat[7] = $_POST['assistance'] ?? '';
        
        return $_boat;
    }
}

// Get the boat array from the form submission, and convert it into a string
$_boat_updated_arr = boat_from_form();
$_boat_updated_str = implode(', ', $_boat_updated_arr );

// Get the boat key from the boat array.
$_boat_key = $_boat_updated_arr[0];

// Open the boat data file and 
// copy each line (except the one being updated) into $_boats_updated_str.
// Then append the updated boat record to $_boats_updated_str.

$_boats_updated_str = '';

$_file_handle = fopen('boat_data.csv', 'r');
while (($_boat_str = fgets($_file_handle)) !== false) {
    $_boat_arr = str_getcsv( $_boat_str );
    if ( $_boat_arr[0] !== $_boat_key ) {
        $_boats_updated_str .= $_boat_str;
    }
}
fclose($_file_handle);

$_boats_updated_str .= $_boat_updated_str;

// Write the updated boat data back to the file.
$_file_handle = fopen("boat_data.csv", 'w');
if ( $_file_handle ) {
    fwrite($_file_handle, $_boats_updated_str);
    fclose($_file_handle);
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
        <h2>Account updated</h2>
        <p>Boat name: <?php echo $_boat_updated_arr[2]?></p>
        <p>Email address: <?php echo $_boat_updated_arr[3]?></p>
        <p>Mobile number: <?php echo $_boat_updated_arr[4]?></p>
        <p>Min occupancy: <?php echo $_boat_updated_arr[5]?></p>
        <p>Max occupancy: <?php echo $_boat_updated_arr[6]?></p>
        <p>Assistance: <?php echo $_boat_updated_arr[7]?></p>
    </body>
</html>
