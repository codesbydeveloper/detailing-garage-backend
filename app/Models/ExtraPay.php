<?php

namespace App\Models;

use Database\Factories\ExtraPayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraPay extends Model
{
    /** @use HasFactory<ExtraPayFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'extra_pay';

    protected $fillable = [
        'staff_id',
        'date',
        'amount',
        'reason',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
