<?php

namespace App\Domain\Planning\Enums;

enum ProcurementCategory: string
{
    case Goods = 'goods';
    case Infrastructure = 'infrastructure';
    case ConsultingServices = 'consulting_services';
}
