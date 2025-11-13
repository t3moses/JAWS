<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'database.php';
require_once 'names.php';

function crew_from_post() {

    // Make the $_crew associative array from the posted crew data.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $_crew[ 'key' ] = $_POST['crew_key'] ?? '';
        $_crew[ 'first_name' ] = $_POST['first_name'] ?? '';
        $_crew[ 'last_name' ] = $_POST['last_name'] ?? '';
        $_crew[ 'partner_key' ] = ''; // partner key
        $_crew[ 'email_address' ] = $_POST['email_address'] ?? '';
        $_crew[ 'membership_number' ] = $_POST['membership_number'] ?? '';
        $_crew[ 'skill' ] = $_POST['skill'] ?? '';
        $_crew[ 'experience' ] = $_POST['experience'] ?? '';
        $_crew[ 'whitelist' ] = ''; // whitelisi
        
        return $_crew;
    }
}

// Get the crew array from the post, and extract the key and display name.

$_crew_arr = crew_from_post();
$_user_crew_key = $_crew_arr[ 'key' ];
$_display_name = display_name_from_names( $_crew_arr[ 'first_name' ], $_crew_arr[ 'last_name' ]);

// Get the crews data file as a list of associative arrays.
// Append the array for the new crew.  And write the result back to the file.
$_db_crews_data_lst_asa = lst_asa_from_file( 'crews_data_file' );
$_db_crews_data_lst_asa[] = $_crew_arr;
file_from_lst_asa( 'crews_data_file', $_db_crews_data_lst_asa ); // 

// Now open the crew availability file and append the default record.
// Then write the result back to the file.
$_db_crews_availability_str = str_from_file( 'crews_availability_file' );
$_db_crews_availability_arr_str = explode( "\n", $_db_crews_availability_str );
// $_header_row_str = $_db_crews_availability_arr_str[ 0 ];

$_crew_availability_updated_arr[] = $_crew_arr[ 'key' ];

// Fill the crew availability record with 'U', indicating unavailable.
for ( $_index = 0; $_index < number_of_events(); $_index++ ) {
    $_crew_availability_updated_arr[] = 'U';
}

$_crew_availability_updated_str = implode( ',', $_crew_availability_updated_arr );
$_crews_availability_updated_str = $_db_crews_availability_str . chr(0x0a) . $_crew_availability_updated_str;

file_from_str( 'crews_availability_file', $_crews_availability_updated_str );

?>


<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <div>
            <p class = "p_class" >Username: <?php echo $_display_name; ?></p></br>
        </div>
        <div>
            <p class = "p_class" >Email address: <?php echo $_crew_arr[ 'email_address' ]; ?></p></br>
            <p class = "p_class" >Membership number: <?php echo $_crew_arr[ 'membership_number' ]; ?></p></br>
            <p class = "p_class" >Skill: <?php echo $_crew_arr[ 'skill' ]; ?></p></br>
            <p class = "p_class" >Experience: <?php echo $_crew_arr[ 'experience' ]; ?></p></br>
        </div>
        <div>
            <button class = "button_class" type="button" onclick="window.location.href='/account_crew_availability_form.php?ckey=<?php echo $_user_crew_key;?>'">Next</button>
        </div>
    </body>
</html>
