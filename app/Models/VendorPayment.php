<?php

namespace App\Models;

use App\Models\Concerns\HasAuditUsers;
use Database\Factories\VendorPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorPayment extends Model
{
    /** @use HasFactory<VendorPaymentFactory> */
    use HasAuditUsers, HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'date',
        'amount',
        'purpose',
        'payment_method',
        'reference',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }
}
