season_update runs folloowing every user input, whether the registration or availability update of a boat or crew.
For each event in the season, season_update.php gets lists of boats and crews that are available and forms them into a flotilla.  It then adds each to the flotillas data structure.
Selection::select provides the lists of selected and waitlist boats amd crew for each event.  Selection is based on the rank of the boat and crew.
Boat and crew rank is managed by the boat and crew classes respectively.
