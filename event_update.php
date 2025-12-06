<?php

use nsc\sdc\season as season;
use nsc\sdc\fleet as fleet;
use nsc\sdc\squad as squad;
use nsc\sdc\select as select;
use nsc\sdc\event as event;

require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Selection/src/Selection.php';
require_once __DIR__ . '/Libraries/Event/src/Event.php';

$_season = new season\Season();
$_fleet = new fleet\Fleet();
$_squad = new squad\Squad();
$_select = new select\Selection();
$_event = new event\Event();

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

    $_select->select( $_event_id );

    $_selected_boats = $_select->get_selected_boats();
    $_selected_crews = $_select->get_selected_crews();
    $_waitlist_crews = $_select->get_waitlist_crews();
/*
    $_filename = __DIR__ . '/debug.txt';
    file_put_contents( $_filename, count( $_waitlist_crews ));
*/
    $_event->set_event_id( $_event_id );
    $_event->set_selected_boats( $_selected_boats );
    $_event->set_selected_crews( $_selected_crews );
    $_event->set_waitlist_crews( $_waitlist_crews );

    $_flotilla = $_event->get_flotilla();

    $_season->set_flotilla( $_event_id, $_flotilla );
    $_flotillas = $_season->get_flotillas( );

    header( "Location: /Libraries/Html/data/page.html" );
    exit;

?>
