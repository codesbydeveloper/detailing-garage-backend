<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ExtraPayReason: string
{
    use HasValues;

    case Overtime = 'Overtime';
    case ExtraWork = 'Extra Work';
    case Incentive = 'Incentive';
    case Bonus = 'Bonus';
    case HolidayWork = 'Holiday Work';
    case PerformanceBonus = 'Performance Bonus';
    case Other = 'Other';
}
