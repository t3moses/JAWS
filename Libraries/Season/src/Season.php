<?php

namespace nsc\sdc\season;

    class Season {

        private static $_source;
        private static $_time;
        private static $_year;
        private static $_start_time;
        private static array $_event_ids = [];
        private static array $_flotillas = []; // Associative array

        public static function load_season_data() {

            $_config_fname = __DIR__ . '/../data/config.json';
            $_config_file = file_get_contents( $_config_fname );
            $config_data = json_decode( $_config_file, true );
            self::$_source = $config_data[ 'config' ][ 'source' ];
            self::$_year = $config_data[ 'config' ][ 'year' ];

            if ( self::$_source === 'simulated ') {
                self::$_time = strtotime( $config_data[ 'config' ][ 'year' ] . '-' . $config_data[ 'config' ][ 'month' ] . '-' .  $config_data[ 'config' ][ 'day' ]);
            } else {
                self::$_time = time( );
            }         
            self::$_start_time = $config_data[ 'config' ][ 'start_time' ];
            self::$_event_ids = $config_data[ 'config' ][ 'event_ids' ];

        }

        public static function get_event_time( string $_event_id ) {

            $_date = date_create_from_format( 'Y D M j H i s', self::$_year . ' ' . $_event_id . ' ' . self::$_start_time );
            $_utime = $_date->format('U');
            return $_utime;
        }

        public static function get_past_events( ) : ?array {

            $_past_events = [];
            $_event_ids = self::get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = self::get_event_time( $_event_id );
                if ( $_event_time < time() ) {
                    $_past_events[] = $_event_id;
                }
                else {
                    break;
                }
            }
            return $_past_events;
        }

        public static function get_future_events( ) : ?array {

            $_future_events = [];
            $_event_ids = self::get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = self::get_event_time( $_event_id );

                    if ( $_event_time > self::$_time ) {

                    $_future_events[] = $_event_id;
                }
            }
            return $_future_events;
        }


        public static function get_next_event( ) : ?string {

            $_event_ids = self::get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = self::get_event_time( $_event_id );
                if ( $_event_time < self::$_time ) {
                }
                else {
                    return $_event_id;
                }
            }
            return null;
        }

        public static function get_last_event( ) : ?string {

            $_last_event = null;
            $_event_ids = self::get_event_ids();
            foreach( $_event_ids as $_event_id ) {
                $_event_time = self::get_event_time( $_event_id );
                if ( $_event_time < self::$_time ) {
                    $_last_event = $_event_id;
                }
                else {
                    return $_last_event;
                }
            }
            return $_last_event;
        }

        public static function get_first_time() {
            return self::get_event_time( self::get_event_ids()[ 0 ]);
        }
        public static function get_final_time() {
            return self::get_event_time( self::get_event_ids()[ -1 ]);
        }
        public static function get_event_count() : int {
            return count(self::$_event_ids);
        }
        public static function get_event_ids() : array {
            return self::$_event_ids;
        }
        public static function get_flotilla( string $_event_id ) : ?array {
            return self::$_flotillas[ $_event_id ];
        }
        public static function set_flotilla( string $_event_id, $_flotilla ) : void {
            self::$_flotillas[ $_event_id ] = $_flotilla;
        }
        public static function get_flotillas() {
            return self::$_flotillas;
        }
    }

?>
