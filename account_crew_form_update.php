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
function crew_from_form() {

    // Make the $_crew array from the posted crew data.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $_crew[0] = $_POST['crew_key'] ?? '';
        $_crew[1] = $_POST['first_name'] ?? '';
        $_crew[2] = $_POST['last_name'] ?? '';
        $_crew[3] = $_POST['partner key'] ?? '';
        $_crew[4] = $_POST['email_address'] ?? '';
        $_crew[5] = $_POST['membership_number'] ?? '';
        $_crew[6] = $_POST['skill'] ?? '';
        $_crew[7] = $_POST['experience'] ?? '';
        $_crew[8] = $_POST['whitelist'] ?? '';
        
        return $_crew;
    }
}

function display_name_from_names( $_first_name, $_last_name ) {
    // Create a display name from first and last names.
    $_first_part = ucfirst( strtolower( str_replace(" ", "", $_first_name )));
    $_second_part = strtoupper( str_replace(" ", "", $_last_name )[0]);
    return $_first_part . $_second_part;
}

// Get the crew array from the form submission, and convert it into a string
$_crew_updated_arr = crew_from_form();
$_display_name = display_name_from_names( $_crew_updated_arr[1], $_crew_updated_arr[2] );

$_crew_updated_str = implode(',', $_crew_updated_arr );

// Open the crew data file and 
// copy each line (except the one being updated) into $_crews_updated_str.
// Append the updated crew string.

// Read the crew data file as a string.
$_db_crews_str = file_get_contents('crew_data.csv');

// Then explode it into an array of crew strings.
$_db_crews_arr_str = explode( "\n", $_db_crews_str );

// Get the key index from the first row of the crews array.
$_key_index = index_from_row( $_db_crews_arr_str[0], 'key' );
$_user_crew_key = $_crew_updated_arr[ $_key_index ];

// Walk through the array of crew strings.
// Copy all except the one being updated into $_crews_updated_str.
$_crews_updated_str = '';

foreach ( $_db_crews_arr_str as $_db_crew_str ) {

    $_db_crew_arr = explode( ',', $_db_crew_str );

    if ( $_db_crew_arr[ $_key_index ] !== $_user_crew_key && !empty( $_db_crew_str ) ) {

        $_crews_updated_str .= $_db_crew_str . "\n";
    }
}

// Then append the updated crew record to $_crews_updated_str.
$_crews_updated_str .= $_crew_updated_str;

// Write the updated crew data back to the file.
file_put_contents( 'crew_data.csv', $_crews_updated_str );


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
            <p>first name: <?php echo $_crew_updated_arr[1]?></p></br>
            <p>last name: <?php echo $_crew_updated_arr[2]?></p></br>
            <p>Email address: <?php echo $_crew_updated_arr[4]?></p></br>
            <p>Membership number: <?php echo $_crew_updated_arr[5]?></p></br>
            <p>Skill: <?php echo $_crew_updated_arr[6]?></p></br>
            <p>Experience: <?php echo $_crew_updated_arr[7]?></p></br>
        </div>
        <div>
            <button type="button" onclick="window.location.href='/program.html'">Done</button>
        </div>
    </body>
</html>
