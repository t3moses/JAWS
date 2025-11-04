<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'names.php';

function crew_key_from_form() {

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

// Get the crew key from the redirect and look up its display name in crews_data.csv.
$_user_crew_key = crew_key_from_form();
$_db_crew_data = file_get_contents('crews_data.csv');

$_first_name = subject_attribute_from_file( $_user_crew_key, "first name", $_db_crew_data );
$_last_name = subject_attribute_from_file( $_user_crew_key, "last name", $_db_crew_data );
$_display_name = display_name_from_names( $_first_name, $_last_name );

// Read the crew availability file into an array of crew strings.
$_db_crews_availability_str = file_get_contents('crews_availability.csv');
$_db_crews_availability_arr_str = explode( "\n", $_db_crews_availability_str );

$_header_arr = explode( ",", $_db_crews_availability_arr_str[ 0 ] );

foreach ( $_db_crews_availability_arr_str as $_db_crew_availability_str ) {

    $_db_crew_availability_arr = explode( ',', $_db_crew_availability_str );

    if ( $_db_crew_availability_arr[ 0 ] === $_user_crew_key) {

        break; // with $_db_crew_availability_arr containing the target crew availability.

    }
}

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

            <?php for ( $_index = 1; $_index < count( $_db_crew_availability_arr ); $_index++ ) { ?>
                
                <div class='flex-container'>
                    <div class='column'>
                        <p class = "p_class" ><?php echo $_header_arr[ $_index ]; ?></p>
                    </div>
                    <div class='column'>
                        <p class = "p_class" >I am available</p>
                    </div>
                    <div class='column'>
                        <select class = select_class name=avail id=avail>

                            <option value = "" <?php if($_db_crew_availability_arr[ $_index ] === '' ) { echo ' selected'; } ?>>No</option>
                            <option value = "Y" <?php if($_db_crew_availability_arr[ $_index ] !== '' ) { echo ' selected'; } ?>>Yes</option>

                        </select></br>
                    </div>
                </div>
            <?php } ?>

            <input class = "button_class" type="submit" value="Next"> 
        </form>
    </body>
</html>
