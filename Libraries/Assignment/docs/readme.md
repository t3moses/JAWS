
Selected crew objects have the following resources:

boat: the boat to which the crew is assigned
loss: (5-vector indicating conformance with each of the five rules)
grad: (5-vector indicating influence on conformance with each of the five rules)

The rules are listed in decreasing order of importance.

The objective is to minimize loss, placing most emphasis on the most important rules.

Loss dimensions (higher numbers reflect higher loss):

0: Assistance.  The boat's requested skill level minus the crew's skill level. (Note: if the boat's requested skill level is met by another crew, then the swap will take place amongst the crews and the qualifying crew will be locked.)

1: Whitelist.  Equal to 1 if the boat to which the crew is assigned is not on the crew’s whitelist.  Otherwise, 0.

2: Skill.  mod ( crew skill - median skill level ).

3: Partner.  Equal to 1 if the crew’s partner is assigned to the same boat.  Otherwise, 0.

4: Repeat: Equal to the number of times the crew has been assigned to the this boat.

Grad dimensions (higher numbers reflect better capability to reduce loss):

0: Assistance.  The crew's skill level.

1: Whitelist.  Equal to the number of boats on the crew’s whitelist.

2: Skill.  Median skill level - mod( crew skill - median skill level ) (Note: maximum if the crew skill level is at the median.)

3: Partner.  Equal to 1 if the crew does not have a partner in the program.  Otherwise 0.

4: Repeat.  Equal to the number of the crew's absences. (Note: the more absences, the less likely this crew will cause a repeat.)


Assignment algorithm (called by season_update() : flotilla )

flotilla contains the initial assignment of selected boats and crew for the next event.

for rule[ i = 0 : r-1 ]
  for selected crew[ j = 0 : n-1 ]
    calculate grad for rule i
    calculate initial loss for rule i
  sort grad_list // by decreasing grad
  for selected crew[ j = 0 : n-1 ]
    sort loss_list // by decreasing loss
    crew_s = grad_list[ 0 ] // the crew whose grad under rule i is greatest.
    crew_d = loss_list[ 0 ] // the crew whose loss under rule i is greatest.
    if crew_d->loss > crew_s->loss
      crew_d->boat <-> crew_s->boat
      recalculate crew_s->loss
      recalculate crew_d->loss
      delete crew_s from grad_list
    else break // move on to next rule
