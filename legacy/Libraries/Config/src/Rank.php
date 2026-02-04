<?php

namespace nsc\sdc\config\rank;

abstract Class Rank {

    const BOAT_RANK_FLEXIBILITY_DIMENSION = 0;
    const BOAT_RANK_ABSENCE_DIMENSION = 1;
    const CREW_RANK_COMMITMENT_DIMENSION = 0;
    const CREW_RANK_FLEXIBILITY_DIMENSION = 1;
    const CREW_RANK_MEMBERSHIP_DIMENSION = 2;
    const CREW_RANK_ABSENCE_DIMENSION = 3;
    const FLEXIBLE = 0;
    const INFLEXIBLE = 1;
    const NON_MEMBER = 0;
    const MEMBER = 1;
    const UNAVAILABLE = 0; // Commitment values
    const NO_SHOW = 1;
    const AVAILABLE = 2;
    const GUARANTEED = 3;

}

?>