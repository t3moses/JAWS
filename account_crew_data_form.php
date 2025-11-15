<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'arrays.php';
require_once 'names.php';
require_once 'database.php';

/*

ARRIVE HERE ONLY IF THE CREW MEMBER DOES NOT CURRENTLY HAVE AN ACCOUNT.

*/

function crew_name_array_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_cname[ 0 ] = $_GET['fname'] ?? '';
        $_cname[ 1 ] = $_GET['lname'] ?? '';

        // Validate the data
        if (empty( $_cname )) {
            return null;
        }
        else {
            return $_cname;
        }
    }
}

$_crew_name_arr = crew_name_array_from_get_url();

$_crew = asa_from_default_crew();
$_crew[ 'key' ] = key_from_strings( $_crew_name_arr );
$_crew[ 'first_name' ] = $_crew_name_arr[0];
$_crew[ 'last_name' ] = $_crew_name_arr[1];


?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=010">
    </head>
    <body>

        <form class = "form_class" method="post" action="account_crew_data_update.php">

            <input class = "hidden_class" type="text" id="crew_key" name="crew_key" value="<?php echo $_crew[ 'key' ]; ?>">
            <input class = "hidden_class" type="text" id="first_name" name="first_name" value="<?php echo $_crew[ 'first_name' ]; ?>">
            <input class = "hidden_class" type="text" id="last_name" name="last_name" value="<?php echo $_crew[ 'last_name' ]; ?>">

            <label class = "label_class" for="email_address">Email address:</label>
            <input class = "text_class"  type="email" id="email_address" name="email_address" value="<?php echo $_crew[ 'email_address' ]; ?>"required></br>

            <label class = "label_class" for="membership_number">Membership number:</label>
            <input class = "text_class" type="text" id="membership_number" name="membership_number" value="<?php echo $_crew[ 'membership_number' ]; ?>"></br>

            <label class = "label_class" for="skill">Skill:</label>
            <select class = "select_class" name="skill" id="skill">
                <option value="0" <?php if($_crew[ 'skill' ] == '0') echo 'selected'; ?>>New</option>
                <option value="1" <?php if($_crew[ 'skill' ] == '1') echo 'selected'; ?>>Crew</option>
                <option value="2" <?php if($_crew[ 'skill' ] == '2') echo 'selected'; ?>>Skipper</option>
            </select></br>

            <label class = "label_class" for="experience">Experience:</label>
            <textarea class = "textarea_class" name="experience" id="experience" rows="10"><?php echo $_crew[ 'experience' ]; ?></textarea></br>

            <input class = "button_class" type="submit" value="Next"> 

        </form>

    </body>
</html>
