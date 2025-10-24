<?php

function index_from_array( $_array, $_element ) {
    // Return the index of the element in the array, or -1 if not found.
    foreach ( $_array as $_index => $_item ) {
        if ( $_item === $_element ) {
            return $_index;
        }
    }
    return -1;
}

function index_from_row( $_row, $_element ) {
    // Return the index of the element in the row.
    $_array = str_getcsv( $_row );
    $_index = index_from_array( $_array, $_element );
    return $_index;
}

function array_from_key( $_arrays, $_key, $_key_index ) {
    // Return the array from arrays that corresponds to the key.
    foreach( $_arrays as $_array ){
        if ( $_array[ $_key_index ] === $_key ) {
            return $_array;
        }
    }
    return -1;
}

?>
