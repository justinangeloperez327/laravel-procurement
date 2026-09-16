<?php

namespace App\Domain\Planning\Enums;

enum PlanningWorkflowAction: string
{
    case Submitted = 'submitted';
    case ReviewStarted = 'review_started';
    case Recommended = 'recommended';
    case Approved = 'approved';
    case Returned = 'returned';
}
