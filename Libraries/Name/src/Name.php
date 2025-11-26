<?php

    function csv_safe( $_unsafe_string ) {

        // Remove commas and newlines from the string.

        $_safe_string = '';
        for( $_index = 0; $_index < strlen( $_unsafe_string ); $_index++ ) {
            if ( $_unsafe_string[ $_index ] === ',' ) {
                $_safe_string .= '+u002C';
                }
            elseif ( $_unsafe_string[ $_index ] === "\n" ) {
                $_safe_string .= '+u2028';
            }
            else {
                $_safe_string .= $_unsafe_string[ $_index ] ;
            }
        }
        return $_safe_string;
    }

    function user_safe( $_user_unsafe ) {

        // Remove unsafe characters from the string.

        return htmlspecialchars( $_user_unsafe );
    }

    function key_from_string( $_string ) {

        // Create a sanitized database key from a string.

        return strtolower( csv_safe( preg_replace('/\s+/', '', user_safe ( $_string ))));
    }

    function key_from_strings( $_fname, $_lname ) {

        // Create a database key from the first two elements of the string array.
    
        return strtolower( preg_replace('/\s+/', '', csv_safe( user_safe( $_fname,))) . 
        preg_replace('/\s+/', '', csv_safe( user_safe( $_lname,))));
    }

    function display_name_from_string( $_string ) {
        // Create a display name from the name.
        $_display_name = ucfirst( strtolower( str_replace(' ', '', $_string )));
        return $_display_name;
    }

    function display_name_from_strings( $_first_name, $_last_name ) : string {
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
