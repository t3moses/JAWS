<?php

function csvToAssociativeArray($filename) {

// Claude wrote this.

    $data = [];
    
    if (($handle = fopen($filename, 'r')) !== false) {
        // Read the header row
        $header = fgetcsv($handle, 0, ',','"', '\\');
        
        // Read each data row
        while (($row = fgetcsv($handle, 0, ',','"', '\\')) !== false) {
            // Combine header with row values
            $data[] = array_combine($header, $row);
        }
        
        fclose($handle);
    }
 
    return $data;
}



function associativeArrayToCsv($data, $filename) {

// Claude wrote this.

    if (empty($data)) {
        return false;
    }
    
    $handle = fopen($filename, 'w');
    
    // Write header row from array keys
    fputcsv($handle, array_keys($data[0]), ',', '"', '\\');
    
    // Write data rows
    foreach ($data as $row) {
        fputcsv($handle, $row, ',', '"', '\\');
    }
    
    fclose($handle);
    return true;
}


function str_from_file( $_file_type ) {

/*

    Return the csv file as a string.

    $_file_type must be one of the following values ...
    'crews_data_file'
    'crews_availability_file'
    'boats_data_file'
    'boats_availability_file'
    'crews_history_file'

*/

    $_config = require './config/config.php'; // config.php is an associative array
    $_filename = $_config['file'][ $_file_type ]; // $_data is a csv file
    $_data_str = file_get_contents( $_filename  );
    return $_data_str;

}


function file_from_str( $_file_type, $_data_str ) {

/*

    Write the string to the file.

    $_file_type must be one of the following values ...
    'crews_data_file'
    'crews_availability_file'
    'boats_data_file'
    'boats_availability_file'
    'crews_history_file'

*/

    $_config = require './config/config.php'; // config.php is an associative array
    $_filename = $_config['file'][ $_file_type ]; // $_data is a csv file
    file_put_contents( $_filename, $_data_str );
    return true;

}


function lst_asa_from_file( $_file_type ) {

/*

    Retrieve the csv file, convert it to a list of associative arrays and return the result

    $_file_type must be one of the following values ...
    'crews_data_file'
    'crews_availability_file'
    'boats_data_file'
    'boats_availability_file'
    'crews_history_file'

*/

    $_config = require './config/config.php'; // config.php is an associative array
    $_filename = $_config['file'][ $_file_type ]; // $_data is a csv file
    $_data_lst_asa = csvToAssociativeArray( $_filename ); // $_data_lst_asa is a list of associative arrays
    return $_data_lst_asa;

}


function file_from_lst_asa( $_file_type, $_data_lst_asa ) {

/*

    Convert the data, which is a list of associative arrays, to csv and store it to the file,
    replacing any existing file of that type.

    $_file_type must be one of the following values ...
    'crews_data_file'
    'crews_availability_file'
    'boats_data_file'
    'boats_availability_file'
    'crews_history_file'

*/

    $_config = require './config/config.php'; // config.php is an associative array
    $_filename = $_config['file'][ $_file_type ]; // $_filename identifies a csv file
    associativeArrayToCsv( $_data_lst_asa, $_filename ); // $_data_lst_asa is a list of associative arrays
    return true;

}


function event_ids() {

    $_config = require './config/config.php'; // config.php is an associative array
    return $_config[ 'event_id' ];

}


function number_of_events() {

    $_config = require './config/config.php'; // config.php is an associative array
    return count( $_config[ 'event_id' ] );

}


function asa_from_default_boat(  ) {

// Return the default boat data as an associative array.

    $_config = require './config/config.php'; // config.php is an associative array
    return $_config[ 'default_boat' ];

}


function asa_from_default_crew(  ) {

// Return the default crew data as an associative array.

    $_config = require './config/config.php'; // config.php is an associative array
    return $_config[ 'default_crew' ];

}


?>
