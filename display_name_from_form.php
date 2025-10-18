<?php

function display_name_from_form() {

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $fname = $_POST['fname'] ?? '';
        $lname = $_POST['lname'] ?? '';
        
        // Validate the data
        if (empty($fname) || empty($lname)) {
            return "All fields are required.";
        }
        
        // Sanitize data to prevent security issues
        $fname = htmlspecialchars($fname);
        $lname = htmlspecialchars($lname);
        
        return ($fname . " " . $lname);
    }
}

$_display_name = display_name_from_form();

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Display Name From Form</title>
    </head>
    <body>
        <p>Result: <?php echo $_display_name; ?></p>
    </body>
</html>
