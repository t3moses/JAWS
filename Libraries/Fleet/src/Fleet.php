<?php

namespace nsc\sdc\fleet;

use nsc\sdc\boat as boat;
use nsc\sdc\season as season;

require_once __DIR__ . '/../../Csv/src/Csv.php';
require_once __DIR__ . '/../../Boat/src/Boat.php';
require_once __DIR__ . '/../../Season/src/Season.php';

    class Fleet {

        public $boats = [];

        public function __construct() {
        /*
        Instantiate the fleet object with the contents of the fleet database.
        */
            $this->load();

        }

        public function contains( $_boat_key ): bool {

        /*
        Returns true if the boat with the supplied key is in the fleet.  Otherwise, return false.
        */

            foreach ( $this->boats as $_boat ) {
                if ( $_boat->get_key() === $_boat_key ) {
                    return true;
                }
            }
            return false;
        }

        public function get_boat( $_boat_key ) : ?boat\Boat{

        /*
        If the boat is in the fleet, return the boat object.  Otherwise return null.
        */

            for ( $i = 0; $i < count( $this->boats ); $i++ ) {
                if ( $this->boats[ $i ]->key === $_boat_key ) {
                    return $this->boats[ $i ];
                }
            }
            return null;
        }

        public function set_boat( boat\Boat $_boat ) : void {

        /*
        If the boat object is in the fleet, replace it.  Otherwise append it.
        */

            for ( $i = 0; $i < count( $this->boats ); $i++ ) {
                if ( $this->boats[ $i ]->key === $_boat->get_key() ) {
                    $this->boats[ $i ] = $_boat;
                    return;
                }
            }
            $this->boats[] = $_boat;
            return;
        }

        function get_available( $_event_id ) : ?array {

            /*
            Return the list of boat objects with available berths on the event day.
            */

            $_available_boats = [];
            foreach( $this->boats as $_boat ) {
                $_berths = $_boat->get_berths( $_event_id );
                if ( $_berths !== '0' ) {
                    $_available_boats[] = $_boat;
                }
            }
            return $_available_boats;
        }

        public function update_absence_rank( $_boats ) {
            foreach( $_boats as $_boat ) {
                $_boat->update_absence_rank();
            }
        }

        public function update_history( $_event_id, $_flotilla ) {

            foreach( $this->boats as $_boat ) {
                $_boat->history[ $_event_id ] = '';
                foreach( $_flotilla[ 'crewed_boats' ] as $_crewed_boat ) {
                    if ( $_crewed_boat[ 'boat' ]->key === $_boat->key ) {
                        $_boat->history[ $_event_id ] = 'Y';
                        break;
                    }
                }
            }
        }

        public function load() : bool {

            /*
            Build the squad object as a list of crew objects from the contents of the CSV files.
            */

            $f = fopen( __DIR__ . "/../docs/debug.txt", "w" );

            $_property_names = array_keys(get_class_vars('nsc\sdc\boat\Boat'));

            $_filename = __DIR__ . '/../data/fleet_data.csv';
            $_handle = fopen( $_filename, "r" );

            if ( !$_handle) {
                $_handle = fopen( $_filename, "w" );
                fputcsv( $_handle, $_property_names, ',', '"', '\\');
                fclose( $_handle );
                $_handle = fopen( $_filename, "r" );
            }

            $_header = fgetcsv($_handle, 0, ',','"', '\\');

            fwrite( $f, print_r($_property_names, true) . "\n" );
            fwrite( $f, print_r($_header, true) . "\n" );
            fclose( $f );

            if( $_header !== $_property_names ) {
                fclose($_handle);
                return false;
            }

            season\Season::load_season_data();
            $_event_ids = season\Season::get_event_ids();

            while (($_property_values = fgetcsv($_handle, 0, ',','"', '\\')) !== false) {

                $_boat = new boat\Boat();

                for( $i = 0; $i < count( $_property_names ); $i++ ) {

                    $_property_name = $_property_names[ $i ];
                    if ( is_array( $_boat->$_property_name )) {
                        $_ex_property_name = explode( ';', $_property_values[ $i ]);                        
                        $_boat->$_property_name = explode( ';', $_property_values[ $i ]);    

                        if ( $_property_names[ $i ] === 'berths' || $_property_names[ $i ] === 'history' ) {
                            $_boat->$_property_name = array_combine( $_event_ids, $_ex_property_name);
                        }
                        else { // must be rank
                            $_boat->$_property_name = $_ex_property_name;
                        }
                    }

                    else {
                        $_boat->$_property_name = $_property_values[ $i ];
                    }
                }
                $this->boats[] = $_boat;
            }            
            fclose($_handle);

            return true;
        }

        public function save(): void {

            /*
            Write the fleet object to the CSV files.
            */

            $_property_names = array_keys(get_class_vars('nsc\sdc\boat\Boat'));

            $_filename = __DIR__ . '/../data/fleet_data.csv';
            $_handle = fopen( $_filename, "w" );
            fputcsv( $_handle, $_property_names, ',', '"', '\\');

            foreach( $this->boats as $_boat ) {
                $_ex_property_values = [];
                $_property_values = array_values( get_object_vars( $_boat ));
                foreach( $_property_values as $_property_value ) {
                    if ( is_array( $_property_value )) {
                        $_ex_property_value = implode(';', $_property_value );
                        $_ex_property_values[] = $_ex_property_value;
                    }
                    else {
                        $_ex_property_values[] = $_property_value;
                    }
                }
                fputcsv( $_handle, $_ex_property_values, ',', '"', '\\' );
            } 
            fclose($_handle);
            return;
        }

    }

?>

