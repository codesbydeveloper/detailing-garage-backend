<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PreferredFinish: string
{
    use HasValues;

    case Gloss = 'Gloss';
    case HighGloss = 'High Gloss';
    case Matte = 'Matte';
    case Satin = 'Satin';
    case FactoryFinish = 'Factory Finish';
}
