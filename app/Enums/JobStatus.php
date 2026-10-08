<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum JobStatus: string
{
    use HasValues;

    case Booked = 'booked';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
