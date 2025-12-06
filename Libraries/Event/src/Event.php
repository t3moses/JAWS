<?php

namespace nsc\sdc\event;

use nsc\sdc\fleet as fleet;
use nsc\sdc\squad as squad;

require_once __DIR__ . '/../../Fleet/src/Fleet.php';
require_once __DIR__ . '/../../Squad/src/Squad.php';


    Class Event {

        private $flotilla = [];
        private $event_id;
        private $selected_boats = [];
        private $selected_crews = [];
        private $waitlist_boats = [];
        private $waitlist_crews = [];

        public function set_event_id( string $_event_id ) {
            $this->event_id = $_event_id;
        }
        public function set_selected_boats( $_boats ) {
            $this->selected_boats = $_boats;
        }
        public function set_selected_crews( $_crews ) {
            $this->selected_crews = $_crews;
        }
        public function set_waitlist_boats( $_boats ) {
            $this->waitlist_boats = $_boats;
        }
        public function set_waitlist_crews( $_crews ) {
            $this->waitlist_crews = $_crews;
        }

        public function get_flotilla( ) {

            $_crewed_boats = [];
            $_first_crew = 0;

            foreach( $this->selected_boats as $_boat ) {
                $_crewed_boat[ 'boat' ] = $_boat;
                $_crew_count = $_boat->occupied_berths;
                $_crewed_boat[ 'crews' ] = array_slice( $this->selected_crews, $_first_crew, $_crew_count );
                $_first_crew += $_crew_count;
                $_crewed_boats[] = $_crewed_boat;
            }
            $this->flotilla[ 'event_id' ] = $this->event_id;
            $this->flotilla[ 'crewed_boats' ] = $_crewed_boats;
            $this->flotilla[ 'waitlist' ] = $this->waitlist_crews;
            return $this->flotilla;
        }

    }

?>