<?php

namespace nsc\sdc\squad;

use nsc\sdc\crew as crew;
use nsc\sdc\season as season;

require_once __DIR__ . '/../../Csv/src/Csv.php';
require_once __DIR__ . '/../../Crew/src/Crew.php';
require_once __DIR__ . '/../../Season/src/Season.php';

    class Squad {

        public $crews = [];

        public function __construct() {

        /*
        Instantiate the squad object with the contents of the squad database.
        */
            $this->load();

        }

        public function contains( $_crew_key ): bool {

        /*
        Returns true if the crew with the supplied key is in the squad.  Otherwise, return false.
        */

            foreach ( $this->crews as $_crew ) {
                if ( $_crew->key === $_crew_key ) {
                    return true;
                }
            }
            return false;
        }

        public function get_crew( $_crew_key ) : ?crew\Crew {

        /*
        If the crew is in the squad, return it.  Otherwise return null.
        */

            for ( $i = 0; $i < count( $this->crews ); $i++ ) {
                if ( $this->crews[ $i ]->key === $_crew_key ) {
                    return $this->crews[ $i ];
                }
            }
            return null;

        }

        public function set_crew( $_crew ) {

        /*
        If the crew is in the squad, replace it.  Otherwise append it.
        */

            for ( $i = 0; $i < count( $this->crews ); $i++ ) {
                if ( $this->crews[ $i ]->key === $_crew->get_key() ) {
                    $this->crews[ $i ] = $_crew;
                    return;
                }
            }
            $this->crews[] = $_crew;
            return;
            
        }

        function get_available( $_event_id ) : ?array {

            /*
            Return the list of crews available on the event day.
            */

            $_available_crews = [];
            foreach( $this->crews as $_crew ) {
                $_available_crew = $_crew->available[ $_event_id ];
                if ( $_available_crew !== '0' ) {
                    $_available_crews[] = $_crew;
                }
            }

            return $_available_crews;
        }

        public function update_absence_rank( $_crews ) {
            foreach ( $_crews as $_crew ) {
                $_crew->update_absence_rank();
            }

        }

        public function update_commitment_rank( $_crews, $_event_id ) {
            foreach ( $_crews as $_crew ) {
                $_crew->update_commitment_rank( $_event_id );
            }
        }

        public function update_availability( $_crews, $_commitment ) {
            foreach( $_crews as $_crew ) {
                $_crew->update_availability( $_commitment );
            }
        }

        public function update_history( $_event_id, $_flotilla ) {

        // Traverse the squad array.  Set all values for the event_id to '',
        // except any in flotilla, which are set to 'Y'.

            foreach( $this->crews as $_squad_crew ) {
                $_squad_crew->history[ $_event_id ] = '';
                foreach( $_flotilla[ 'crewed_boats' ] as $_crewed_boat ) {
                    foreach( $_crewed_boat[ 'crews' ] as $_flotilla_crew ) {
                        if ( $_flotilla_crew->key === $_squad_crew->key ) {
                            $_squad_crew->history[ $_event_id ] = $_crewed_boat[ 'boat' ]->key;
                            break;
                        }
                    }
                }
            }
        }

        private function load() : bool {

            /*
            Build the squad object as a list of crew objects from the contents of the CSV files.
            */

            $_property_names = array_keys(get_class_vars('nsc\sdc\crew\Crew'));

            $_filename = __DIR__ . '/../data/squad_data.csv';
            $_handle = fopen( $_filename, "r" );

            if ( !$_handle) {
                $_handle = fopen( $_filename, "w" );
                fputcsv( $_handle, $_property_names, ',', '"', '\\');
                fclose( $_handle );
                $_handle = fopen( $_filename, "r" );
            }
            $_header = fgetcsv($_handle, 0, ',','"', '\\');

            if( $_header !== $_property_names ) {
                return false;
            }

            season\Season::load_season_data();
            $_event_ids = season\Season::get_event_ids();

            while (($_property_values = fgetcsv($_handle, 0, ',','"', '\\')) !== false) {

                $_crew = new crew\Crew();

                for( $i = 0; $i < count( $_property_names ); $i++ ) {

                    $_property_name = $_property_names[ $i ];

                    if ( is_array( $_crew->$_property_name )) {
                        $_ex_property_name = explode( ';', $_property_values[ $i ]);
                        if ( $_property_names[ $i ] === 'available' || $_property_names[ $i ] === 'history' ) {
                            $_crew->$_property_name = array_combine( $_event_ids, $_ex_property_name);
                        }
                        else {
                            $_crew->$_property_name = $_ex_property_name;
                        }
                    }
                    else {
                        $_crew->$_property_name = $_property_values[ $i ];
                    }
                    
                }
                $this->crews[] = $_crew;
            }            
            fclose($_handle);
            return true;
        }


        public function save(): void {

            /*
            Write the squad object to the CSV files.
            */

            $_property_names = array_keys(get_class_vars('nsc\sdc\crew\Crew'));

            $_filename = __DIR__ . '/../data/squad_data.csv';
            $_handle = fopen( $_filename, "w" );
            fputcsv( $_handle, $_property_names, ',', '"', '\\');

            foreach( $this->crews as $_crew ) {
                $_ex_property_values = [];
                $_property_values = array_values( get_object_vars( $_crew ));
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
/*
        public function __destruct() {
            $this->save();
        }
*/
    }

?>