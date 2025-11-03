<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

The query string consists of the crew key and a list of the crew's available codes; one code for each event.

This must be formed into an array and then a comma-separated string.

The file crews_availability.csv contains an entry for the crew identified in the query string.
This entry has to be replaced by the one formed from the query string.
Then the result has to be written back to crews_availability.csv file.

*/

function string_from_get() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve get data
        $_user_str = $_SERVER['QUERY_STRING'];

        // Validate the data
        if (empty( $_user_str )) {
            return null;
        }
        else {
            return $_user_str;
        }
    }
}

// Get the query string from the get URL.
$_user_str = string_from_get();

// Trim the prefix from the query string.

if ( str_starts_with( $_user_str, "key=" )) {
    $_user_str = substr( $_user_str, strlen( "key=" ));
}

// Convert the result into an array.  The first element is the crew key.
// The remaining elements are the crew's availability codes; one for each event.
$_user_arr = explode( "&avail=", $_user_str );
$_user_crew_key = $_user_arr[ 0 ];

// Convert $_user_arr "" / "Y" to "No" / "Yes".
$_availability_updated[ 0 ] = $_user_crew_key ;
$_number_of_events = count( $_user_arr);
for ( $_index = 1; $_index < $_number_of_events; $_index++ ) {
    if ( $_user_arr[ $_index ] === "" ) {
        $_availability_updated[ $_index ] = "No";
    }
    else {
        $_availability_updated[ $_index ] = "Yes";
    }
}

// Now convert the query array back into a comma-separated string.
$_user_str = implode( ",", $_user_arr );

// Now read the crew's availability file as a string.
$_db_crews_availability_str = file_get_contents('crews_availability.csv');

// And explode it into an array of strings; one string for each crew.
$_db_crews_availability_arr_str = explode( "\n", $_db_crews_availability_str );

// Get the event dates from the first row of the crews_availability.csv file.
$_header_arr = explode(",", $_db_crews_availability_arr_str[ 0 ] );
$_number_of_events = count( $_header_arr );

// Now build the updated file.
$_crews_availability_updated_str = '';

// Copy the original file to the updated file,
// replacing the entry for the crew with the user-provided values.

$_number_of_crews = count( $_db_crews_availability_arr_str );

for ( $_index = 0; $_index < $_number_of_crews; $_index++ ) {

    if ( $_index !== 0 ){
        $_crews_availability_updated_str .= chr(0x0a);
    }

    $_db_crew_availability_str = $_db_crews_availability_arr_str[ $_index ];
    $_db_crew_availability_arr = explode( ',', $_db_crew_availability_str );

    if ( $_db_crew_availability_arr[ 0 ] === $_user_crew_key ) {
        $_crews_availability_updated_str .= $_user_str;
    }
    else {
        $_crews_availability_updated_str .= $_db_crew_availability_str;
    }
}

// Finally, rewrite the boats_availability.csv file.
file_put_contents( 'crews_availability.csv', $_crews_availability_updated_str );

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
<!--

Loop through the list of events, displaying the event value.

-->
        <?php for ( $_index = 1; $_index < $_number_of_events; $_index++ ) { ?>
            <div class='flex-container'>
                <div class='column'><p class = "p_class" > <?php echo $_header_arr[ $_index ]; ?></p></div>
                <div class='column'><p class = "p_class" > <?php echo $_availability_updated[ $_index ]; ?></p></div>
                </div>
            </div>
        <?php } ?>
        <div>
            <button type = "button" class = "button_class" onclick = "window.location.href='/program.html'">Done</button>
        </div>
    </body>
</html>
