<?php

/*
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
*/

function replace_csv_row( $_new_row, $_filename ){

/*

Replace the row in the csv file that corresponds to the new row.

*/

    // Start by separating the new row into a key and a list of values.
    // Then get the file from the database, and convert it into a list of strings.
    // The first row in the list is the header row.
    $_new_key = array_shift( $_new_row ); // $_new_row is now the csv row without a key.
    $_db_str = str_from_file( $_filename );
    $_db_lst_str = explode( "\n", $_db_str );
    $_header_str = array_shift( $_db_lst_str ); // $_db_lst_str is now the csv rows without a header row.
    $_updated_lst_str = [];

    // Now traverse the list to find the row that matches the new row.

    foreach( $_db_lst_str as $_db_str  ) {
        $_db_lst =  explode( ',', $_db_str ); // $_db_lst is the key plus list of values
        $_db_key = array_shift( $_db_lst ); // $_db_lst is now the list of values without the key
        $_number_of_values = count( $_db_lst );
        $_updated_column_lst = [];
        if ( $_db_key === $_new_key ) {
            for ( $_column_index = 0; $_column_index < $_number_of_values; $_column_index++ ) {
                if ( $_new_row[ $_column_index ] === "No" && $_db_lst[ $_column_index ] !== 'N' ) {
                    $_updated_column_lst[ $_column_index ] = 'U';
                }
                elseif ( $_db_lst[ $_column_index ] === 'U') {
                    $_updated_column_lst[ $_column_index ] = 'A';
                }
                else {
                    $_updated_column_lst[ $_column_index ] = $_db_lst[ $_column_index ];
                }
            }        
        }
        else { // This row corresponds to a row other than the new row
            $_updated_column_lst = $_db_lst;
        }
        array_unshift( $_updated_column_lst, $_db_key );
        $_updated_column_str = implode( ',', $_updated_column_lst );
        $_updated_lst_str[] = $_updated_column_str; // Add to the list
        }
    array_unshift( $_updated_lst_str, $_header_str );
    $_updated_str = implode( "\n", $_updated_lst_str );
    file_from_str( $_filename, $_updated_str );
    }

?>
