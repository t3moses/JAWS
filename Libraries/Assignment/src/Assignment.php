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

    public $loss = array();
    public $grad = array();

    private const MAX_SKILL = 2;

    public function __construct() {
        
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
            $_top_skill = 0;
            $_bottom_skill = self::MAX_SKILL;
            for ( $_i = 0; $_i < count( $crewed_boat[ 'crews' ]); $_i++ ) {
                $cb_crew = $crewed_boat[ 'crews' ][ $_i ];
                if ( (int)$cb_crew->skill > $_top_skill ) {
                    $_top_skill = $cb_crew->skill;
                }
                if ( (int)$cb_crew->skill < $_bottom_skill ) {
                    $_bottom_skill = (int)$cb_crew->skill;
                }
            }
            $_median_skill = ( $_top_skill + $_bottom_skill ) / 2;
            return abs ( (int)$crew->skill - intval( $_median_skill ));
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
                   return array_count_values($cb_crew->history)[ $cb_boat->key ] ?? 0;
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
            return abs ( (int)$crew->skill - self::MAX_SKILL / 2 );

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

    public function assign( $_flotilla ) {

        $f = fopen( __DIR__ . "/flotilla.txt", "w" );

        foreach ( Rule::cases() as $_rule ) {

            for( $_i = 0; $_i < count( $_flotilla[ 'crewed_boats' ]); $_i++ ) {

                $_crewed_boat = $_flotilla[ 'crewed_boats' ][ $_i ];

                for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {

                    $_crew = $_crewed_boat[ 'crews' ][ $_j ];

                    $this->loss[ $_crew->key ] = $this->crew_loss( $_rule, $_crew, $_crewed_boat );
                    $this->grad[ $_crew->key ] = $this->crew_grad( $_rule, $_crew, $_crewed_boat );

                }
            }

            arsort( $this->loss );
            arsort( $this->grad );
            fwrite( $f, "Rule: " . $_rule->name . "\n\n" );

            for( $_i = 0; $_i < count( $_flotilla[ 'crewed_boats' ]); $_i++ ) {

                $_crewed_boat = $_flotilla[ 'crewed_boats' ][ $_i ];

                for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                    $_cb_crew = $_crewed_boat[ 'crews' ][ $_j ];
                    fwrite( $f, $_cb_crew->key . "  " . array_key_first( $this->loss ) . "  " . array_key_first( $this->grad ) . "\n" );
                    if ( $_cb_crew->key === array_key_first( $this->loss ) ) {  
                        fwrite( $f, "Found loss\n" );   
                        $_top_loss_crew = $_cb_crew;
                    }
                    else if ( $_cb_crew->key === array_key_first( $this->grad ) ) {  
                        fwrite( $f, "Found grad\n" );   
                        $_top_grad_crew = $_cb_crew;
                    }
                }
            }

            fwrite( $f, "Swapping " . $_top_loss_crew->key . " and " . $_top_grad_crew->key . "\n\n" );

            for( $_i = 0; $_i < count( $_flotilla[ 'crewed_boats' ]); $_i++ ) {

                $_crewed_boat = $_flotilla[ 'crewed_boats' ][ $_i ];

                for( $_j = 0; $_j < count( $_crewed_boat[ 'crews' ]); $_j++ ) {
                    $_cb_crew = $_crewed_boat[ 'crews' ][ $_j ];
                    if ( $_cb_crew->key === array_key_first( $this->loss ) ) {  
                        $_flotilla[ 'crewed_boats' ][ $_i ][ 'crews' ][ $_j ] = $_top_grad_crew;
                    }
                    else if ( $_cb_crew->key === array_key_first( $this->grad ) ) {
                        $_flotilla[ 'crewed_boats' ][ $_i ][ 'crews' ][ $_j ] = $_top_loss_crew;
                    }
                }
            }

            array_shift( $this->grad );

        }

        fclose( $f );

        return $_flotilla;
    }
}


?>