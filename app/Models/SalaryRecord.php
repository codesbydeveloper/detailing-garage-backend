<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\SalaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    /** @use HasFactory<SalaryFactory> */
    use HasAuditUsers, HasFactory;

    protected static function newFactory(): SalaryFactory
    {
        return SalaryFactory::new();
    }

    protected $fillable = [
        'staff_id',
        'year',
        'month',
        'basic_salary',
        'extra_pay',
        'bonus',
        'deduction',
        'advance',
        'final_payable',
        'status',
        'paid_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'basic_salary' => 'decimal:2',
            'extra_pay' => 'decimal:2',
            'bonus' => 'decimal:2',
            'deduction' => 'decimal:2',
            'advance' => 'decimal:2',
            'final_payable' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class)->withTrashed();
    }
}
