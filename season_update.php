<?php

use nsc\sdc\fleet as fleet;
use nsc\sdc\squad as squad;
use nsc\sdc\season as season;
use nsc\sdc\select as select;
use nsc\sdc\event as event;
use nsc\sdc\html as html;

require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Selection/src/Selection.php';
require_once __DIR__ . '/Libraries/Event/src/Event.php';
require_once __DIR__ . '/Libraries/Html/src/Html.php';

$_fleet = new fleet\Fleet();
$_squad = new squad\Squad();
$_season = new season\Season();
$_select = new select\Selection();
$_event = new event\Event();

const BOAT_RANK_FLEXIBILITY_DIMENSION = 0;
const BOAT_RANK_ABSENCE_DIMENSION = 1;
const CREW_RANK_COMMITMENT_DIMENSION = 0;
const CREW_RANK_FLEXIBILITY_DIMENSION = 1;
const CREW_RANK_MEMBERSHIP_DIMENSION = 2;
const CREW_RANK_ABSENCE_DIMENSION = 3;
const FLEXIBLE = 0;
const INFLEXIBLE = 1;
const NON_MEMBER = 0;
const MEMBER = 1;
const UNAVAILABLE = 0; // Commitment values
const NO_SHOW = 1;
const AVAILABLE = 2;
const GUARANTEED = 3;

$_event_ids = $_season->get_future_events( );

foreach( $_event_ids as $_event_id ) {

    $_select->select( $_fleet, $_squad, $_event_id  );

    $_selected_boats = $_select->get_selected_boats();
    $_selected_crews = $_select->get_selected_crews();
    $_waitlist_crews = $_select->get_waitlist_crews();

    $_event->set_event_id( $_event_id );
    $_event->set_selected_boats( $_selected_boats );
    $_event->set_selected_crews( $_selected_crews );
    $_event->set_waitlist_crews( $_waitlist_crews );

    $_flotilla = $_event->get_flotilla();

    if ( $_event_id === $_season->get_next_event() ) {

        $_squad->update_availability( $_selected_crews, GUARANTEED );
        $_squad->update_availability( $_waitlist_crews, AVAILABLE );

        $_fleet->update_history( $_event_id, $_flotilla );
        $_squad->update_history( $_event_id, $_flotilla );

    }

    $_season->set_flotilla( $_event_id, $_flotilla );

    $_fleet->save();
    $_squad->save();

}

$_flotillas = $_season->get_flotillas( );
html\save( $_flotillas );
header( "Location: /Libraries/Html/data/page.html" );
exit;


?>
