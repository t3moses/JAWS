
The rules are listed in decreasing order of importance.

The objective is to minimize loss, placing most emphasis on the most important rules.

Loss dimensions (higher numbers reflect higher loss):

0: Assistance.  The boat's requested skill level minus the crew's skill level. (Note: if the boat's requested skill level is met by another crew, then the swap will take place amongst the crews and the qualifying crew will be locked.)

1: Whitelist.  Equal to 1 if the boat to which the crew is assigned is not on the crew’s whitelist.  Otherwise, 0.

2: Skill.  mod ( crew skill - median skill level ).

3: Partner.  Equal to 1 if the crew’s partner is assigned to the same boat.  Otherwise, 0.

4: Repeat: Equal to the number of times the crew has been assigned to this boat.

Grad dimensions (higher numbers reflect better capability to reduce loss):

0: Assistance.  The crew's skill level.

1: Whitelist.  Equal to the number of boats on the crew’s whitelist.  (Note: the more boats on the whitelist, the less likely this crew member will cause a whitelist violation.)

2: Skill.  Median skill level - mod( crew skill - median skill level ).  (Note: maximum if the crew skill level is equal to the median value.)

3: Partner.  Equal to 1 if the crew does not have a partner in the program.  Otherwise 0.

4: Repeat.  Equal to the number of the crew's absences. (Note: the more absences, the less likely this crew member will cause a repeat.)


For each rule in turn, starting with the most significant one ...

1. The assignment algorithm identifies the crew member with the highest loss

If the highest loss is 0, it jumps to the next rule.

It also orders the crews by grad in descening order.

It identifies the crew member on a different boat with the highest grad.  If its grad is 0, it jumps to the next rule.

Otherwise, it swaps the two crew members.

This pair is then swapped.

