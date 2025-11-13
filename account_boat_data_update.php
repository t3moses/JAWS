<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';

function boat_from_post() {

    // Make the $_boat associative array from the posted boat data.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $_boat[ 'key' ] = $_POST['boat_key'] ?? '';
        $_boat[ 'owner_first_name' ] = $_POST['owner_first_name'] ?? '';
        $_boat[ 'owner_last_name' ] = $_POST['owner_last_name'] ?? '';
        $_boat[ 'display_name' ] = $_POST['display_name'] ?? '';
        $_boat[ 'email_address' ] = $_POST['email_address'] ?? '';
        $_boat[ 'mobile_number' ] = $_POST['mobile_number'] ?? '';
        $_boat[ 'min_occupancy' ] = $_POST['min_occupancy'] ?? '';
        $_boat[ 'max_occupancy' ] = $_POST['max_occupancy'] ?? '';
        $_boat[ 'assistance' ] = $_POST['assistance'] ?? '';
        
        return $_boat;
    }
}

// Get the boat associative array from the form posted by account_boat_data_form.php
// and convert it into a string.  This will be appended to $_boats_updated_str
// and written back to the boats_data.csv file.
// The boat per-event occupancy will, initially, be set to the boat max occupancy.
$_boat_update_asa = boat_from_post();
$_boat_key = $_boat_update_asa[ 'key' ];
$_max_occupancy = $_boat_update_asa[ 'max_occupancy' ];
$_boat_update_str = implode( ',', $_boat_update_asa );

// Read the boats data file as a string.
$_db_boats_data_str = str_from_file( 'boats_data_file' );

// Then append the updated boat string.
$_db_boats_updated_str  = $_db_boats_data_str . "\n" . $_boat_update_str . "\n";

// And write the updated boats data back to the file.
file_from_str( 'boats_data_file', $_db_boats_updated_str );

//--------------
// Set the availabilitty to max occupancy for every event date.
//--------------

// Read the boats_availability_file into a string.
$_db_boats_availability_str = str_from_file( 'boats_availability_file' );

// We need to know the number of events,
$_number_of_events = number_of_events();

// Create the updated boat availability array
// as the boat key followed by the boat's max occupancy for each event.
$_boat_availability_updated_arr = [];
$_boat_availability_updated_arr[] = $_boat_key;

for ( $_index = 0; $_index < $_number_of_events ; $_index++ ) {
    $_boat_availability_updated_arr[] = $_max_occupancy;
}

// Convert the array to a string.
$_boat_availability_updated_str = implode(',', $_boat_availability_updated_arr );

// Then append it to the updated boats availability string.
$_boats_availability_updated_str = $_db_boats_availability_str . chr(0x0a) . $_boat_availability_updated_str . "\n";

// And write the result back to the file.
file_from_str( 'boats_availability_file', $_boats_availability_updated_str ); 

?>


<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <div>
            <p class = "p_class" ><?php echo $_boat_update_asa[ 'display_name' ]?>'s account has been created</p>
        </div>
        <div>
            <p class = "p_class" >Owner first name: <?php echo $_boat_update_asa[ 'owner_first_name' ]?></p></br>
            <p class = "p_class" >Owner last name: <?php echo $_boat_update_asa[ 'owner_last_name' ]?></p></br>
            <p class = "p_class" >Boat name: <?php echo $_boat_update_asa[ 'display_name' ]?></p></br>
            <p class = "p_class" >Email address: <?php echo $_boat_update_asa[ 'email_address' ]?></p></br>
            <p class = "p_class" >Mobile number: <?php echo $_boat_update_asa[ 'mobile_number' ]?></p></br>
            <p class = "p_class" >Min occupancy: <?php echo $_boat_update_asa[ 'min_occupancy' ]?></p></br>
            <p class = "p_class" >Max occupancy: <?php echo $_boat_update_asa[ 'max_occupancy' ]?></p></br>
            <p class = "p_class" >Assistance: <?php echo $_boat_update_asa[ 'assistance' ]?></p>
        </div>
        <div>
            <button class="button_class" type="button" onclick="window.location.href='/account_boat_availability_form.php?bkey=<?php echo $_boat_key; ?>'">Next</button>
        </div>
    </body>
</html>
