<?php

// Prevent caching of this page

/*

ARRIVE HERE ONLY IF THE CREW MEMBER DOES CURRENTLY HAVE AN ACCOUNT.

*/

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';
require_once 'names.php';

function crew_key_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_crew_key = $_GET['ckey'] ?? '';

        // Validate the data
        if (empty($_crew_key)) {
            return null;
        }
        else {
            return $_crew_key;
        }
    }
}

// Get the crew key from the get url query string and look up its display name in crews_data.csv.
$_user_crew_key = crew_key_from_get_url();
$_db_crews_data_lst_asa = lst_asa_from_file( 'crews_data_file' );

$_first_name = subject_attribute_from_file( $_user_crew_key, "first_name", $_db_crews_data_lst_asa );
$_last_name = subject_attribute_from_file( $_user_crew_key, "last_name", $_db_crews_data_lst_asa );
$_display_name = display_name_from_names( $_first_name, $_last_name );

// Get the database crews availability as a list of strings.
$_db_crews_availability_str = str_from_file( 'crews_availability_file' );
$_db_crews_availability_lst_str = explode( "\n", $_db_crews_availability_str );

// Find the array corresponding to $_user_crew_key.
// Isolate the key.
$_record_exists = false;
foreach ( $_db_crews_availability_lst_str as $_db_crew_availability_str ) {
    $_db_crew_availability_arr = explode( ',', $_db_crew_availability_str );
    if ( $_db_crew_availability_arr[ 0 ] === $_user_crew_key ) {
        $_record_exists = true;
        $_db_crew_key = array_shift( $_db_crew_availability_arr );
        break; // with $_db_crew_availability_arr containing the target crew availability array minus the key.
    }
}
if ( $_record_exists === false ) { die(' Unabkle to find crew record' ); }

// Get the event ids and the number of events from the database.
$_event_ids = event_ids();
$_number_of_events = number_of_events();


?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <p class = "p_class" >Username: <?php echo $_display_name; ?></p>
        <form method="get" action="account_crew_availability_update.php">
            <input class = "hidden_class" type="text" id="key" name="key" value="<?php echo $_user_crew_key; ?>"required>


<!--

Lopp through the list of events, displaying the event value and
offering a choice betweenAvailable and not available.  

-->

            <?php for ( $_index = 0; $_index < $_number_of_events; $_index++ ) { ?>
                
                <div class='flex-container'>
                    <div class='column'>
                        <p class = "p_class" ><?php echo $_event_ids[ $_index ]; ?></p>
                    </div>
                    <div class='column'>
                        <p class = "p_class" >I am available</p>
                    </div>
                    <div class='column'>
                        <select class = select_class name=avail id=avail>

                            <option value = "No" <?php if($_db_crew_availability_arr[ $_index ] === 'U' ) { echo ' selected'; } ?>>No</option>
                            <option value = "Yes" <?php if($_db_crew_availability_arr[ $_index ] !== 'U' ) { echo ' selected'; } ?>>Yes</option>

                        </select></br>
                    </div>
                </div>
            <?php } ?>

            <input class = "button_class" type="submit" value="Next"> 
        </form>
    </body>
</html>
