<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum UserRole: string
{
    use HasValues;

    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';
}
