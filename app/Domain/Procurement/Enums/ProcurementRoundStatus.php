<?php

namespace App\Domain\Procurement\Enums;

enum ProcurementRoundStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Posted = 'posted';
    case Bidding = 'bidding';
    case Evaluation = 'evaluation';
    case PostQualification = 'post_qualification';
    case Failed = 'failed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
