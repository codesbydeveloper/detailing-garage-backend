<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'logo',
        'phone',
        'email',
        'gst_number',
        'address',
        'currency',
        'currency_symbol',
        'timezone',
    ];
}
