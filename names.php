<?php


    function key_from_name( $_name ) {
        // Create a sanitized database key from a name.
        return trim( strtolower( htmlspecialchars( $_name )));
    }

    function key_from_names( $_name_arr ) {
        // Create a database key from the first and last names entered by the user.
        return strtolower( $_name_arr[ 0 ] . $_name_arr[ 1 ]);
    }

    function subject_attribute_from_file( $_subject_key, $_attribute_name, $_file ) {

    // Convert the file to an array of strings.
    // Convert the first string to an array (this is the header row).
    // Find the array index of the requested attribute.

        $_file_arr_str = explode( "\n", $_file );
        $_header_str = $_file_arr_str[ 0 ];
        $_header_arr = explode( ',', $_header_str );
        $_attribute_index = array_search( $_attribute_name, $_header_arr );

    // Traverse the rows of the array looking for the one that corespomds to the subject.
    // Do this by converting each row to an array of attibute values (the first attribute is the subject key).
    // Then return the value of the requested attribute.
    // If the subject is not represented in the file, return  null.

        foreach ( $_file_arr_str as $_file_str ) {
            $_attribute_arr = explode( ',', $_file_str );
            if ( $_attribute_arr[ 0 ] === $_subject_key ) {
                return $_attribute_arr[ $_attribute_index ];
            }
        }
        return null;
    }

    function display_name_from_names( $_first_name, $_last_name ) {
        // Create a display name from first and last names.
        $_first_part = ucfirst( strtolower( str_replace(' ', '', $_first_name )));
        $_second_part = strtoupper( str_replace(' ', '', $_last_name )[0]);
        return $_first_part . $_second_part;
    }
    
?>
