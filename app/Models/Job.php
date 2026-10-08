<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasAuditUsers, HasFactory, SoftDeletes;

    protected $fillable = [
        'job_number',
        'date',
        'delivery_date',
        'car_name',
        'car_number',
        'customer_name',
        'customer_mobile',
        'customer_email',
        'work_type_id',
        'income',
        'amount_paid',
        'product_charge',
        'labour_charge',
        'pending_pay',
        'discount',
        'final_amount',
        'profit',
        'payment_status',
        'job_status',
        'remark',
        'lead_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'delivery_date' => 'date',
            'income' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'product_charge' => 'decimal:2',
            'labour_charge' => 'decimal:2',
            'pending_pay' => 'decimal:2',
            'discount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    public function workType(): BelongsTo
    {
        return $this->belongsTo(WorkType::class)->withTrashed();
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(JobPayment::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $like = \App\Support\Query::like($term);

        if ($like === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('job_number', 'like', $like)
                ->orWhere('car_name', 'like', $like)
                ->orWhere('car_number', 'like', $like)
                ->orWhere('customer_name', 'like', $like)
                ->orWhere('customer_mobile', 'like', $like)
                ->orWhere('customer_email', 'like', $like);
        });
    }
}
