<?php

namespace nsc\sdc\assignment;

enum Rule: int {
    case ASSIST = 0;
    case WHITELIST = 1;
    case SKILL = 2;
    case PARTNER = 3;
    case REPEAT = 4;
}

Class Assignment {

    public $losses = array();
    public $grads = array();
    private $flotilla;

    private const MAX_SKILL = 2;
    private $f;

    public function pretty_print( $_flotilla ) {

        // Print the flotilla assignment in a readable format

        foreach( $_flotilla[ 'crewed_boats' ] as $_crewed_boat ) {

            fwrite( $this->f, "Boat: " . $_crewed_boat[ 'boat' ]->key . " " . $_crewed_boat[ 'boat' ]->assistance_required . "\n" );
            foreach( $_crewed_boat[ 'crews' ] as $_cb_crew ) {
                fwrite( $this->f, "  Crew: " . $_cb_crew->key . " " . $_cb_crew->skill . "\n" );
            }
            fwrite( $this->f, "\n" );

        }

    }

    public function crew_from_key( $_crew_key ) : ?object {

        // Return the crew object for the crew with key $_crew_key

        $_flotilla = $this->flotilla;

        foreach( $_flotilla[ 'crewed_boats' ] as $_crewed_boat ) {
            foreach( $_crewed_boat[ 'crews' ] as $_cb_crew ) {
                if ( $_cb_crew->key === $_crew_key ) {
                    return $_cb_crew;
                }
            }
        }
        return null; // If crew not found in flotilla
    }

    public function crewed_boat_from_key( $_crew_key ) : ?array {

        // Return the crewed boat object for the crew with key $_crew_key

        $_flotilla = $this->flotilla;

        foreach( $_flotilla[ 'crewed_boats' ] as $_crewed_boat ) {
            foreach( $_crewed_boat[ 'crews' ] as $_cb_crew ) {
                if ( $_cb_crew->key === $_crew_key ) {
                    return $_crewed_boat;
                }
            }
        }
        return null; // If crew not found in flotilla
    }
    


    public function crew_loss( $rule, $crew, $crewed_boat ) : int {

        if ( $rule === Rule::ASSIST ) {
            if ( $crewed_boat[ 'boat' ]->assistance_required === 'No' ){
                return 0;
            }
            for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
                $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
                if ( (int)$cb_crew->skill === self::MAX_SKILL ) {
                    return 0;
                }
            }
            return self::MAX_SKILL - $crew->skill;
        } 

        elseif ( $rule === Rule::WHITELIST ) {
            if ( in_array( $crewed_boat[ 'boat' ]->key, $crew->whitelist ) ) {
                return 0;
            } else {
                return 1;
            }
        }

        elseif ( $rule === Rule::SKILL ) {

            $top_skill = 0;
            $bottom_skill = self::MAX_SKILL;

            for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
                $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
                $top_skill = max( $top_skill, (int)$cb_crew->skill );
                $bottom_skill = min( $bottom_skill, (int)$cb_crew->skill );
            }
            $spread = $top_skill - $bottom_skill;
            if( $spread === self::MAX_SKILL && ( (int)$crew->skill === 0 || (int)$crew->skill === self::MAX_SKILL )) {
                return 1;
            } else {
                return 0;
            }
        }

        elseif ( $rule === Rule::PARTNER ) {
            for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
                $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
                if ( $cb_crew->key === $crew->partner_key ) {
                    return 1;
                }
            }
            return 0;
        }

        elseif ( $rule === Rule::REPEAT ) {
            $cb_boat = $crewed_boat[ 'boat' ];
            for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
                $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
                if ( $crew->key === $cb_crew->key ) {
                   return array_count_values( $cb_crew->history )[ $cb_boat->key ] ?? 0;
                }
            }
            return 0;
        }

        else return 0;
    }

    private function crew_grad( $rule, $crew, $crewed_boat ) : int {

        if ( $rule === Rule::ASSIST ) {
            return $crew->skill;

        } elseif ( $rule === Rule::WHITELIST ) {
            return count( $crew->whitelist );
 
        } elseif ( $rule === Rule::SKILL ) {
            if( (int)$crew->skill === 1 ) {
                return 1;
            }
            else return 0; // Skill is 0 or 2

        } elseif ( $rule === Rule::PARTNER ) {
            if ( $crew->partner_key === NULL ) {
                return 1;
            } else {
                return 0;
            }

        } elseif ( $rule === Rule::REPEAT ) {
            return array_count_values( $crew->history )[NULL] ?? 0;
        }
        else return 0;
    }


    public function best_swap( $_losses, $_grads, $_rule ) : ?object {

        // Find the best crew to swap with $_crew based on the grad array.
        // Don't consider crews from the same boat.
        // Only consider swaps that reduce the loss value for $_crew.
        // Return the crew key.

        //Find the boat to which the crew $_losses[ 0 ]->key is assigned.

        $_a_crewed_boat = $this->crewed_boat_from_key( array_keys( $_losses )[ 0 ]);
        if ( $_a_crewed_boat === null ) {
            return null; // Crew not found in flotilla
        }
        $_a_boat = $_a_crewed_boat[ 'boat' ];

        // Now go through the grad array to find the best swap candidate.

        foreach( $_grads as $_b_crew_key => $_b_grad ) {

            // Find the candidate crew and its boat.

            $_b_crewed_boat = $this->crewed_boat_from_key( $_b_crew_key );
            if ( $_b_crewed_boat === null ) {
                return null; // Replacement crew not found in flotilla
            }
            $_b_boat = $_b_crewed_boat[ 'boat' ];


            if ( $_a_boat === $_b_boat ) {
                continue; // Same boat, try next candidate
            }

            // If the candidate crew does not reduce loss, try the next candidate crew
            // Otherwise, return the candidate crew

            $_b_crew = $this->crew_from_key( $_b_crew_key );
            $_b_loss = $this->crew_loss( $_rule, $_b_crew, $_b_crewed_boat );
            if ( $_b_loss >= array_values( $_losses )[ 0 ] ) {
                continue; // Does not reduce loss, try next candidate
            } else {
                return $_b_crew; // Valid swap found
            }

        }

        fwrite( $this->f, "No valid swap found\n" );

        return null; // No valid swap found

    }

    public function assign( $_flotilla ) {

        $this->flotilla = $_flotilla;

        $f = fopen( __DIR__ . "/../docs/debug.txt", "w" );
        $this->f = $f;

        // Build the unlocked_crews array listing the keys of all crew objects in the flotilla

        $unlocked_crews = [];
        for( $_i = 0; $_i < count( $_flotilla[ 'crewed_boats' ]); $_i++ ) {
            $_crewed_boat = $_flotilla[ 'crewed_boats' ][ $_i ];
            for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                $_crew = $_crewed_boat[ 'crews' ][ $_j ];
                $unlocked_crews[] = $_crew->key;
            }
        }

        fwrite( $f, "Initial Assignment:\n\n" );
        $this->pretty_print( $_flotilla );
/*
        Identify any crewed boats that
        a. require assistance and
        b. have one or more crew with a skill of MAX_SKILL,
        and remove one ofthose crew from the unlocked crews list.
*/
        for( $_i = 0; $_i < count( $_flotilla[ 'crewed_boats' ]); $_i++ ) {
            $_crewed_boat = $_flotilla[ 'crewed_boats' ][ $_i ];
            if ( $_crewed_boat[ 'boat' ]->assistance_required === 'Yes' ) {
                for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                    $_crew = $_crewed_boat[ 'crews' ][ $_j ];
                    if ( (int)$_crew->skill === self::MAX_SKILL ) {
                        // Remove this crew from the unlocked crews list
                        unset( $unlocked_crews[ array_search( $_crew->key, $unlocked_crews ) ] );
                        $unlocked_crews = array_values( $unlocked_crews ); // Reindex the array
                        break; // Only remove one crew per boat
                    }
                }
            }
        }

        fwrite( $f, "After initial step:\n\n" );
        $this->pretty_print( $_flotilla );

        foreach ( Rule::cases() as $_rule ) {

            while ( count( $unlocked_crews ) > 1 ) {

                fwrite($f, "Rule: " . $_rule->name . "\n\n" );

                $this->losses = []; // reset the losses and grads arrays
                $this->grads = [];

                // Make lists of loss and grad values for all unlocked crews

                for( $_i = 0; $_i < count( $_flotilla[ 'crewed_boats' ]); $_i++ ) {
                    $_crewed_boat = $_flotilla[ 'crewed_boats' ][ $_i ];
                    for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                        $_crew = $_crewed_boat[ 'crews' ][ $_j ];
                        if ( in_array( $_crew->key, $unlocked_crews )) {
                            $this->losses[ $_crew->key ] = $this->crew_loss( $_rule, $_crew, $_crewed_boat );
                            $this->grads[ $_crew->key ] = $this->crew_grad( $_rule, $_crew, $_crewed_boat );
                        }
                    }
                }

                // Order the lists

                arsort( $this->losses );
                arsort( $this->grads );

                if( array_values( $this->losses )[0] === 0 ) break; // Move to the next rule
                if( array_values( $this->grads )[0] === 0 ) break; // Move to the next rule

                else { // Swap?

                    $_top_loss_crew = $this->crew_from_key( array_keys( $this->losses )[0] );
                    $_top_grad_crew = $this->best_swap( $this->losses, $this->grads, $_rule );

                    if ( $_top_grad_crew === null ) break; // Valid swap not found, move to next rule

                    fwrite( $f, "Top Loss Crew: " . $_top_loss_crew->key . " Loss: " . array_values( $this->losses )[0] . "\n" );
                    fwrite( $f, "Top Grad Crew: " . ( $_top_grad_crew ? $_top_grad_crew->key : "None" ) . " Grad: " . ( $_top_grad_crew ? array_values( $this->grads )[0] : "None" ) . "\n" );

                    // Find the crew and boat indices and objects corresponding to the top loss and grad crews

                    for( $_i = 0; $_i < count( $_flotilla[ 'crewed_boats' ]); $_i++ ) {
                        $_crewed_boat = $_flotilla[ 'crewed_boats' ][ $_i ];
                        for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                            $_cb_crew = $_crewed_boat[ 'crews' ][ $_j ];
                            if ( $_cb_crew === $_top_loss_crew ) {
                                $_top_loss_boat_index = $_i;
                                $_top_loss_boat = $_crewed_boat[ 'boat' ];
                                $_top_loss_crew_index = $_j;
                                $_top_loss_crew_copy = clone $_cb_crew;
                            }
                            if ( $_cb_crew == $_top_grad_crew ) {
                                $_top_grad_boat_index = $_i;
                                $_top_grad_boat = $_crewed_boat[ 'boat' ];
                                $_top_grad_crew_index = $_j;
                                $_top_grad_crew_copy = clone $_cb_crew;
                            }
                        }
                    }

                    fwrite( $f, "Crews: " . implode( ",", $unlocked_crews ) . "\n" );

                    $_flotilla[ 'crewed_boats' ][ $_top_loss_boat_index ][ 'crews' ][ $_top_loss_crew_index ] = $_top_grad_crew_copy;
                    $_flotilla[ 'crewed_boats' ][ $_top_grad_boat_index ][ 'crews' ][ $_top_grad_crew_index ] = $_top_loss_crew_copy;
                    array_splice( $unlocked_crews, array_search( $_top_grad_crew_copy->key, $unlocked_crews ), 1 );

                    fwrite( $f, "Swap: " . $_top_loss_boat->key . " " . $_top_loss_crew_copy->key . " " .
                    $_top_grad_boat->key . " " . $_top_grad_crew_copy->key . "\n\n" );

                }

            $this->pretty_print( $_flotilla );

            }
        }

        fclose( $f );

        return $_flotilla;
    }
}


?>