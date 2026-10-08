<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasAuditUsers, HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'staff_code',
        'user_id',
        'full_name',
        'profile_photo',
        'phone',
        'email',
        'address',
        'date_of_birth',
        'joining_date',
        'emergency_contact',
        'emergency_contact_number',
        'staff_category_id',
        'role',
        'department',
        'salary_type',
        'basic_salary',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'basic_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StaffCategory::class, 'staff_category_id')->withTrashed();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaryRecords(): HasMany
    {
        return $this->hasMany(SalaryRecord::class);
    }

    public function extraPays(): HasMany
    {
        return $this->hasMany(ExtraPay::class);
    }

    public function personalExpenses(): HasMany
    {
        return $this->hasMany(PersonalExpense::class);
    }
}
