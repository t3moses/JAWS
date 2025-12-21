<?php

use nsc\sdc\name as name;
use nsc\sdc\crew as crew;
use nsc\sdc\squad as squad;
use nsc\sdc\config\rank as rank;

// Prevent caching of this page

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/Libraries/Name/src/Name.php';
require_once __DIR__ . '/Libraries/Crew/src/Crew.php';
require_once __DIR__ . '/Libraries/Squad/src/Squad.php';
require_once __DIR__ . '/Libraries/Config/src/Rank.php';

function crew_from_post() {

    // Make the $_crew object from the posted crew data.

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $_crew = new crew\Crew();
        $_crew->set_key( name\safe($_POST['crew_key']) ?? '' );
        $_crew->set_first_name( name\safe($_POST['first_name']) ?? '' );
        $_crew->set_last_name( name\safe($_POST['last_name']) ?? '' );
        $_crew->set_email( name\safe($_POST['email']) ?? '' );
        $_crew->set_membership_number( name\safe($_POST['membership_number']) ?? '');
        $_crew->set_skill( name\safe($_POST['skill']) ?? '' );
        $_crew->set_experience( name\safe($_POST['experience']) ?? '' );
        
        return $_crew;
    }
}

$_squad = new squad\Squad();

$_crew = crew_from_post();
$_crew->set_display_name( name\display_name_from_strings( $_crew->get_first_name(), $_crew->get_last_name()));
$_crew->set_partner_key( '' );
if( $_crew->is_member()) {
    $_crew->set_rank( rank\Rank::CREW_RANK_MEMBERSHIP_DIMENSION, rank\Rank::MEMBER );
}
else {
    $_crew->set_rank( rank\Rank::CREW_RANK_MEMBERSHIP_DIMENSION, rank\Rank::NON_MEMBER );
}
if( $_crew->is_flex()) {
    $_flex = true;
    $_crew->set_rank( rank\Rank::CREW_RANK_FLEXIBILITY_DIMENSION, rank\Rank::FLEXIBLE );
}
else {
    $_flex = false;
    $_crew->set_rank( rank\Rank::CREW_RANK_FLEXIBILITY_DIMENSION, rank\Rank::INFLEXIBLE );
}
$_crew->set_rank( rank\Rank::CREW_RANK_COMMITMENT_DIMENSION, rank\Rank::UNAVAILABLE );
$_crew->set_rank( rank\Rank::CREW_RANK_ABSENCE_DIMENSION , 0 );
$_crew->set_all_available( rank\Rank::UNAVAILABLE );
$_crew->set_all_history( '' );
$_crew->update_whitelist();

$_squad->set_crew( $_crew );
$_squad->save();


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
            <p class = "p_class" >Username: <?php echo $_crew->get_display_name(); ?></p></br>
        </div>
        <div>
            
            <p class = "p_class" ><?php if ( $_flex ) { echo 'You are also registered as a boat owner'; } ?></p></br>

            <p class = "p_class" >Email address: <?php echo $_crew->get_email(); ?></p></br>
            <p class = "p_class" >Membership number: <?php echo $_crew->get_membership_number(); ?></p></br>
            <p class = "p_class" >Skill: <?php echo $_crew->get_skill(); ?></p></br>
            <p class = "p_class" >Experience: <?php echo name\unsafe( $_crew->get_experience()); ?></p></br>
        </div>
        <div>
            <button class = "button_class" type="button" onclick="window.location.href='/account_crew_availability_form.php?ckey=<?php echo $_crew->get_key()?>'">Next</button>
        </div>
    </body>
</html>
