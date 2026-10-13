<?php

namespace App\Domain\Procurement\Enums;

enum EligibilityEvaluationStatus: string
{
    case Pending = 'pending';
    case Eligible = 'eligible';
    case Ineligible = 'ineligible';
}
