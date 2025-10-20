<?php

function key_from_name() {

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
        
        return (strtolower($fname) . strtolower($lname));
    }
}


$handle = fopen("dbfile.txt", 'w');
if ($handle) {
    $_database_key = key_from_name();
    fwrite($handle, $_database_key);
    fclose($handle);
}

?>

<!DOCTYPE html>
<html>
    <head>
    </head>
    <body>
        <p>Thank you for registering</p>
        <p>Your database key is <?php echo $_database_key; ?></p>
    </body>
</html>
