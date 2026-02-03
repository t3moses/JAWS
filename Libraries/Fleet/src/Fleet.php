<?php

namespace nsc\sdc\fleet;

use nsc\sdc\boat as boat;
use nsc\sdc\season as season;

require_once __DIR__ . '/../../Database/src/Database.php';
require_once __DIR__ . '/../../Boat/src/Boat.php';
require_once __DIR__ . '/../../Season/src/Season.php';

    class Fleet {

        public $fleet = [];

        public function __construct() {
        /*
        Instantiate the fleet object with the contents of the fleet database.
        */
            self::load();

        }

        public static function registerBoat($_entity_key, $_display_name, $_owner_key,
                    $_owner_email, $_owner_mobile, $_min_berths, $_max_berths,
                    $_assistance_required, $_social_preference, $_rank,
                    $_occupied_berths, $_berths, $_history) {

            $db = getDatabase();
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO fleet (entity_key, display_name, owner_key,
                    owner_email, owner_mobile, min_berths, max_berths,
                    assistance_required, social_preference, rank,
                    occupied_berths, berths, history) 
                    VALUES (:entity_key, :display_name, :owner_key,
                    :owner_email, :owner_mobile, :min_berths, :max_berths,
                    :assistance_required, :social_preference, :rank,
                    :occupied_berths, :berths, :history)
                ");
                
                $stmt->execute([
                    ':entity_key' => $_entity_key,
                    ':display_name' => $_display_name,
                    ':owner_key' => $_owner_key,
                    ':owner_email' => $_owner_email,
                    ':owner_mobile' => $_owner_mobile,
                    ':min_berths' => $_min_berths,
                    ':max_berths' => $_max_berths,
                    ':assistance_required' => $_assistance_required,
                    ':social_preference' => $_social_preference,
                    ':rank' => $_rank,
                    ':occupied_berths' => $_occupied_berths,
                    ':berths' => $_berths,
                    ':history' => $_history
                ]);
                
                return true;
            } catch (\PDOException $e) {
                die('Insert failed: ' . $e->getMessage());
            }
        }

        public static function crew_as_boat_owner( $_owner_key ) {

            // Return the crew key for the owner of the boat,
            // or false if the owner is not registered as crew.

            $db = getDatabase();
            $stmt = $db->prepare("SELECT entity_key FROM squad WHERE entity_key = :owner_key LIMIT 1");
            $stmt->execute([':owner_key' => $_owner_key]);
            return $stmt->fetchColumn();
        }

        public static function load() : bool {

        // Load the fleet table into the $fleet array of Boat objects.

            $db = getDatabase();
            season\Season::load_season_data();

            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $db->query('SELECT entity_key, display_name, owner_key,
                    owner_email, owner_mobile, min_berths, max_berths,
                    assistance_required, social_preference, rank,
                    occupied_berths, berths, history FROM fleet');

            while ($boat = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $boat[ 'rank' ] = explode( ';', $boat[ 'rank' ]);

                $boat[ 'berths' ] = explode( ';', $boat[ 'berths' ]);
                $_future_event_ids = season\Season::get_future_events();
                $_future_berths = array_slice($boat[ 'berths' ], count($boat[ 'berths' ]) - count($_future_event_ids));
                $boat[ 'berths' ] = array_combine( $_future_event_ids, $_future_berths );

                $boat[ 'history'] = explode( ';', $boat[ 'history']);
                $_past_event_ids = season\Season::get_past_events();
                $_history = array_slice($boat[ 'history' ], count($boat[ 'history' ]) - count($_past_event_ids));
                $boat[ 'berths' ] = array_combine( $_past_event_ids, $_history );

                self::$fleet[] = (new boat\Boat())->hydrate($boat);
            }
            return true;
        }


        function save() : bool {

        // Update the history field of each boat in the fleet table.

            $db = getDatabase();

            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE fleet SET history = :history WHERE key = :key");

            foreach ( self::$fleet as $boat ) {
                $stmt->execute([
                    ':key' => $boat['key'],
                    ':history' => implode( ';', $boat['history'])
                ]);
            }

            $db->commit();

            return true;
        }
    }

?>