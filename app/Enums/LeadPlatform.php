<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum LeadPlatform: string
{
    use HasValues;

    case Facebook = 'Facebook';
    case Instagram = 'Instagram';
    case Google = 'Google';
    case Website = 'Website';
    case WhatsApp = 'WhatsApp';
    case Organic = 'Organic';
}
