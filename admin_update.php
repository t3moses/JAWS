<?php

use nsc\sdc\season as season;

require_once __DIR__ . '/Libraries/Season/src/Season.php';

$_season = new season\Season();

    function date_from_post() {

        // Make the $_crew object from the posted crew data.

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $_date[ 'source' ] = $_POST['source'] ?? '';
            $_date[ 'year' ] = $_POST['year'] ?? '';
            $_date[ 'month' ] = $_POST['month'] ?? '';
            $_date[ 'day' ] = $_POST['day'] ?? '';
            
            return $_date;
        }
    }

    $_date = date_from_post();

    if ( $_date[ 'source' ] === "simulated" ) {
        $_utime = strtotime( $_date[ 'year' ] . '-' . $_date[ 'month' ] . '-' .  $_date[ 'day' ]);
        $_season->set_time( $_utime );
    }

    $_event_id = $_season->get_next_event();

    header( "Location: /program.html" );
    exit;

?>
