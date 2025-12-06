<?php

namespace nsc\sdc\name;

    function safe( $_raw_input ) {

        $_special = htmlspecialchars( $_raw_input );

        $_safe_input = '';
        for( $_i = 0; $_i < strlen( $_raw_input ); $_i++ ) {
            if ( $_raw_input[ $_i ] === ',' ) {
                $_safe_input .= '+u002C';
                }
            elseif ( $_raw_input[ $_i ] === "\x0D" ) {
                $_safe_input .= '+u2028';
            }
            elseif ( $_raw_input[ $_i ] === "\x0A" ) {
                // Omit the character.
            }
            else {
                $_safe_input .= $_raw_input[ $_i ] ;
            }
        }        

        return $_safe_input;
    }

    function unsafe( $_safe_input ){

        $_raw_input = str_replace( '+u002C', ',', $_safe_input );
        $_raw_input = str_replace( '+u2028', "\x0D\x0A", $_raw_input );
        return $_raw_input;

    }

    function key_from_string( $_string ) {

        // Create a sanitized database key from a string.

        return strtolower( safe( preg_replace('/\s+/', '', $_string )));
    }

    function key_from_strings( $_fname, $_lname ) {

        // Create a database key from the first two elements of the string array.
    
        return strtolower( preg_replace('/\s+/', '', safe( $_fname )) . 
        preg_replace('/\s+/', '', safe( $_lname)));
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


?>
