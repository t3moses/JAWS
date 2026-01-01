<?php

namespace nsc\sdc\assignment;

enum Rule: int {
    case ASSIST = 0;
    case WHITELIST = 1;
    case HIGH_SKILL = 2;
    case LOW_SKILL = 3;
    case PARTNER = 4;
    case REPEAT = 5;
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

        foreach( $this->flotilla[ 'crewed_boats' ] as $_crewed_boat ) {
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

        foreach( $this->flotilla[ 'crewed_boats' ] as $_crewed_boat ) {
            foreach( $_crewed_boat[ 'crews' ] as $_cb_crew ) {
                if ( $_cb_crew->key === $_crew_key ) {
                    return $_crewed_boat;
                }
            }
        }
        return null; // If crew not found in flotilla
    }

    private function replace_crew( $_a_crew, $_b_crew, $_crewed_boat ) {

        // Return the crewed boat object with $_a_crew replaced by $_b_crew

        foreach( $_crewed_boat[ 'crews' ] as $_cb_crew ) {
            if ( $_cb_crew->key === $_a_crew->key ) {
                $_cb_crew = $_b_crew;
            }
        }
        return $_crewed_boat;
    }
    

    private function skill_spread( $crewed_boat ) {

        $top_skill = 0;
        $bottom_skill = self::MAX_SKILL;

        for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
            $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
            $top_skill = max( $top_skill, (int)$cb_crew->skill );
            $bottom_skill = min( $bottom_skill, (int)$cb_crew->skill );
        }
        return $top_skill - $bottom_skill; // skill spread
    }


    public function crew_loss( $rule, $crew, $crewed_boat ) : int {

        if ( $rule === Rule::ASSIST ) {
            if ( $crewed_boat[ 'boat' ]->assistance_required === 'No' ){
                return 0;
            }
            else {
                for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
                    $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
                    if ( (int)$cb_crew->skill === self::MAX_SKILL ) {
                        return 0;
                    }
                }
            return self::MAX_SKILL - (int)$crew->skill;
            }
        } 

        else if ( $rule === Rule::WHITELIST ) {
            if ( in_array( $crewed_boat[ 'boat' ]->key, $crew->whitelist ) ) {
                return 0;
            } else {
                return 1;
            }
        }

        else if ( $rule === Rule::HIGH_SKILL ) {
            if ( $this->skill_spread( $crewed_boat ) === self::MAX_SKILL ) {
                if ( (int)$crew->skill === self::MAX_SKILL ) {
                    return 1; // high spread, skill is max, so yes to high skill loss
                }
                else {
                    return 0; // high spread, but skill is 0 or middle, so no to high skill loss
                }
            }
            return 0; // not even high spread, so no to high skill loss
        }


        else if ( $rule === Rule::LOW_SKILL ) {
            if ( $this->skill_spread( $crewed_boat ) === self::MAX_SKILL ) {
                if ( (int)$crew->skill === 0 ) {
                    return 1; // high spread, skill is 0 so yes to low skill loss
                }
                else {
                    return 0; // high spread, but skill is high or middle, so no to low skill loss
                }
            }
            return 0; // not even high spread, so no to low skill loss
        }

        else if ( $rule === Rule::PARTNER ) {
            for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
                $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
                if ( $cb_crew->key === $crew->partner_key ) {
                    return 1;
                }
            }
            return 0;
        }

        else if ( $rule === Rule::REPEAT ) {
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
            return (int)$crew->skill;
        }
        else if ( $rule === Rule::WHITELIST ) {
            return count( $crew->whitelist );
        }
        else if ( $rule === Rule::HIGH_SKILL ) {
            if ( $this->skill_spread( $crewed_boat ) === self::MAX_SKILL ) {
                if ( (int)$crew->skill === self::MAX_SKILL ) {
                    return 0; // skill is max so no to grad
                }
                else {
                    return 1; // skill is low or middle, so yes to high skill grad
                }
            }
            else {
                return 0;
            }
        }
        else if ( $rule === Rule::LOW_SKILL ) {
            if ( $this->skill_spread( $crewed_boat ) === self::MAX_SKILL ) {
                if ( (int)$crew->skill === 0 ) {
                    return 0; // skill is 0, so no to low skill grad
                }
                else {
                    return 1; // skill is high or middle, so yes to low skill grad
                }
            }
            else {
                return 0;
            }
        }

        else if ( $rule === Rule::PARTNER ) {
            if ( $crew->partner_key === NULL ) {
                return 1;
            } else {
                return 0;
            }
        }
        else if ( $rule === Rule::REPEAT ) {
            return array_count_values( $crew->history )[NULL] ?? 0;
        }
        else return 0;
    }


    public function best_swap( $_losses, $_grads, $_rule ) : ?object {

        // Find the best crew to swap with $_crew based on the grad array.
        // Don't consider crews from the same boat.
        // Only consider swaps that reduce the loss value for $_crew.
        // Return the crew object.

        //Find the crew and boat corresponding to the highest loss.

        $_a_crew_key = array_keys( $_losses )[ 0 ];
        $_a_crew = $this->crew_from_key( $_a_crew_key );
        $_a_crewed_boat = $this->crewed_boat_from_key( $_a_crew_key );

        if ( $_a_crewed_boat === null ) {
            return null; // Crew not found in flotilla
        }
        else {
            $_a_boat = $_a_crewed_boat[ 'boat' ];
        }

        // Now go through the grad array to find the best swap candidate.

        foreach( $_grads as $_b_crew_key => $_b_grad ) {

            // Find the candidate crew and its boat.

            $_b_crewed_boat = $this->crewed_boat_from_key( $_b_crew_key );
            if ( $_b_crewed_boat === null ) {
                return null; // Replacement crew not found in flotilla
            }
            else {
                $_b_boat = $_b_crewed_boat[ 'boat' ];
            }

            if ( $_a_boat->key === $_b_boat->key ) {
               continue; // Same boat, try next candidate
            }

            // If the candidate crew does not reduce loss, try the next candidate crew
            // Otherwise, return the candidate crew

            $_b_crew = $this->crew_from_key( $_b_crew_key );

            $_ab_crewed_boat = $this->replace_crew( $_a_crew, $_b_crew, $_a_crewed_boat );
            $_b_loss = $this->crew_loss( $_rule, $_b_crew, $_ab_crewed_boat );
            if ( $_b_loss >= array_values( $_losses )[ 0 ] ) {
                continue; // Does not reduce loss, try next candidate
            } else {
                return $_b_crew; // Valid swap found
            }
        }

        fwrite( $this->f, "No valid swap found\n\n" );

        return null; // No valid swap found

    }

    public function assign( $_flotilla ) {

        $this->flotilla = $_flotilla;

        $f = fopen( __DIR__ . "/../docs/debug.txt", "w" );
        $this->f = $f;

        // Build the unlocked_crews array listing the keys of all crew objects in the flotilla

        $unlocked_crews = [];
        for( $_i = 0; $_i < count( $this->flotilla[ 'crewed_boats' ]); $_i++ ) {
            $_crewed_boat = $this->flotilla[ 'crewed_boats' ][ $_i ];
            for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                $_crew = $_crewed_boat[ 'crews' ][ $_j ];
                $unlocked_crews[] = $_crew->key;
            }
        }

        fwrite( $f, "Available crews: " . implode( ",", $unlocked_crews ) . "\n" );
        fwrite( $f, "Initial Assignment:\n\n" );
        $this->pretty_print( $this->flotilla );

        for( $_i = 0; $_i < count( $this->flotilla[ 'crewed_boats' ]); $_i++ ) {
            $_crewed_boat = $this->flotilla[ 'crewed_boats' ][ $_i ];
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
        fwrite( $f, "Available crews: " . implode( ",", $unlocked_crews ) . "\n" );
        fwrite( $f, "After initialization:\n\n" );
        $this->pretty_print( $this->flotilla );

        foreach ( Rule::cases() as $_rule ) {

            while ( count( $unlocked_crews ) > 1 ) {

                fwrite($f, "Rule: " . $_rule->name . "\n\n" );

                $this->losses = []; // reset the losses and grads arrays
                $this->grads = [];

                // Make lists of loss and grad values for all unlocked crews

                for( $_i = 0; $_i < count( $this->flotilla[ 'crewed_boats' ]); $_i++ ) {
                    $_crewed_boat = $this->flotilla[ 'crewed_boats' ][ $_i ];
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

                $_top_loss_crew = $this->crew_from_key( array_keys( $this->losses )[0] );
                $_top_grad_crew = $this->best_swap( $this->losses, $this->grads, $_rule );

                if ( $_top_grad_crew === null ) break; // Valid swap not found, move to next rule

                // Find the crew and boat indices and objects corresponding to the top loss and grad crews

                for( $_i = 0; $_i < count( $this->flotilla[ 'crewed_boats' ]); $_i++ ) {
                    $_crewed_boat = $this->flotilla[ 'crewed_boats' ][ $_i ];
                    for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                        $_cb_crew = $_crewed_boat[ 'crews' ][ $_j ];
                        if ( $_cb_crew->key === $_top_loss_crew->key ) {
                            $_top_loss_boat_index = $_i;
                            $_top_loss_boat = $_crewed_boat[ 'boat' ];
                            $_top_loss_crew_index = $_j;
                            $_top_loss_crew_copy = clone $_cb_crew;
                        }
                        if ( $_cb_crew->key === $_top_grad_crew->key ) {
                            $_top_grad_boat_index = $_i;
                            $_top_grad_boat = $_crewed_boat[ 'boat' ];
                            $_top_grad_crew_index = $_j;
                            $_top_grad_crew_copy = clone $_cb_crew;
                        }
                    }
                }

                fwrite( $f, "Availabke crews: " . implode( ",", $unlocked_crews ) . "\n" );

                $this->flotilla[ 'crewed_boats' ][ $_top_loss_boat_index ][ 'crews' ][ $_top_loss_crew_index ] = $_top_grad_crew_copy;
                $this->flotilla[ 'crewed_boats' ][ $_top_grad_boat_index ][ 'crews' ][ $_top_grad_crew_index ] = $_top_loss_crew_copy;
                array_splice( $unlocked_crews, array_search( $_top_grad_crew_copy->key, $unlocked_crews ), 1 );

                fwrite( $f, "Swap: " . $_top_loss_boat->key . " " . $_top_loss_crew_copy->key . " " .
                $_top_grad_boat->key . " " . $_top_grad_crew_copy->key . "\n\n" );

            $this->pretty_print( $this->flotilla );

            }
        }

        fclose( $f );

        return $this->flotilla;
    }
}


?>