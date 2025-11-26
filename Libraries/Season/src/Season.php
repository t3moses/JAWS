<?php

    class Season {

        private array $event_ids = []; // List of event IDs

        public function __construct() {

            $_events = require __DIR__ . '/../data/events.php';
            $this->event_ids = $_events['event_ids'];

        }

        public function get_event_count() : int {

            return count($this->event_ids);

        }

        public function get_event_ids() : array {

            return $this->event_ids;

        }
    }
?>