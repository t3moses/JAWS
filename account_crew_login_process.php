<?php

require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Authn/src/Authn.php';

use nsc\sdc\name as name;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $password = $_POST['password'];
}
// Convert fname and lname to key.
// loginUser is a function defined in auth.php

    $_crew_key = name\key_from_strings( $fname, $lname );

    if (loginUser($_crew_key, $password, 'crew')) {
        header('Location: /account_crew_availability_form.php');
    } else { // No such account ... try again.
        header('Location: /account_crew_login.php?state=1');
    }

?>