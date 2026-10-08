<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasAuditUsers, HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'staff_id',
        'date',
        'clock_in',
        'clock_out',
        'total_minutes',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'total_minutes' => 'integer',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }

    public function minutesBetweenClocks(): ?int
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return null;
        }

        return (int) round($this->clock_in->diffInMinutes($this->clock_out, true));
    }
}
