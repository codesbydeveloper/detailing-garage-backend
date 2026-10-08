<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SalaryStatus: string
{
    use HasValues;

    case Draft = 'draft';
    case Generated = 'generated';
    case Paid = 'paid';
}
