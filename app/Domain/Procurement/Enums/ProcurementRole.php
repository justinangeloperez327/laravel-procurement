<?php

namespace App\Domain\Procurement\Enums;

enum ProcurementRole: string
{
    case EndUserHead = 'end_user_head';
    case PlanningReviewer = 'planning_reviewer';
    case BacSecretariat = 'bac_secretariat';
    case BacChairperson = 'bac_chairperson';
    case Hope = 'hope';
}
