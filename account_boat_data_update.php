<?php

use nsc\sdc\name as name;
use nsc\sdc\boat as boat;
use nsc\sdc\fleet as fleet;
use nsc\sdc\season as season;
use nsc\sdc\config\rank as rank;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


require_once __DIR__ . '/Libraries/Fleet/src/Fleet.php';
require_once __DIR__ . '/Libraries/Boat/src/Boat.php';
require_once __DIR__ . '/Libraries/Season/src/Season.php';
require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Config/src/Rank.php';


function boat_from_post() {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $_boat = new boat\Boat( );
        $_boat->set_key( name\safe($_POST['boat_key']) ?? '' );
        $_boat->set_owner_first_name( name\safe($_POST['owner_first_name']) ?? '' );
        $_boat->set_owner_last_name( name\safe($_POST['owner_last_name']) ?? '' );
        $_boat->set_display_name( name\safe($_POST['display_name']) ?? '' );
        $_boat->set_owner_email( name\safe($_POST['owner_email']) ?? '' );
        $_boat->set_owner_mobile( name\safe($_POST['owner_mobile']) ?? '' );
        $_boat->set_min_berths( name\safe($_POST['min_berths']) ?? '' );
        $_boat->set_max_berths( name\safe($_POST['max_berths']) ?? '' );
        $_boat->set_assistance_required( name\safe($_POST['assistance_required']) ?? '');
        $_boat->set_social_preference( name\safe($_POST['social_preference']) ?? '');
        
        return $_boat;
    }
}

$_season = new season\Season();
$_fleet = new fleet\Fleet();

$_boat = boat_from_post();
$_number_of_events = $_season->get_event_count();
$_event_ids = $_season->get_event_ids();
if( $_boat->is_flex() ) {
    $_flex = true;
    $_boat->set_rank( rank\Rank::BOAT_RANK_FLEXIBILITY_DIMENSION, rank\Rank::FLEXIBLE );
}
else {
    $_flex = false;
    $_boat->set_rank( rank\Rank::BOAT_RANK_FLEXIBILITY_DIMENSION, rank\Rank::INFLEXIBLE );
}
$_boat->set_all_berths( $_boat->get_max_berths() );
$_boat->set_all_history( '' );
$_boat->update_absence_rank();
$_boat->update_whitelist();

$_fleet->set_boat( $_boat );
$_fleet->save();

?>


<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/styles.css?v=004">
    </head>
    <body>
        <div>
            <a href='/../../../program.html'>
                <img src='/./Libraries/Html/data/NSC-SDC_logo.png' alt='Program page' width = '100'>
            </a>
        </div>
        <div>
            <p class = "p_class" ><?php echo $_boat->get_display_name() ?>'s account has been created</p>
        </div>
        <div>

            <p class = "p_class" ><?php if ( $_flex ) { echo 'You are also registered as a crew member'; } ?></p></br>

            <p class = "p_class" >Owner first name: <?php echo $_boat->get_owner_first_name() ?></p></br>
            <p class = "p_class" >Owner last name: <?php echo $_boat->get_owner_last_name() ?></p></br>
            <p class = "p_class" >Boat name: <?php echo $_boat->get_display_name()?></p></br>
            <p class = "p_class" >Email address: <?php echo $_boat->get_owner_email() ?></p></br>
            <p class = "p_class" >Mobile number: <?php echo $_boat->get_owner_mobile() ?></p></br>
            <p class = "p_class" >Min berths: <?php echo $_boat->get_min_berths() ?></p></br>
            <p class = "p_class" >Max berths: <?php echo $_boat->get_max_berths() ?></p></br>
            <p class = "p_class" >Assistance: <?php echo $_boat->get_assistance_required() ?></p>
            <p class = "p_class" >Whatsapp: <?php echo $_boat->get_social_preference() ?></p>
        </div>
        <div>
            <button class="button_class" type="button" onclick="window.location.href='/account_boat_availability_form.php?bkey=<?php echo $_boat->key; ?>'">Next</button>
        </div>
    </body>
</html>
