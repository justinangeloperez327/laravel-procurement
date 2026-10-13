<?php

namespace App\Domain\Procurement\Enums;

enum BidSubmissionStatus: string
{
    case Received = 'received';
    case Opened = 'opened';
}
