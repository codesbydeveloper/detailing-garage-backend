<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SalaryType: string
{
    use HasValues;

    case Monthly = 'monthly';
    case Daily = 'daily';
    case Hourly = 'hourly';
}
