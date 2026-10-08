<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum CarCondition: string
{
    use HasValues;

    case Excellent = 'Excellent';
    case Good = 'Good';
    case Average = 'Average';
    case NeedsCorrection = 'Needs Correction';
    case HeavilyScratched = 'Heavily Scratched';
    case NewVehicle = 'New Vehicle';
}
