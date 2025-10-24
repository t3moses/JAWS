<?php

function key_from_name( $_name ) {

// Create a sanitized database key from a name.

    return trim( strtolower( htmlspecialchars( $_name )));
}

?>