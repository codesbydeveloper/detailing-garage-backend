<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum StaffStatus: string
{
    use HasValues;

    case Active = 'active';
    case Inactive = 'inactive';
    case OnLeave = 'on_leave';
}
