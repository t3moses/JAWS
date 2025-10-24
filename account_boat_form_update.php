<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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
function boat_from_form() {

    // Make the $_boat array from the posted boat data.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $_boat[0] = $_POST['boat_key'] ?? '';
        $_boat[1] = $_POST['owner_first_name'] ?? '';
        $_boat[2] = $_POST['owner_last_name'] ?? '';
        $_boat[3] = $_POST['display_name'] ?? '';
        $_boat[4] = $_POST['email_address'] ?? '';
        $_boat[5] = $_POST['mobile_number'] ?? '';
        $_boat[6] = $_POST['min_occupancy'] ?? '';
        $_boat[7] = $_POST['max_occupancy'] ?? '';
        $_boat[8] = $_POST['assistance'] ?? '';
        
        return $_boat;
    }
}

// Get the boat array from the form submission, and convert it into a string
$_boat_updated_arr = boat_from_form();

$_boat_updated_str = implode(',', $_boat_updated_arr );

echo $_boat_updated_str; // --- IGNORE ---

// Open the boat data file and 
// copy each line (except the one being updated) into $_boats_updated_str.
// Append the updated boat string.

// Read the boat data file as a string.
$_db_boats_str = file_get_contents('boat_data.csv');

// Then explode it into an array of boat strings.
$_db_boats_arr_str = explode( "\n", $_db_boats_str );

// Get the key index from the first row of the boats array.
$_key_index = index_from_row( $_db_boats_arr_str[0], 'key' );
$_user_boat_key = $_boat_updated_arr[ $_key_index ];

// Walk through the array of boat strings.
// Copy all except the one being updated into $_boats_updated_str.
$_boats_updated_str = '';

foreach ( $_db_boats_arr_str as $_db_boat_str ) {

    $_db_boat_arr = explode( ',', $_db_boat_str );

    if ( $_db_boat_arr[ $_key_index ] !== $_user_boat_key && !empty( $_db_boat_str ) ) {

        $_boats_updated_str .= $_db_boat_str . "\n";
    }
}

// Then append the updated boat record to $_boats_updated_str.
$_boats_updated_str .= $_boat_updated_str;

// Write the updated boat data back to the file.
file_put_contents( 'boat_data.csv', $_boats_updated_str );


?>

<!DOCTYPE html>
<html>
    <head>
        <style>
            button {
                margin-top: 10px;
                background-color: #DDDDDD;
                border: 2px solid #000000;
                border-radius: 10px;
                font-size: 24px;
                cursor: pointer;
            }
            label {
                display: inline-block;
                width: 150px;
                margin-bottom: 10px;
            }
            p {
                display: inline-block;
                font-size: 24px;
                margin-bottom: 10px;
            }    
        </style>
    </head>
    <body>
        <div>
            <p>Your account has been updated</p>
        </div>
        <div>
            <p>Owner first name: <?php echo $_boat_updated_arr[1]?></p></br>
            <p>Owner last name: <?php echo $_boat_updated_arr[2]?></p></br>
            <p>Boat name: <?php echo $_boat_updated_arr[3]?></p></br>
            <p>Email address: <?php echo $_boat_updated_arr[4]?></p></br>
            <p>Mobile number: <?php echo $_boat_updated_arr[5]?></p></br>
            <p>Min occupancy: <?php echo $_boat_updated_arr[6]?></p></br>
            <p>Max occupancy: <?php echo $_boat_updated_arr[7]?></p></br>
            <p>Assistance: <?php echo $_boat_updated_arr[8]?></p>
        </div>
        <div>
            <button type="button" onclick="window.location.href='/program.html'">Next</button>
        </div>
    </body>
</html>
