A crew's rank is a tensor with shape {3,2,2,e} (where e is the number of events in the season).
Dimension 0 indicates commitment.  No-shows are assigned the value 0.  Those who sign-up for an event more than five days in advance are assigned the value 2.  All others are assigned the value 1.
Dimension 1 indicates whether the crew is flexible (0) or inflexible (1).  Flexible crew are also registered as a boat owner.  Inflexible crew get priority over flexible ones.
Dimension 2 indicates whether the crew is a non-member (0) or a member (1).  Members get priority over non-members.
Dimension 3 indicates absence, i.e. the number of events that the crew has missed.  Absent crews are given higher priority than frequent ones.
Rank is used to set crew priority.  Where two or more crew members have the same rank, they are ordered randomly.

Ex 1. [1,1,0,0] The crew did not sign-up early and has not been a no-show.  They have not registered as a boat owner.  They are a non-member.  And they have missed none of the past events.