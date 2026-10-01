<?php

namespace App\Domain\Procurement\Enums;

enum ProcurementActivityStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
