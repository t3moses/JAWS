A boat's rank is a tensor with shape {2,e} (where e is the number of events in the season).
Dimension 0 indicates whether the boat is flexible (0) or inflexible (1).  The owners of flexible boats are also registered as crew.  Inflexible boats take priority ov flexible ones.
Dimension 1 indicates absence, i.e. the number of events that the boat has missed.  Absent boats take priority over frequent ones.

Ex.1 [0,2] The skipper is also registered as a crew member and the boat has missed two of the past events.
Ex.2 [1,1] The skipper is not registered as a crew member and the boat has only missed one oast event.
Ex.2 takes priority of Ex.1.
The flexibility rank component is set during registration.  It needs persistent storage.
The absence rank compnent is set during registration.  It is updated by the Selection instance every time an event flotilla is calculated for which the boat is available.