<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PaymentStatus: string
{
    use HasValues;

    case Paid = 'paid';
    case Partial = 'partial';
    case Pending = 'pending';
}
