<?php

require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Authn/src/Authn.php';

use nsc\sdc\name as name;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bname = trim($_POST['bname']);
    $password = $_POST['password'];
}

// Convert bname to key.
// loginUser is a function defined in auth.php

    $_boat_key = name\key_from_string( $bname );

    if (loginUser($_boat_key, $password, 'boat')) {
        header('Location: /account_boat_availability_form.php');
    } else { // Login failed.
        header('Location: /account_boat_login.php?state=1');
    }

?>