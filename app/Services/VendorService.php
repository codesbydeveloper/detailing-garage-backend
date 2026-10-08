<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Support\Money;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorService
{
    public function __construct(
        private ActivityLogService $activity,
        private NumberGenerator $numbers,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Vendor::query();
        $query->search($filters['search'] ?? null);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'name', 'vendor_code', 'status', 'created_at'], 'name');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function create(array $data, User $actor): Vendor
    {
        return DB::transaction(function () use ($data, $actor) {
            $vendor = Vendor::query()->create([
                'vendor_code' => $this->numbers->sequence(Vendor::class, 'vendor_code', 'VND'),
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'company' => $data['company'] ?? null,
                'address' => $data['address'] ?? null,
                'category' => $data['category'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->activity->log('VENDOR_CREATED', 'vendors', 'Vendor created', $vendor, null, $this->activity->snapshot($vendor), 'success', $actor);

            return $vendor;
        });
    }

    public function update(Vendor $vendor, array $data, User $actor): Vendor
    {
        $vendor->fill(collect($data)->only([
            'name', 'phone', 'email', 'company', 'address', 'category', 'notes', 'status',
        ])->all());
        $vendor->updated_by = $actor->id;
        $vendor->save();
        [$old, $new] = $this->activity->changed($vendor);
        $this->activity->log('VENDOR_UPDATED', 'vendors', 'Vendor updated', $vendor, $old, $new, 'success', $actor);

        return $vendor;
    }

    public function delete(Vendor $vendor, User $actor): string
    {
        if ($vendor->payments()->withTrashed()->exists()) {
            $old = ['status' => $vendor->status];
            $vendor->update(['status' => 'inactive', 'updated_by' => $actor->id]);
            $this->activity->log('VENDOR_UPDATED', 'vendors', 'Vendor deactivated because payments exist', $vendor, $old, ['status' => 'inactive'], 'success', $actor);

            return 'deactivated';
        }

        $snapshot = $this->activity->snapshot($vendor);
        $vendor->delete();
        $this->activity->log('VENDOR_DELETED', 'vendors', 'Vendor deleted', $vendor, $snapshot, null, 'success', $actor);

        return 'deleted';
    }

    public function wasDeactivated(Vendor $vendor): bool
    {
        return $vendor->status === 'inactive' && $vendor->payments()->withTrashed()->exists();
    }

    public function paginatePayments(array $filters): LengthAwarePaginator
    {
        $query = VendorPayment::query()->with('vendor:id,name,vendor_code');

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if ($like = Query::like($filters['search'] ?? null)) {
            $query->where(function ($inner) use ($like) {
                $inner->where('purpose', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', $like));
            });
        }

        Query::whereDateRange($query, 'date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'date', 'amount', 'created_at'], 'date');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function createPayment(array $data, User $actor): VendorPayment
    {
        return DB::transaction(function () use ($data, $actor) {
            $payment = VendorPayment::query()->create([
                'vendor_id' => $data['vendor_id'],
                'date' => $data['date'],
                'amount' => Money::of($data['amount']),
                'purpose' => $data['purpose'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $payment->load('vendor');
            $this->activity->log('VENDOR_PAYMENT_CREATED', 'vendors', 'Vendor payment recorded', $payment, null, $this->activity->snapshot($payment), 'success', $actor);

            return $payment;
        });
    }

    public function updatePayment(VendorPayment $payment, array $data, User $actor): VendorPayment
    {
        $payment->fill([
            'vendor_id' => $data['vendor_id'] ?? $payment->vendor_id,
            'date' => $data['date'] ?? $payment->date,
            'amount' => array_key_exists('amount', $data) ? Money::of($data['amount']) : $payment->amount,
            'purpose' => $data['purpose'] ?? $payment->purpose,
            'payment_method' => array_key_exists('payment_method', $data) ? $data['payment_method'] : $payment->payment_method,
            'reference' => array_key_exists('reference', $data) ? $data['reference'] : $payment->reference,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $payment->notes,
            'updated_by' => $actor->id,
        ]);
        $payment->save();
        [$old, $new] = $this->activity->changed($payment);
        $this->activity->log('VENDOR_PAYMENT_UPDATED', 'vendors', 'Vendor payment updated', $payment, $old, $new, 'success', $actor);

        return $payment->load('vendor');
    }

    public function deletePayment(VendorPayment $payment, User $actor): void
    {
        $snapshot = $this->activity->snapshot($payment);
        $payment->delete();
        $this->activity->log('VENDOR_PAYMENT_DELETED', 'vendors', 'Vendor payment deleted', $payment, $snapshot, null, 'success', $actor);
    }
}
