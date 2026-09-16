<?php

namespace App\Domain\Procurement\Enums;

enum ProcurementStatus: string
{
    case Planned = 'planned';
    case Preparation = 'preparation';
    case Posting = 'posting';
    case Bidding = 'bidding';
    case Evaluation = 'evaluation';
    case PostQualification = 'post_qualification';
    case ForAward = 'for_award';
    case Awarded = 'awarded';
    case Contracting = 'contracting';
    case Implementation = 'implementation';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
