<?php

namespace App\Domain\Procurement\Enums;

enum BidderParticipationStatus: string
{
    case Registered = 'registered';
    case Withdrawn = 'withdrawn';
}
