<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\PersonalExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalExpense extends Model
{
    /** @use HasFactory<PersonalExpenseFactory> */
    use HasAuditUsers, HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_number',
        'date',
        'expense',
        'price',
        'staff_id',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'price' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $like = \App\Support\Query::like($term);

        if ($like === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('expense', 'like', $like)
                ->orWhere('expense_number', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('staff', fn (Builder $staff) => $staff->where('full_name', 'like', $like));
        });
    }
}
