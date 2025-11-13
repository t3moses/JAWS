<?php

return [
    'file' => [
        'boats_data_file' => './config/boats_data.csv',
        'boats_availability_file' => './config/boats_availability.csv',
        'crews_data_file' => './config/crews_data.csv',
        'crews_availability_file' => './config/crews_availability.csv',
        'crews_history_file' => './config/crews_history.csv',
        'calendar_file' => './config/calendar.html',
        'debug_file' => './config/debug.txt',
        'addresses_file' => './config/addresses.txt',
        'crew_info_file' => './config/crew_info.txt',
        'tickets_file' => './config/tickets.txt',
    ],
    'event_id' => ['Fri May 29','Fri Jun 5','Fri Jun 12','Fri Jun 19','Sat Jun 27','Fri Jul 3','Fri Jul 10','Sat Jul 18','Fri Jul 24','Fri Jul 31','Sat Aug 8','Fri Aug 14','Fri Aug 21','Sat Aug 29','Fri Sep 4','Fri Sep 11','Fri Sep 18','Fri Sep 25'],
    'weight' => [
        'assist' => '16', // Non-compliance weights for each rule
        'whitelist' => '8',
        'skill_spread' => '4',
        'partner' => '2',
        'repeat' => '1'
    ],
    'default_boat' => [
        'key' => '',
        'owner_first_name' => '',
        'owner_last_name' => '',
        'display_name' => '',
        'email_address' => '',
        'mobile_number' => '',
        'min_occupancy' => '1',
        'max_occupancy' => '1',
        'assistance' => 'No'
    ],
    'default_crew' => [
        'key' => '',
        'first_name' => '',
        'last_name' => '',
        'partner_key' => '',
        'email_address' => '',
        'membership_number' => '',
        'skill' => '0',
        'experience' => '',
        'whitelist' => []
    ],
    'epochs' => [
        'local' => 6, // max iterations for local minimum search
        'global' => 8 // ditto for global
    ],
    'cut_off' => 6 # Days prior to the event during which crew positions may be guaranteed.
];

?>
