<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadStatus extends Model
{
    use HasAuditUsers, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'is_active',
        'is_converted',
        'is_lost',
        'is_new',
        'order',
    ];

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsConvertedAttribute(): bool
    {
        return $this->slug === 'converted';
    }

    public function getIsLostAttribute(): bool
    {
        return $this->slug === 'lost';
    }

    public function getIsNewAttribute(): bool
    {
        return $this->slug === 'new';
    }

    public function getOrderAttribute(): int
    {
        return (int) $this->sort_order;
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
