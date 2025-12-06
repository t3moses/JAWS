<?php

$_filename = __DIR__ . '/../data/config.json';
$_config = file_get_contents( $_filename  );
$_config_arr = json_decode( $_config, true );

echo $_config_arr[ 'config' ][ 'time' ];

?>