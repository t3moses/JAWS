<?php

namespace nsc\sdc\select;

use nsc\sdc\fleet as fleet;
use nsc\sdc\squad as squad;

require_once __DIR__ . '/../../Fleet/src/Fleet.php';
require_once __DIR__ . '/../../Squad/src/Squad.php';

const BOAT_RANK_FLEXIBILITY_DIMENSION = 0;
const BOAT_RANK_ABSENCE_DIMENSION = 1;
const CREW_RANK_COMMITMENT_DIMENSION = 0;
const CREW_RANK_FLEXIBILITY_DIMENSION = 1;
const CREW_RANK_MEMBERSHIP_DIMENSION = 2;
const CREW_RANK_ABSENCE_DIMENSION = 3;

    class Selection {

        private $event_id;
        private $selected_boats;
        private $selected_crews;
        private $waitlist_boats;
        private $waitlist_crews;

        private function get_min_berths( $_boats ) {
            $_min_berths = 0;
            foreach( $_boats as $_boat ) {
                $_min_berths += $_boat->min_berths;
            }
            return $_min_berths;
        }

        private function get_max_berths( $_boats ) {
            $_max_berths = 0;
            foreach( $_boats as $_boat ) {
                $_max_berths += (int)$_boat->berths[ $this->event_id ];
            }
            return $_max_berths;
        }

        private function shuffle( $_list, $_seed ) {

            if( $_seed !== null ) {
                mt_srand( (int)$_seed );
            }

            $_temp = $_list;

            for ( $_i = 0; $_i < count( $_list ); $_i++ ) {
                $_source_index = mt_rand( 0, count( $_temp ) - 1 );
                $_list[ $_i ] = $_temp[ $_source_index ];
                array_splice( $_temp, $_source_index, 1 );
            }
            return $_list;

        }

        private function is_greater( $_rank_1, $_rank_2 ) : bool {

            $_dimensions = count( $_rank_1 );
            for ( $_i = 0; $_i < $_dimensions; $_i++ ) {

                if ( (int)$_rank_1[ $_i ] > (int)$_rank_2[ $_i ] ) {
                    return true;
                }
                elseif ( (int)$_rank_1[ $_i ] < (int)$_rank_2[ $_i ] ) {
                    return false;
                }

            }
            return false; // The input ranks are equal
        }

        private function bubble( $_list ) { // refers to a list of objects

            $_n = count( $_list );

            // Traverse through all array elements
            for( $_i = 0; $_i < $_n; $_i++ ) {

                $_swapped = false;

                // Last i elements are already in place

                for ( $_j = 0; $_j < $_n - $_i - 1; $_j++ ) {
                    
                    // Traverse the list from 0 to n-i-1. Swap if the element 
                    // found is greater than the next element

                    if ( $this->is_greater( $_list[$_j]->rank, $_list[$_j + 1]->rank )) {

                        $_temp = $_list[ $_j ];
                        $_list[ $_j ] = $_list[ $_j + 1 ];
                        $_list[ $_j + 1 ] = $_temp;
                        $_swapped = true;

                    }
                }

                // If no two elements were swapped by inner loop, then break

                if ( $_swapped == false )
                    break;
                }
                return $_list;
            }

        private function cut( $_boats, $_crews ) {

            // Cut boats or crew to fit, then distribute crew amongst boats

            $_min_berths = $this->get_min_berths( $_boats );
            $_max_berths = $this->get_max_berths( $_boats );
            if ( count( $_crews ) < $_min_berths ) {
                $this->case_1( $_boats, $_crews );
            }
            elseif ( count( $_crews ) > $_max_berths ) {
                $this->case_2( $_boats, $_crews );
            }
            else {
                $this->case_3( $_boats, $_crews );
            }
        }

        private function case_1( $_boats, $_crews ) {

            // The minimum number of crews required by owners exceeds the actual number of available crews
            // We need to cut boats, starting with the lowest ranked boat

            foreach( $_boats as $_boat ) {
                $_boat->occupied_berths = $_boat->min_berths;
            }
            $_all_berths = $this->get_min_berths( $_boats );
            $_crew_count = count( $_crews );
            $_waitlist_boats = [];
            while ( $_all_berths > $_crew_count ) {
                $_cut_boat = array_shift( $_boats );
                $_all_berths -= $_cut_boat->min_berths;
                $_waitlist_boats[] = $_cut_boat;
            }

            if ( $_all_berths === $_crew_count ) {
                $this->selected_boats = array_reverse( $_boats );
                $this->selected_crews = array_reverse( $_crews );
                $this->waitlist_boats[] = array_reverse( $_waitlist_boats );
                $this->waitlist_crews = [];
                return;
            }
            else { // max_berths has to be greater than min_berths
                $this->case_3( $_boats, $_crews );
            }
        }

        private function case_2( $_boats, $_crews ) {

            // The actual number of available crews exceeds the maximum number of available berths
            // We need to cut crews, starting with the lowest ranked crew

            foreach( $_boats as $_boat ) {
                $_boat->occupied_berths = $_boat->berths[ $this->event_id ];
            }
            $_all_berths = $this->get_max_berths( $_boats );
            $_excess_crews = count( $_crews ) - $_all_berths;
            $_waitlist_crews = array_slice( $_crews, 0, $_excess_crews );
            $_crews = array_slice( $_crews, $_excess_crews );

            $this->selected_boats = array_reverse( $_boats );
            $this->selected_crews = array_reverse( $_crews );
            $this->waitlist_boats = [];
            $this->waitlist_crews = array_reverse( $_waitlist_crews );
        }

        private function case_3( $_boats, $_crews ) {

            // The actual number of available crews can be accommodated by the actual number of available berths
            // No cuts are required

            foreach( $_boats as $_boat ) {
                $_boat->occupied_berths = $_boat->min_berths;
            }
            $_all_berths = $this->get_min_berths( $_boats );
            $_crew_count = count( $_crews );
            while ( $_all_berths < $_crew_count ) {
                $_biggest_space = 0;
                foreach( $_boats as $_boat ) {
                    $_boat_space = $_boat->max_berths - $_boat->occupied_berths;
                    if ( $_boat_space > $_biggest_space ) {
                        $_biggest_space = $_boat_space;
                        $_augmented_boat = $_boat;
                    }
                }
                $_augmented_boat->occupied_berths++;
                $_all_berths++;
            }
            $this->selected_boats = array_reverse( $_boats );
            $this->selected_crews = array_reverse( $_crews );
            $this->waitlist_boats = [];
            $this->waitlist_crews = [];
        }

        public function select( $_fleet, $_squad, $_event_id  ) {

            $this->event_id = $_event_id;

            $_boats = $_fleet->get_available( $_event_id );
            $_crews = $_squad->get_available( $_event_id );

            $_fleet->update_absence_rank( $_boats );
            $_squad->update_absence_rank( $_crews );
            $_squad->update_commitment_rank( $_crews );

            $_shuffled_boats = $this->shuffle( $_boats, $_event_id );
            $_sorted_boats = $this->bubble( $_shuffled_boats );
            $_shuffled_crews = $this->shuffle( $_crews, $_event_id );
            $_sorted_crews = $this->bubble( $_shuffled_crews );
            $this->cut( $_sorted_boats, $_sorted_crews );

        }

        public function get_selected_boats( ) {
            return $this->selected_boats;
        }
        public function get_selected_crews( ) {
            return $this->selected_crews;
        }
        public function get_waitlist_boats( ) {
            return $this->waitlist_boats;
        }
        public function get_waitlist_crews( ) {
            return $this->waitlist_crews;
        }


    }

?>