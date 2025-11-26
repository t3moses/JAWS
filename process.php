<?php

    // Prevent caching of this page

    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    function set_now() {

        // Return the current Unix time.
        // In 'test' mode, return a fixed time from config/admin.php.

        $_admin = require './config/admin.php';

        if ( $_admin['mode'] === 'live' ) {

            $_now = time();

        }
        else {

            $_now = strtotime( $_admin['datetime']['month'].
            $_admin['datetime']['day'].
            $_admin['datetime']['hour'].
            $_admin['datetime']['min'] );

        }

        // echo date('m-d H:i', set_now());

        return $_now;
    }

    function time_from_event_id( $_event_id, $_year ) {
        
        // Return the Unix time for the event identified by $_event_id in the year $_year.

        return strtotime( $_year . $_event_id );

    }

    function future_evernts_from_event_list( $_event_id_list, $_now ) {

        // Return a list of future events from the given list of event IDs, based on $_now.

        $_future_events = [];

        $_year = date('Y', $_now );

        foreach ( $_event_id_list as $_event_id ) {

            $_event_time = time_from_event_id( $_event_id, $_year );

            if ( $_event_time < $_now ) {

                $_event_time = time_from_event_id( $_event_id, $_year + 1 );

            }

            if ( $_event_time > $_now ) {

                $_future_events[] = [
                    'event_id' => $_event_id,
                    'event_time' => $_event_time
                ];

            }

        }

        return $_future_events;

    }

/*

Get the Unix time for all events.
Create a list of future events, based on $_now.
Create a list of lists of available boats for those events.
Create a list of lists of available crews for those events.
Set the occupancy of each boat that will participate in each event.
Choose the crews that will participate in each event.
Order crews for each event by mandatory rules.
Establish the cut-off point for boats and crews for each event.
Order crews by discretionay rules.
Assign crews to boats.
Build the calendar.

*/

?>