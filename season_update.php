<?php

use nsc\sdc\season as season;
use nsc\sdc\select as select;
use nsc\sdc\event as event;
use nsc\sdc\html as html;

require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Selection/src/Selection.php';
require_once __DIR__ . '/Libraries/Event/src/Event.php';
require_once __DIR__ . '/Libraries/Html/src/Html.php';

$_season = new season\Season();
$_select = new select\Selection();
$_event = new event\Event();

$_event_ids = $_season->get_event_ids();

foreach( $_event_ids as $_event_id ) {

    $_select->select( $_event_id );

    $_selected_boats = $_select->get_selected_boats();
    $_selected_crews = $_select->get_selected_crews();
    $_waitlist_crews = $_select->get_waitlist_crews();

    $_event->set_event_id( $_event_id );
    $_event->set_selected_boats( $_selected_boats );
    $_event->set_selected_crews( $_selected_crews );
    $_event->set_waitlist_crews( $_waitlist_crews );

    $_flotilla = $_event->get_flotilla();
    $_season->set_flotilla( $_event_id, $_flotilla );

}

$_flotillas = $_season->get_flotillas( );
html\save( $_flotillas );
header( "Location: /Libraries/Html/data/page.html" );
exit;


?>
