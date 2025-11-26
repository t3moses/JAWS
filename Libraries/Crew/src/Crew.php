<?php

require_once __DIR__ . '/../../Season/src/Season.php';
require_once __DIR__ . '/../../Squad/src/Squad.php';

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
        public function set_rank( array $_rank ) {
            return $this->rank = $_rank;
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
            $_season = new Season();
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
            $_season = new Season();
            $_event_ids = $_season->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $this->set_history( $_event_id, $_history );
            }
            return $this->history;
        }
    }

?>