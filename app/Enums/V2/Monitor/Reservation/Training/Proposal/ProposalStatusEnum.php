<?php

namespace App\Enums\V2\Monitor\Reservation\Training\Proposal;

enum ProposalStatusEnum: int
{
    case PENDING = 1;
        // case ACTIVE = 2;
    case CANCELLED = 2;
    case RESERVER = 3;
}
