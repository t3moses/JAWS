<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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

// Get the boat array from the form posted by account_boat_data_form.php
// and convert it into a string.  This will be appended to $_boats_updated_str
// and written back to the boats_data.csv file.
$_boat_updated_arr = boat_from_form();
$_boat_key = $_boat_updated_arr[ 0 ];
$_max_occupancy = $_boat_updated_arr[ 7 ];
$_boat_updated_str = implode(',', $_boat_updated_arr );

// Read the boats_data.csv file as a string.
$_db_boats_str = file_get_contents( 'boats_data.csv' );

// Then append the updated boat string.
$_db_boats_str  = $_db_boats_str . "\n" . $_boat_updated_str;

// And write the updated boats data back to the file.
file_put_contents( 'boats_data.csv', $_db_boats_str );

//--------------
// Set the availabilitty to "Y" for every event date.
//--------------

// Read the boats_availability.csv file into a string.
$_db_boats_availability_str = file_get_contents('boats_availability.csv');

// We need to know the number of events,
// so explode it into an array of boat availability strings.
$_db_boats_availability_arr_str = explode( "\n", $_db_boats_availability_str );

// And eplode the header row into 'key' followed by event dates.
$_header_row_str = $_db_boats_availability_arr_str[ 0 ];
$_header_row_arr = explode( ",", $_header_row_str );
$_event_count = count( $_header_row_arr );

// Create the updated boat availability array
// as the boat key followed by max_occupancy for each event.
$_boat_availability_updated_arr = [];
$_boat_availability_updated_arr[ 0 ] = $_boat_key;

for ( $_index = 1; $_index < $_event_count; $_index++ ) {
    $_boat_availability_updated_arr[ $_index ] = $_max_occupancy;
}

// Convert the array to a string.
$_boat_availability_updated_str = implode(',', $_boat_availability_updated_arr );

// Then append it to the updated boats availability string.
$_boats_availability_updated_str = $_db_boats_availability_str . chr(0x0a) . $_boat_availability_updated_str;

// And write the result back to the file.
file_put_contents( 'boats_availability.csv', $_boats_availability_updated_str );

?>


<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <body>
        <div>
            <p class = "p_class" >Your account has been updated</p>
        </div>
        <div>
            <p class = "p_class" >Owner first name: <?php echo $_boat_updated_arr[1]?></p></br>
            <p class = "p_class" >Owner last name: <?php echo $_boat_updated_arr[2]?></p></br>
            <p class = "p_class" >Boat name: <?php echo $_boat_updated_arr[3]?></p></br>
            <p class = "p_class" >Email address: <?php echo $_boat_updated_arr[4]?></p></br>
            <p class = "p_class" >Mobile number: <?php echo $_boat_updated_arr[5]?></p></br>
            <p class = "p_class" >Min occupancy: <?php echo $_boat_updated_arr[6]?></p></br>
            <p class = "p_class" >Max occupancy: <?php echo $_boat_updated_arr[7]?></p></br>
            <p class = "p_class" >Assistance: <?php echo $_boat_updated_arr[8]?></p>
        </div>
        <div>
            <button class="button_class" type="button" onclick="window.location.href='/account_boat_availability_form.php?bkey=<?php echo $_boat_key; ?>'">Next</button>
        </div>
    </body>
</html>
