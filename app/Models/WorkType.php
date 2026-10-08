<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkType extends Model
{
    use HasActiveFlag, HasAuditUsers, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'default_price',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
        ];
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
