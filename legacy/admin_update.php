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

    $_config_fname = __DIR__ . '/Libraries/Season/data/config.json';
    $_config_file = file_get_contents( $_config_fname );
    $config_data = json_decode( $_config_file, true );

    $config_data[ 'config' ][ 'source' ] = $_date[ 'source' ];
    $config_data[ 'config' ][ 'year' ] = $_date[ 'year' ];

    if ( $_date[ 'source' ] === "simulated" ) {

        $config_data[ 'config' ][ 'month' ] = $_date[ 'month' ];
        $config_data[ 'config' ][ 'day' ] = $_date[ 'day' ];

    }

    file_put_contents( $_config_fname, json_encode( $config_data ));

    header( "Location: /program.html" );

    exit;

?>
