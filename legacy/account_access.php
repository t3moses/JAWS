<?php

use nsc\sdc\season as season;

require_once __DIR__ . '/Libraries/Season/src/Season.php';

season\Season::load_season_data();
$_event_ids = season\Season::get_event_ids();
$_blackout_from = season\Season::get_blackout_from();
$_blackout_to = season\Season::get_blackout_to();

// If today is an event day and the time is between blackout_from and 
// blackout_to, then $_blackout = true, else $_blackout = false.

$_blackout = in_array( date('D M j'), $_event_ids ) &&
    time() > $_blackout_from &&
    time() < $_blackout_to;

if ( $_blackout ) {
    header("Location: /account_locked.php");
    exit;
} else {
    header("Location: /account.html");
    exit;
}

?>