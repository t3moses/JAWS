<?php

use nsc\sdc\season as season;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Season/src/Season.php';


season\Season::load_season_data();

$_event_ids = season\Season::get_future_events();

?>

<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/css/styles.css?v=14">
    </head>
    <body>
        <div>
            <a href='/index.html'>
                <img src='/Libraries/Html/data/NSC-SDC_logo.png' alt='Program page' width = '100'>
            </a>
        </div>
        <div>
            <h2>
                Registration opens 2026 April 1.
            </h2>
            <p class = "p_class" >
                Save the dates:
            </p>
        </div>
<!--

Loop through the list of events, displaying the event id.

-->
    <?php for ( $i = 0; $i < count($_event_ids); $i++ ) { ?>
        <div class='flex-container'>
            <div class='column'><p class = "p_class" > <?php echo $_event_ids[ $i ]; ?></p></div>
        </div>
    <?php } ?>
    </body>
</html>
