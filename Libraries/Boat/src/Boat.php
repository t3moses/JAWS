<?php

namespace nsc\sdc\boat;

use nsc\sdc\name as name;
use nsc\sdc\season as season;
use nsc\sdc\squad as squad;


require_once __DIR__ . '/../../Name/src/Name.php';
require_once __DIR__ . '/../../Season/src/Season.php';
require_once __DIR__ . '/../../Squad/src/Squad.php';
require_once __DIR__ . '/../../Name/src/Name.php';

    class Boat {

        public $key;
        public $display_name;
        public $owner_first_name;
        public $owner_last_name;
        public $owner_email;
        public $owner_mobile;
        public $min_berths;
        public $max_berths;
        public $occupied_berths;
        public $assistance_required;
        public $rank = []; // Indexed array
        public $berths = []; // Associative array
        public $history = []; // Associative array

        public function __construct( ) {

        }
        public function set_default() {
            $this->key = '';
            $this->owner_first_name = '';
            $this->owner_last_name = '';
            $this->owner_email = '';
            $this->owner_mobile = '';
            $this->min_berths = 1;
            $this->max_berths = 1;
            $this->assistance_required = 'No';
        }
        public function get_key() {
            return $this->key;
        }
        public function get_display_name() {
            return $this->display_name;
        }
        public function get_owner_first_name() {
            return $this->owner_first_name;
        }
        public function get_owner_last_name() {
            return $this->owner_last_name;
        }
        public function get_owner_key() {
            return name\key_from_strings( $this->owner_first_name, $this->owner_last_name );
        }
        public function get_owner_email() {
            return $this->owner_email;
        }
        public function get_owner_mobile() {
            return $this->owner_mobile;
        }
        public function get_min_berths() {
            return $this->min_berths;
        }
        public function get_max_berths() {
            return $this->max_berths;
        }
        public function get_berths( $_event_id ) : int {
            return $this->berths[ $_event_id ];
        }
        public function get_all_berths() : array {
            return $this->berths;
        }
        public function get_assistance_required() {
            return $this->assistance_required;
        }
        public function get_rank() {
            return $this->rank;
        }
        public function get_history( $_event_id ) : string {
            return $this->history[ $_event_id ];
        }
        public function get_all_history( ) : array {
            return $this->history;
        }
        public function set_key( $_key ) {
            $this->key = $_key;
        }
        public function set_display_name( $_display_name ) {
            $this->display_name = $_display_name;
        }
        public function set_owner_first_name( $_owner_first_name ) {
            $this->owner_first_name = $_owner_first_name;
        }
        public function set_owner_last_name( $_owner_last_name ) {
            $this->owner_last_name = $_owner_last_name;
        }
        public function set_owner_email( $_owner_email ) {
            $this->owner_email = $_owner_email;
        }
        public function set_owner_mobile( $_owner_mobile ) {
            $this->owner_mobile = $_owner_mobile;
        }
        public function set_min_berths( $_min_berths ) {
            $this->min_berths = $_min_berths;
        }
        public function set_max_berths( $_max_berths ) {
            $this->max_berths = $_max_berths;
        }
        public function set_berths( $_event_id, $_berths ) {
            return $this->berths[ $_event_id ] = $_berths;
        }
        public function set_all_berths( $_berths ) {
            $_season = new season\Season();
            $_event_ids = $_season->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $this->berths[ $_event_id ] = $_berths;
            }
            return $this->berths;
        }
        public function set_assistance_required( $_assistance_required ) {
             $this->assistance_required = $_assistance_required;
        }
        public function set_rank( $_dim, $_rank) {
            $this->rank[ $_dim ] = $_rank;
            return $this->rank;
        }
        public function set_history( $_event_id, $_history ) {
            return $this->history[ $_event_id ] = $_history;
        }
        public function set_all_history( $_history ) {
            $_season = new season\Season();
            $_event_ids = $_season->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $this->history[ $_event_id ] = $_history;
            }
            return $this->history;
        }

        public function is_flex() : bool {

            /*
            If the boat owner is also a crew, return true and update the crew rank tensors
            */

            $_squad = new squad\Squad();
            foreach( $_squad->crews as $_crew ) {
                if ( $this->get_owner_key() === $_crew->get_key() ) {
                    $_crew->set_rank( 1, 0 );
                    return true;
                }
            }
            return false;
        }

        public function get_absence() : int {

            /*
            Return the number of past events in which the boat was available
            Also update the rank tensor
            */

            $_season = new season\Season();
            $_past_events = $_season->get_past_events();
            $_absence = count( $_past_events );

            foreach( $_past_events as $_past_event ) {

                if ( $this->history[ $_past_event ] !== '' ) {
                    $_absence--;
                }
            }
            return $_absence;
        }

    }

?>