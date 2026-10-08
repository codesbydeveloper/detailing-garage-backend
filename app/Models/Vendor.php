<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasAuditUsers;
use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasActiveFlag, HasAuditUsers, HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_code',
        'name',
        'phone',
        'email',
        'company',
        'address',
        'category',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $like = \App\Support\Query::like($term);

        if ($like === null) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('name', 'like', $like)
                ->orWhere('vendor_code', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('company', 'like', $like)
                ->orWhere('category', 'like', $like);
        });
    }
}
