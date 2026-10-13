<?php

namespace App\Domain\Procurement\Enums;

enum EligibilityCriterionResult: string
{
    case Pending = 'pending';
    case Compliant = 'compliant';
    case NonCompliant = 'non_compliant';
    case NotApplicable = 'not_applicable';
}
