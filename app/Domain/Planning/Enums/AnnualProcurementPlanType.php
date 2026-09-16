<?php

namespace App\Domain\Planning\Enums;

enum AnnualProcurementPlanType: string
{
    case Indicative = 'indicative';
    case Final = 'final';
    case Updated = 'updated';
}
