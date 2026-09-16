<?php

namespace App\Enums;

enum PlanningStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
