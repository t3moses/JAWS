<?php

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Crew/src/Crew.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';

/*

ARRIVE HERE ONLY IF THE CREW MEMBER DOES NOT CURRENTLY HAVE AN ACCOUNT.

*/

function crew_name_array_from_get_url() {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Retrieve form data
        $_cname[ 'fname' ] = $_GET['fname'] ?? '';
        $_cname[ 'lname' ] = $_GET['lname'] ?? '';

        // Validate the data
        if (empty( $_cname[ 'fname' ]) && empty( $_cname[ 'lname' ])) {
            return null;
        }
        else {
            return $_cname;
        }
    }
}

$_user_crew_name = crew_name_array_from_get_url();

$_user_crew_key = key_from_strings( $_user_crew_name[ 'fname' ], $_user_crew_name[ 'lname' ] );
$_display_name = display_name_from_strings( $_user_crew_name[ 'fname' ], $_user_crew_name[ 'lname' ] );
$_crew = new Crew();
$_crew->set_default();
$_crew->set_key( $_user_crew_key );
$_crew->set_first_name( $_user_crew_name[ 'fname' ] );
$_crew->set_last_name( $_user_crew_name[ 'lname' ] );

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=010">
    </head>
    <body>

        <form class = "form_class" method="post" action="account_crew_data_update.php">

            <input class = "hidden_class" type="text" id="crew_key" name="crew_key" value="<?php echo $_crew->get_key(); ?>">
            <input class = "hidden_class" type="text" id="first_name" name="first_name" value="<?php echo $_crew->get_first_name(); ?>">
            <input class = "hidden_class" type="text" id="last_name" name="last_name" value="<?php echo $_crew->get_last_name(); ?>">

            <label class = "label_class" for="email">Email address:</label>
            <input class = "text_class"  type="email" id="email" name="email" value="<?php echo $_crew->get_email(); ?>"required></br>

            <label class = "label_class" for="membership_number">Membership number:</label>
            <input class = "text_class" type="text" id="membership_number" name="membership_number" value="<?php echo $_crew->get_membership_number(); ?>"></br>

            <label class = "label_class" for="skill">Skill:</label>
            <select class = "select_class" name="skill" id="skill">
                <option value="0" <?php if($_crew->get_skill() == '0') echo 'selected'; ?>>New</option>
                <option value="1" <?php if($_crew->get_skill() == '1') echo 'selected'; ?>>Crew</option>
                <option value="2" <?php if($_crew->get_skill() == '2') echo 'selected'; ?>>Skipper</option>
            </select></br>

            <label class = "label_class" for="experience">Experience:</label>
            <textarea class = "textarea_class" name="experience" id="experience" rows="10"><?php echo $_crew->get_experience(); ?></textarea></br>

            <input class = "button_class" type="submit" value="Next"> 

        </form>

    </body>
</html>
