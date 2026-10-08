<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasAuditUsers, HasFactory, SoftDeletes;

    protected $fillable = [
        'created_time',
        'ad_id',
        'ad_name',
        'adset_id',
        'adset_name',
        'campaign_id',
        'campaign_name',
        'form_id',
        'form_name',
        'is_organic',
        'platform',
        'service_interested',
        'car_condition',
        'preferred_finish',
        'planned_service_date',
        'car_model',
        'car_colour',
        'full_name',
        'phone_number',
        'email',
        'lead_status_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'created_time' => 'datetime',
            'is_organic' => 'boolean',
            'planned_service_date' => 'date',
        ];
    }

    public function leadStatus(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class)->withTrashed();
    }

    public function leadNotes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function notesRelation(): HasMany
    {
        return $this->leadNotes();
    }

    public function followups(): HasMany
    {
        return $this->hasMany(LeadFollowup::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $like = \App\Support\Query::like($term);

        if ($like === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('full_name', 'like', $like)
                ->orWhere('phone_number', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('car_model', 'like', $like)
                ->orWhere('campaign_name', 'like', $like);
        });
    }
}
