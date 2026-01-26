Crew data comprises:
key - derived from first name, last name
display_name - derived from first name, last name
first_name
last_name
partner_key
email
membership_number,
rank
    commitment - derived from available
    flexible - derived from key and fleet during registration
    membership - derived from membership number during registration
    absence - derived from history
skill
experience,
available,
history,
whitelist

Underived variables have set and get functions.
Derived variables additionaly have update functions, which (in turn) call the correspoonding set functions.


A crew's rank is a tensor with shape {3,2,2,e} (where e is the number of events in the season).
Dimension 0 indicates commitment.  No-shows are assigned the value 0.  Those who sign-up for an event more than five days in advance are assigned the value 2.  All others are assigned the value 1.
Dimension 1 indicates whether the crew is flexible (0) or inflexible (1).  Flexible crew are also registered as a boat owner.  Inflexible crew get priority over flexible ones.
Dimension 2 indicates whether the crew is a non-member (0) or a member (1).  Members get priority over non-members.
Dimension 3 indicates absence, i.e. the number of events that the crew has missed.  Infrequent crews are given higher priority than frequent ones.
Rank is used to set crew priority.  Where two or more crew members have the same rank, they are ordered randomly.
Ex 1. [1,1,0,0] The crew did not sign-up early and has not been a no-show.  They have not registered as a boat owner.  They are a non-member.  And they have missed none of the past events.
The commitment rank component is set to 1 during registration.  The admin can set it to 0.  It can be set to 2 during crew availability update.  This needs persistent storage.
The flexibility amd membership rank components are set during registration.  These both need persistent storage.
The absence rank compnent is set during registration.  It is updated by the Selection instance every time an event flotilla is calculated for which the crew is available.
