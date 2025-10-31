<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/*

Get $_crew_name using the user's posted form data,
Convert it to $_crew_key.
Check if $_crew_key exists in crew_data.csv.
If it exists, load account_account_availabiity_form.php.
If it does not exist, load account_crew_data_form.php.
In either case, post $_crew_key.

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

function crew_name_from_form() {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Retrieve form data
        $_first_name = $_POST['fname'] ?? '';
        $_last_name = $_POST['lname'] ?? '';

        // Validate the data
        if (empty($_first_name) || empty($_last_name)) {
            return null;
        }
        else {
            $_crew_name[0] = $_first_name;
            $_crew_name[1] = $_last_name;
            return $_crew_name;
        }
    }
}

function crew_key_from_name( $_first_name, $_last_name ) {
    // Create a sanitized database key from a crew member's name.
    return trim( strtolower( htmlspecialchars( $_first_name . $_last_name )));
}

$_crew_name = crew_name_from_form();

$_first_name = $_crew_name[0];
$_last_name = $_crew_name[1];

// Convert the crew name supplied by the user into a key.
$_user_crew_key = crew_key_from_name( $_first_name, $_last_name );

// Read the crew data file into an array of boat records.
$_db_crews = file_get_contents('crew_data.csv');

$_db_crews = explode( "\n", $_db_crews );

$_header_row = $_db_crews[0];

$_key_index = index_from_row( $_header_row, 'key' );

$_record_exists = false;

foreach ( $_db_crews as $_db_crew ) {

    $_db_crew = explode( ',', $_db_crew );

    if ( $_db_crew[ $_key_index ] === $_user_crew_key ) {

        $_crew_key = $_user_crew_key;
        $_first_name = $_db_crew[ index_from_row( $_header_row, 'first name' )];
        $_last_name = $_db_crew[ index_from_row( $_header_row, 'last name' )];
        $_partner_key = $_db_crew[ index_from_row( $_header_row, 'partner key' )];
        $_email_address = $_db_crew[ index_from_row( $_header_row, 'email address' )];
        $_member_number = $_db_crew[ index_from_row( $_header_row, 'membership number' )];
        $_skill = $_db_crew[ index_from_row( $_header_row, 'skill' )];
        $_experience = $_db_crew[ index_from_row( $_header_row, 'experience' )];
        $_whitelist = $_db_crew[ index_from_row( $_header_row, 'whitelist' )];

        $_record_exists = true;
        break;

    }
}
if ( !$_record_exists ) {
    // If no record exists for this crew member, set default values.
    $_partner_key = '';
    $_email_address = '';
    $_membership_number = '';
    $_skill = '0';
    $_experience = '';
    $_whitelist = '';
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
            p {
                display: inline-block;
                font-size: 24px;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <?php if ($_record_exists === true): ?>

            <form method="post" action="account_crew_form_update.php">

                <label for="first_name">First name:</label>
                <input class = "text_class" type="text" id="first_name" name="first_name" value="<?php echo $_first_name?>"required></br>

                <label for="last_name">Last name:</label>
                <input class = "text_class" type="text" id="last_name" name="last_name" value="<?php echo $_last_name?>"required></br>

                <label for="email_address">Email address:</label>
                <input class = "text_class" type="text" id="email_address" name="email_address" value="<?php echo $_email_address?>"required></br>

                <label for="membership_number">Membership number:</label>
                <input class = "text_class" type="text" id="membership_number" name="membership_number" value="<?php echo $_memebership_number?>"></br>

                <label for="skill">Skill:</label>
                <select class = "select_class" name="skill" id="skill">
                    <option value="0" <?php if($_skill == '0') echo 'selected'; ?>>New</option>
                    <option value="1" <?php if($_skill == '1') echo 'selected'; ?>>Crew</option>
                    <option value="2" <?php if($_skill == '2') echo 'selected'; ?>>Skipper</option>
                </select></br>

                <label for="experience">Experience:</label>
                <textarea class = "textarea_class" name="experience" id="experience" rows="10" cols="30"><?php echo $_experience?></textarea></br>

                <input class = "button_class" type="submit" value="Submit"> 

            </form>

        <?php else: ?>
            
            <p>No record found for crew member <?php echo $_first_name . ' ' . $_last_name; ?>.</p>

            <form method="post" action="account_crew_form_update.php">

                <input class = "button_class" type="submit" value="Submit"> 

            </form>

        <?php endif; ?>
    </body>
</html>
