<?php

namespace nsc\sdc\crew;

use nsc\sdc\season as season;
use nsc\sdc\fleet as fleet;
use nsc\sdc\name as name;

require_once __DIR__ . '/../../Season/src/Season.php';
require_once __DIR__ . '/../../Fleet/src/Fleet.php';
require_once __DIR__ . '/../../Name/src/Name.php';

    class Crew {

        public $key;
        public $display_name;
        public $first_name;
        public $last_name;
        public $partner_key;
        public $email;
        public $membership_number;
        public $rank = []; // Indexed array
        public $skill;
        public $experience;
        public $available = []; // Associative array
        public $history = []; // Associative array
        public $whitelist = []; // Indexed array

        public function __construct( ) {

        }
        public function set_default() {

            $this->key = '';
            $this->first_name = '';
            $this->last_name = '';
            $this->partner_key = '';
            $this->email = '';
            $this->membership_number = '';
            $this->skill = 0;
            $this->experience = '';

        }
        public function get_key() {
            return $this->key;
        }
        public function get_display_name() {
            return $this->display_name;
        }
        public function get_first_name() {
            return $this->first_name;
        }
        public function get_last_name() {
            return $this->last_name;
        }
        public function get_partner_key() {
            return $this->partner_key;
        }
        public function get_email() {
            return $this->email;
        }
        public function get_membership_number() {
            return $this->membership_number;
        }
        public function get_skill() {
            return $this->skill;
        }
        public function get_experience() {
            return $this->experience;
        }
        public function get_rank() : array {
            return $this->rank;
        }
        public function get_commitment_level() {
            return $this->get_rank( 0 );
        }
        public function get_flex_level() {
            return $this->get_rank( 1 );
        }
        public function get_membership_level() {
            return $this->get_rank( 2 );
        }
        public function get_absence_level() {
            return $this->get_rank( 3 );
        }
        public function get_whitelist() {
            return $this->whitelist;
        }
        public function get_available( $_event_id ) : string {
            return $this->available[ $_event_id ];
        }
        public function get_all_available() : array {
            return $this->available;
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
        public function set_first_name( $_first_name ) {
            $this->first_name = $_first_name;
        }
        public function set_last_name( $_last_name ) {
            $this->last_name = $_last_name;
        }
        public function set_partner_key( $_partner_key ) {
            $this->partner_key = $_partner_key;
        }
        public function set_email( $_email ) {
            $this->email = $_email;
        }
        public function set_rank( $_dim, int $_level ) {
            return $this->rank[ $_dim ] = $_level;
        }
        public function set_commitment_level( $_level ) {
            $this->set_rank( 0, $_level );
        }
        public function set_flex_level( ) {
            if( $this->is_flex() ) {
                $this->set_rank( 1, 0 );
            }
            else {
                $this->set_rank( 1, 1 );
            }
        }
        public function set_membership_level( ){
            if ( $this->is_member() ) {
                $this->set_rank( 2, 1 );
            }
            else {
                $this->set_rank( 2, 0 );
            }
        }
        public function set_absence_level( ) {
            $_level = $this->get_absence();
            $this->set_rank( 3, $_level );
        }
        public function set_whitelist( $_whitelist) {
            return $this->whitelist = $_whitelist;
        }
        public function set_membership_number( $_membership_number) {
            return $this->membership_number = $_membership_number;
        }
        public function set_skill( $_skill) {
            return $this->skill = $_skill;
        }
        public function set_experience( $_experience) {
            return $this->experience = $_experience;
        }
        public function set_available( string $_event_id, string $_available ) {
            return $this->available[ $_event_id ] = $_available;
        }
        public function set_all_available( $_available ) {
            $_season = new season\Season();
            $_event_ids = $_season->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $this->set_available( $_event_id, $_available );
            }
            return $this->available;
        }
        public function set_history( $_event_id, $_history ) {
            return $this->history[ $_event_id ] = $_history;
        }
        public function set_all_history( $_history ) {
            $_season = new season\Season();
            $_event_ids = $_season->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $this->set_history( $_event_id, $_history );
            }
            return $this->history;
        }

        public function is_member() {

            if ( $this->membership_number === Null) {
                return false;
            }
            else {
                $_clean_number = [];
                foreach( str_split( $this->membership_number ) as $_char ) {
                    if (ctype_digit($_char)) {
                        $_clean_number[] = $_char;
                    }
                    elseif (ctype_digit($_char)) {
                        return false;
                    }
                }
                if ( count( $_clean_number ) < 4 || count( $_clean_number ) > 9 ) {
                    return false;
                }
            return true;
            }
        }

        public function is_flex() : bool {

            /*
            If the crew is also a boat owner, return true and update the crew and boat rank tensors
            */

            $_fleet = new fleet\Fleet();
            foreach( $_fleet->boats as $_boat ) {
                $_owner_key = name\key_from_strings( $_boat->owner_first_name, $_boat->owner_last_name );
                if ( $_owner_key === $this->key ) {
                    $_boat->set_rank( 0, 0 );
                    return true;
                }
            }
            return false;
        }

        public function get_absence() : int {

            /*
            Return the number of past events in which the crew was assigned
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