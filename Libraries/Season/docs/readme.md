namespace nsc\sdc\season;

Private properties:

$event_ids - A list of strings reflecting the dates of the season's events (e.g. "Fri May 29").

$time - Either current unix time or simulated unix time.

$year - Assumed year for use with an event id (which does not include a year).

$start_time - Time of day at which an event starts.

$flotillas - Associative array containing a flotilla for each future event.

$config_data - json file containing configuration data.

Functions

__construct()

get_event_count() - Returns the numnber of events in the season.

get_event_ids() - Returns a list of strings.  Each string is an event id (e.g. Fri May 29).

get_time() - Return the unix time in use.

set_time() - Set the unix time inuse.  Overrides the current unix time.

get_event_time( string $_event_id ) - Returns the unix time corresponing to the event id using $year.

get_past_events( ) : ?array - Returns a list of ids of events whose dates are earlier than $time.

get_future_events( ) : ?array - Returns the ids of events whose dates are later than $time.

get_next_event( ) : ?string - Returns the id of the earliest event whose date is later than $time.

get_last_event( ) : ?string - Returns the id of the latest event whose date is earlier thAn $time.

get_first_time() - Return the unix time of the first event of the season using $year and $start_time.

get_final_time() - Return the unix time of the last event of the season with $year and $start_time.

get_flotilla( string $_event_id ) : ?array - Returns a list of flotilla objects.
