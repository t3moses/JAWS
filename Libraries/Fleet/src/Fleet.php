<?php

require_once __DIR__ . '/../../Csv/src/Csv.php';
require_once __DIR__ . '/../../Boat/src/Boat.php';

    class Fleet {

        private $boats = [];

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
                if ( $_boat->key === $_boat_key ) {
                    return true;
                }
            }
            return false;
        }

        public function get_boat( $_boat_key ) : ?Boat{

        /*
        If the boat is in the fleet, return it.  Otherwise return null.
        */

            for ( $i = 0; $i < count( $this->boats ); $i++ ) {
                if ( $this->boats[ $i ]->key === $_boat_key ) {
                    return $this->boats[ $i ];
                }
            }
            return null;
        }

        public function set_boat( Boat $_boat ) : void {

        /*
        If the boat is in the fleet, replace it.  Otherwise append it.
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

        public function load() : void {

            /*
            Build the squad object as a list of crew objects from the contents of the CSV files.
            */

            $_property_names = array_keys(get_class_vars('Boat'));

            $_filename = __DIR__ . '/../data/fleet_data.csv';
            $_handle = fopen( $_filename, "r" );

            if ( !$_handle) {
                $_handle = fopen( $_filename, "w" );
                fputcsv( $_handle, $_property_names, ',', '"', '\\');
                fclose( $_handle );
                $_handle = fopen( $_filename, "r" );
            }
            $_header = fgetcsv($_handle, 0, ',','"', '\\');

            if( $_header !== $_property_names ) {
                die( 'The fleet CSV data file is inconsistent with the fleet class' );
            }

            while (($_property_values = fgetcsv($_handle, 0, ',','"', '\\')) !== false) {

                $_boat = new Boat();

                for( $i = 0; $i < count( $_property_names ); $i++ ) {
                    $_property_name = $_property_names[ $i ];
                    if ( is_array( $_boat->$_property_name )) {
                        $_boat->$_property_name = explode( ';', $_property_values[ $i ]);    
                    }
                    else {
                        $_boat->$_property_name = $_property_values[ $i ];
                    }
                }
                $this->boats[] = $_boat;
            }            
            fclose($_handle);
            return;
        }

        public function save(): void {

            /*
            Write the squad object to the CSV files.
            */

            $_property_names = array_keys(get_class_vars('Boat'));

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
            
        public function __destruct() {
            $this->save();
        }

    }

?>

