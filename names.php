<?php

    function key_from_name( $_name ) {
        // Create a sanitized database key from a name.
        return strtolower( htmlspecialchars(preg_replace('/\s+/', '', $_name)));
    }

    function key_from_names( $_name_arr ) {
        // Create a database key from the first and last names entered by the user.
        return strtolower( htmlspecialchars(trim($_name_arr[ 0 ]) . trim($_name_arr[ 1 ])));
    }

    function display_name_from_names( $_first_name, $_last_name ) {
        // Create a display name from first and last names.
        $_first_part = ucfirst( strtolower( str_replace(' ', '', $_first_name )));
        $_second_part = strtoupper( str_replace(' ', '', $_last_name )[0]);
        return $_first_part . $_second_part;
    }

    function subject_attribute_from_file( $_subject_key, $_attribute_name, $_lst_asa ) {

    // $_lst_asa is a list of associative arrays.
    // Find the list entry whose key matches $_subject_key.
    // And return the requested attribute.

        $_asa_exists = false;
        foreach ( $_lst_asa as $_asa ) {
            if ( $_asa[ 'key' ] === $_subject_key ) {
                return $_asa[ $_attribute_name ];
            }
        }
        return $_asa_exists;
    }

?>
