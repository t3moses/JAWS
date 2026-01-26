Each crew has a Loss and a Grad parameter.  These are tensors that reflect (respectively) the extent to which the crew violates each rule, and the extent to which it can mitigate the violation.

The objective is to minimize violations by swapping crews that violate a rule with those that address the violation.  The rules are listed in decreasing order of significance.

The dimensions of loss are determined by:

0: Assistance.  Does the crew satisfy the boat’s need for assistance?
1: Whitelist.  Does the crew list the boat on its whitelist?
2. High-skill.  Do the crews’ have a wide skill spread with the crew at the high end?
3. Low-skill.  Do the crews’ have a wide skill spread with the crew at the low end?
4: Partner.  Is the crew’s partner assigned to the same boat?
5: Repeat.  Has the crew been assigned to the same boat many times?

The dimensions of grad are determined by:

0: Assistance.  The crew's skill level.
1: Whitelist.  The number of boats on the crew’s whitelist; the more boats on the whitelist, the less likely this crew member will cause a whitelist violation.
2: High-skill.  Inverse relationship to skill level.
3: Low-skill.   Proportional relationship to skill level.
4: Partner.  Whether or not the crew has a partner in the program.
5: Repeat.  The number of the crew's absences; the more absences, the less likely the crew member will cause a repeat.)

Once a rule has been satisfied by a swap, the crew that satisfied the rule cannot be subsequently swapped.

