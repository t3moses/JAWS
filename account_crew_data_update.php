<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


function crew_from_form() {

    // Make the $_crew array from the posted crew data.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $_crew[0] = $_POST['crew_key'] ?? '';
        $_crew[1] = $_POST['first_name'] ?? '';
        $_crew[2] = $_POST['last_name'] ?? '';
        $_crew[3] = ''; // partner key
        $_crew[4] = $_POST['email_address'] ?? '';
        $_crew[5] = $_POST['membership_number'] ?? '';
        $_crew[6] = $_POST['skill'] ?? '';
        $_crew[7] = $_POST['experience'] ?? '';
        $_crew[8] = ''; // whitelisi
        
        return $_crew;
    }
}

function display_name_from_names( $_first_name, $_last_name ) {
    // Create a display name from first and last names.
    $_first_part = ucfirst( strtolower( str_replace(' ', '', $_first_name )));
    $_second_part = strtoupper( str_replace(' ', '', $_last_name )[0]);
    return $_first_part . $_second_part;
}

// Get the crew array from the post, and create the display name.

$_crew_arr = crew_from_form();
$_user_crew_key = $_crew_arr[ 0 ];

$_display_name = display_name_from_names( $_crew_arr[1], $_crew_arr[2] );

$_crew_updated_str = implode(',', $_crew_arr );

// Open the crew data file and append the updated crew string.
// Then write the result back to the file.
$_db_crews_str = file_get_contents('crews_data.csv');
$_crews_updated_str = $_db_crews_str . chr(0x0a) . $_crew_updated_str;
file_put_contents( 'crews_data.csv', $_crews_updated_str );

// Now open the crew availability file and append a recod.
// Then write the resilt back to the file.
$_db_crews_availability_str = file_get_contents('crews_availability.csv');
$_db_crews_availability_arr_str = explode( "\n", $_db_crews_availability_str );
$_header_row_str = $_db_crews_availability_arr_str[ 0 ];

$_header_row_arr = explode( ',', $_header_row_str );
$_number_of_events = count( $_header_row_arr );

$_crew_availability_updated_arr = [];
$_crew_availability_updated_arr[ 0 ] = $_crew_arr[ 0 ]; // crew key

// Fill the crew availability record with null strings indicating not available.
for ( $_index = 1; $_index < $_number_of_events; $_index++ ) {
    $_crew_availability_updated_arr[ $_index ] = '';
}

$_crew_availability_updated_str = implode( ',', $_crew_availability_updated_arr );
$_crews_availability_updated_str = $_db_crews_availability_str . chr(0x0a) . $_crew_availability_updated_str;

file_put_contents( 'crews_availability.csv', $_crews_availability_updated_str );

?>


<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="css/styles.css">
    </head>
    <body>
        <div>
            <p class = "p_class" >Username: <?php echo $_display_name; ?></p></br>
        </div>
        <div>
            <p class = "p_class" >Email address: <?php echo $_crew_arr[4]; ?></p></br>
            <p class = "p_class" >Membership number: <?php echo $_crew_arr[5]; ?></p></br>
            <p class = "p_class" >Skill: <?php echo $_crew_arr[6]; ?></p></br>
            <p class = "p_class" >Experience: <?php echo $_crew_arr[7]; ?></p></br>
        </div>
        <div>
            <button class = "button_class" type="button" onclick="window.location.href='/account_crew_availability_form.php?ckey=<?php echo $_user_crew_key;?>'">Next</button>
        </div>
    </body>
</html>
