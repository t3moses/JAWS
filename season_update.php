<?php

use nsc\sdc\fleet as fleet;
use nsc\sdc\squad as squad;
use nsc\sdc\season as season;
use nsc\sdc\select as select;
use nsc\sdc\event as event;
use nsc\sdc\config\rank as rank;
use nsc\sdc\html as html;
use nsc\sdc\assignment as assign;

require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Selection/src/Selection.php';
require_once __DIR__ . '/Libraries/Event/src/Event.php';
require_once __DIR__ . '/Libraries/Config/src/Rank.php';
require_once __DIR__ . '/Libraries/Html/src/Html.php';
require_once __DIR__ . '/Libraries/Assignment/src/Assignment.php';

$_fleet = new fleet\Fleet();
$_squad = new squad\Squad();
$_season = new season\Season();
$_select = new select\Selection();
$_event = new event\Event();
$_assign = new assign\Assignment();

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

        $_squad->update_availability( $_selected_crews, rank\Rank::GUARANTEED );
        $_squad->update_availability( $_waitlist_crews, rank\Rank::AVAILABLE );

        $_fleet->update_history( $_event_id, $_flotilla );
        $_squad->update_history( $_event_id, $_flotilla );

        $_flotilla = $_assign->assign( $_flotilla );

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
