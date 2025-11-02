<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

ARRIVE HERE ONLY IF THE CREW MEMBER DOES NOT CURRENTLY HAVE AN ACCOUNT.

*/

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

function crew_name_array_from_get() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_cname[ 0 ] = $_GET['first'] ?? '';
        $_cname[ 1 ] = $_GET['last'] ?? '';

        // Validate the data
        if (empty( $_cname )) {
            return null;
        }
        else {
            return $_cname;
        }
    }
}

function crew_key_from_name_arr( $_name ) {
    // Create a database key from an array of first and last names.
    return strtolower( $_name[0 ] . $_name[ 1 ] );
}

$_crew_name_arr = crew_name_array_from_get();

$_first_name = $_crew_name_arr[0];
$_last_name = $_crew_name_arr[1];

// Convert the crew name supplied by the user into a key.
$_user_crew_key = crew_key_from_name_arr( $_crew_name_arr );

$_email_address = '';
$_membership_number = '';
$_skill = '0';
$_experience = '';


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
            .textarea_class{
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
        </style>
    </head>
    <body>

        <form class = "form_class" method="post" action="account_crew_data_update.php">

            <input class = "hidden" type="text" id="crew_key" name="crew_key" value="<?php echo $_user_crew_key; ?>">
            <input class = "hidden" type="text" id="first_name" name="first_name" value="<?php echo $_first_name; ?>">
            <input class = "hidden" type="text" id="last_name" name="last_name" value="<?php echo $_last_name; ?>">

            <label for="email_address">Email address:</label>
            <input class = "text_class" type="text" id="email_address" name="email_address" value="<?php echo $_email_address; ?>"required></br>

            <label for="membership_number">Membership number:</label>
            <input class = "text_class" type="text" id="membership_number" name="membership_number" value="<?php echo $_memebership_number; ?>"></br>

            <label for="skill">Skill:</label>
            <select class = "select_class" name="skill" id="skill">
                <option value="0" <?php if($_skill == '0') echo 'selected'; ?>>New</option>
                <option value="1" <?php if($_skill == '1') echo 'selected'; ?>>Crew</option>
                <option value="2" <?php if($_skill == '2') echo 'selected'; ?>>Skipper</option>
            </select></br>

            <label for="experience">Experience:</label>
            <textarea class = "textarea_class" name="experience" id="experience" rows="10" cols="30"><?php echo $_experience; ?></textarea></br>

            <input class = "button_class" type="submit" value="Next"> 

        </form>

    </body>
</html>
