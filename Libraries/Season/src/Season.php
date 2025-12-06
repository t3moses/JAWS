<?php

namespace nsc\sdc\season;

    class Season {

        private $time;
        private $year;
        private $start_time;
        private array $event_ids = [];
        private $flotillas = []; // Associative array

        public function __construct() {

            $_config_fname = __DIR__ . '/../../Config/data/config.json';
            $_config_file = file_get_contents( $_config_fname );
            $config_data = json_decode( $_config_file, true );
            $this->year = $config_data[ 'config' ][ 'year' ];            
            $this->start_time = $config_data[ 'config' ][ 'start_time' ];
            $this->event_ids = $config_data[ 'config' ][ 'event_ids' ];
            $this->time = time( );
            
        }

        public function set_time( $_utime ) {
            $this->time = $_utime;
        }
        public function get_time() {
            return $this->time;
        }
        public function get_event_time( string $_event_id ) {

            $_date_time = new \DateTime();
            $_time = $_date_time->createFromFormat('Y D M d H i s', $this->year . ' ' . $_event_id . ' ' . $this->start_time);
            $_utime = $_time->getTimestamp();
            return $_utime;
        }

        public function get_past_events( ) : ?array {

            $_past_events = [];
            $_event_ids = $this->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = $this->get_event_time( $_event_id );
                if ( $_event_time < $this->time ) {
                    $_past_events[] = $_event_id;
                }
                else {
                    break;
                }
            }
            return $_past_events;
        }

        public function get_future_events( ) : ?array {

            $_future_events = [];
            $_event_ids = $this->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = $this->get_event_time( $_event_id );
                if ( $_event_time > $this->time ) {
                    $_future_events[] = $_event_id;
                }
            }
            return $_future_events;
        }


        public function get_next_event( ) : ?string {

            $_event_ids = $this->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = $this->get_event_time( $_event_id );
                if ( $_event_time < $this->time ) {
                }
                else {
                    return $_event_id;
                }
            }
            return null;
        }

        public function get_last_event( ) : ?string {

            $_last_event = null;
            $_event_ids = $this->get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = $this->get_event_time( $_event_id );
                if ( $_event_time < $this->time ) {
                    $_last_event = $_event_id;
                }
                else {
                    return $_last_event;
                }
            }
            return $_last_event;
        }

        public function get_first_time() {
            return $this->get_event_time( $this->get_event_ids()[ 0 ]);
        }
        public function get_final_time() {
            return $this->get_event_time( $this->get_event_ids()[ -1 ]);
        }
        public function get_event_count() : int {
            return count($this->event_ids);
        }
        public function get_event_ids() : array {
            return $this->event_ids;
        }
        public function get_flotilla( string $_event_id ) : ?array {
            return $this->flotillas[ $_event_id ];
        }
        public function set_flotilla( string $_event_id, $_flotilla ) : void {
            $this->flotillas[ $_event_id ] = $_flotilla;
        }
        public function get_flotillas() {
            return $this->flotillas;
        }
    }

?>
