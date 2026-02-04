<?php

namespace nsc\sdc\html;

include __DIR__ . "/header.php";

function get_max_crew_count( $_flotilla ) {
    $_max_crew_count = 0;
    foreach( $_flotilla[ 'crewed_boats' ] as $_crewed_boat ) {
        $_crew_count = count( $_crewed_boat[ 'crews' ]);
        $_max_crew_count = max( $_crew_count, $_max_crew_count );
        }

    return $_max_crew_count;
}

function encode_top() {

    $_top = '';
    $_top .= "<!DOCTYPE html><html><head>";
    $_top .= "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    $_top .= "<link rel='stylesheet' href='/../../../css/styles.css?v=017'></head>";
    $_top .= "<body>";
    $_top .= header_img();

    return $_top;
}

function encode_tail() {

    $_tail = '';
    $_tail .= "</body></html>";

    return $_tail;
}

function encode_flotilla( $_flotilla ) {

    $_max_crew_count = get_max_crew_count( $_flotilla );
    $_table_column_count = $_max_crew_count + 1;
    $_table_width = 100;
    $_column_width = ceil( $_table_width / $_table_column_count);

    $_body = "<h2>" . "Date: " . $_flotilla[ 'event_id' ] . "</h2>";

    $_body .= "<table class = 'table_class' width = " . $_table_width . "%><tr>";
    $_body .= "<th class = 'th_class' width = " . $_column_width . "%>Boat</th>";
    $_body .= "<th class = 'th_class' colspan = $_table_column_count - 1>Crew</th>";
    $_body .= "</tr>";

    foreach ( $_flotilla[ 'crewed_boats' ] as $_crewed_boat ) { // for each boat in the flotilla
        $_body .= "<tr><td class = 'td_class' >" . $_crewed_boat[ 'boat' ]->display_name . "</td>";
        foreach ( $_crewed_boat[ 'crews' ] as $_crew ) {
            $_body .= "<td class = 'td_class' >" . $_crew->display_name . "</td>";
        }
        $_empty_cells = $_max_crew_count - count( $_crewed_boat[ 'crews' ]);
        for ( $i = 0; $i < $_empty_cells; $i++ ) {
            $_body .= "<td class = 'td_class' >" . "" . "</td>";
        }
        $_body .= "</tr>";
    }
    $_body .= "</table>";

    $_waitlist_row_count = ceil( count( $_flotilla[ 'waitlist' ] ) / $_table_column_count );
    if ( $_waitlist_row_count != 0 ) {
        $_body .= "<h2>" . "Waitlist" . "</h2>";
        $_body .= "<table class = 'table_class' width = " . $_table_width . "%>";
        for ( $_row = 0; $_row < $_waitlist_row_count; $_row++ ) {
            $_body .= "<tr>";
            for( $_column = 0; $_column < $_table_column_count; $_column++ ) {
                $_cell = $_table_column_count * $_row + $_column;
                if ( $_cell < count( $_flotilla[ 'waitlist' ])) {
                    $_body .= "<td class = 'td_class' width = " . $_column_width . "%>" . $_flotilla[ 'waitlist' ][ $_cell ]->display_name . "</td>";
                } else {
                    $_body .= "<td class = 'td_class' width = " . $_column_width . "%></td>";
                }
            }
            $_body .= "</tr>";
        }
        $_body .= "</table>";
    }
    return $_body;
}

function encode_body( $_flotillas ) {

    $_body = '';
    foreach( $_flotillas as $_flotilla ) {
        $_body .= encode_flotilla( $_flotilla );
    }
    return $_body;
}

function save( $_flotillas ) {

    $_html_contents = encode_top() . encode_body( $_flotillas ) . encode_tail();
    $_filename = __DIR__ . '/../data/page.html';
    file_put_contents( $_filename, $_html_contents );

}

?>
