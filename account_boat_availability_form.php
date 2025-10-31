<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

Get $_boat_name using the user's posted form data,
Convert it to $_boat_key.
Check if $_boat_key exists in boats_data.csv.
If it exists, load account_boat_availabiity_form.php.
If it does not exist, load account_boat_data_form.php.
In either case, post $_boat_key.
Use:

<?php

if ( $_record_exists ) {
    header("Location: /account_boat_availability_form.php?" . $_boat_key);
    exit;
} else {
    header("Location: /account_boat_data_form.php?" . $_boat_key);
    exit;
}

?>

The target files can then use $_GET to retrieve the boat key.

*/

function subject_attribute_from_file( $_subject_key, $_attribute_name, $_file ) {

// Convert the file to an array of strings.
// Convert the first string to an array (this is the header row).
// Find the array index of the requested attribute.

    $_file_arr_str = explode( "\n", $_file );
    $_header_str = $_file_arr_str[ 0 ];
    $_header_arr = explode( ',', $_header_str );
    $_attribute_index = array_search( $_attribute_name, $_header_arr );

// Traverse the rows of the array looking for the one that corespomds to the subject.
// Do this by converting each row to an array of attibute values (the first attribute is the subject key).
// Then return the value of the requested attribute.
// If the subject is not represented in the file, return  null.

    foreach ( $_file_arr_str as $_file_str ) {
        $_attribute_arr = explode( ',', $_file_str );
        if ( $_attribute_arr[ 0 ] === $_subject_key ) {
            return $_attribute_arr[ $_attribute_index ];
        }
    }
    return null;
}

function boat_key_from_form() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_boat_key = $_GET['bkey'] ?? '';

        // Validate the data
        if (empty($_boat_key)) {
            return null;
        }
        else {
            return $_boat_key;
        }
    }
}

// Get the boat key from the redirect and look up its name in boats_data.csv.
$_user_boat_key = boat_key_from_form();
$_db_boat_data = file_get_contents('boats_data.csv');
$_display_name = subject_attribute_from_file( $_user_boat_key, "display name", $_db_boat_data );

// Read the boat availability file into an array of boat strings.
$_db_boats_availability_str = file_get_contents('boats_availability.csv');
$_db_boats_availability_arr_str = explode( "\n", $_db_boats_availability_str );

$_header_arr = explode( ",", $_db_boats_availability_arr_str[ 0 ] );

foreach ( $_db_boats_availability_arr_str as $_db_boat_availability_str ) {

    $_db_boat_availability_arr = explode( ',', $_db_boat_availability_str );

    if ( $_db_boat_availability_arr[ 0 ] === $_user_boat_key) {

        break; // with $_db_boat_availability_arr containing the target boat availability.

    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <style>
            .form_class{
                margin-top: 5px;
                margin-bottom: 2px;
                background-color: #DDDDDD;
                border: 2px solid #000000;
                border-radius: 10px;
                font-size: 24px;
            }
            .text_class{
                margin-top: 10px;
                background-color: #DDDDDD;
                border: 2px solid #000000;
                border-radius: 10px;
                font-size: 24px;
            }
            .button_class{
                margin-top: 10px;
                background-color: #DDDDDD;
                border: 2px solid #000000;
                border-radius: 10px;
                font-size: 24px;
                cursor: pointer;
            }
            .div_class{
                margin-left: 10px;
                margin-bottom: 10px;
            }
            .hidden {
                display: none;
            }
            .select_class{
                margin-top: 10px;
                background-color: #DDDDDD;
                border: 2px solid #000000;
                border-radius: 10px;
                font-size: 24px;
            }
            label {
                display: inline-block;
                font-size: 24px;
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
        <p>Boat name: <?php echo $_display_name; ?></p>
        <form method="get" action="account_boat_availability_update.php">
            <input class = "hidden" type="text" id="key" name="key" value="<?php echo $_user_boat_key; ?>"required>


<!--

Lopp through the list of events, displaying the event value and
offering a choice betweenAvailable and not available.  

-->

            <?php

                for ( $_index = 1; $_index < count( $_db_boat_availability_arr ); $_index++ ) {
                    echo "<p>" . $_header_arr[ $_index ] . "</p>";
                    echo "<select class = select_class name=avail id=avail>";
                        echo "<option value=0";
                        if($_db_boat_availability_arr[ $_index ] === '0') echo ' selected';
                        echo ">0</option>";
                        echo "<option value=1";
                        if($_db_boat_availability_arr[ $_index ] === '1') echo ' selected';
                        echo ">1</option>";
                        echo "<option value=2";
                        if($_db_boat_availability_arr[ $_index ] === '2') echo ' selected';
                        echo ">2</option>";
                        echo "<option value=3";
                        if($_db_boat_availability_arr[ $_index ] === '3') echo ' selected';
                        echo ">3</option>";
                        echo "<option value=4";
                        if($_db_boat_availability_arr[ $_index ] === '4') echo ' selected';
                        echo ">4</option>";
                        echo "<option value=5";
                        if($_db_boat_availability_arr[ $_index ] === '5') echo ' selected';
                        echo ">5</option>";
                        echo "<option value=6";
                        if($_db_boat_availability_arr[ $_index ] === '6') echo ' selected';
                        echo ">6</option>";
                    echo "</select></br>";
                }

            ?>

            <input class = "button_class" type="submit" value="Submit"> 
        </form>
    </body>
</html>
