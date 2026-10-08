<?php

namespace App\Http\Controllers\Api\Vendors;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreVendorPaymentRequest;
use App\Http\Requests\UpdateVendorPaymentRequest;
use App\Models\VendorPayment;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorPaymentController extends ApiController
{
    public function index(Request $request, VendorService $vendors): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'vendor_id' => ['nullable', 'integer'],
        ]);

        return $this->paginated($vendors->paginatePayments($filters), \App\Http\Resources\VendorPaymentResource::class);
    }

    public function store(StoreVendorPaymentRequest $request, VendorService $vendors): JsonResponse
    {
        return $this->success($this->payload($vendors->createPayment($request->validated(), $request->user())), 'Vendor payment recorded', 201);
    }

    public function show(VendorPayment $vendorPayment): JsonResponse
    {
        $vendorPayment->load('vendor');

        return $this->success($this->payload($vendorPayment), 'Vendor payment loaded');
    }

    public function update(UpdateVendorPaymentRequest $request, VendorPayment $vendorPayment, VendorService $vendors): JsonResponse
    {
        return $this->success($this->payload($vendors->updatePayment($vendorPayment, $request->validated(), $request->user())), 'Vendor payment updated');
    }

    public function destroy(Request $request, VendorPayment $vendorPayment, VendorService $vendors): JsonResponse
    {
        $vendors->deletePayment($vendorPayment, $request->user());

        return $this->success(null, 'Vendor payment deleted');
    }

    private function payload(VendorPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'vendor_id' => $payment->vendor_id,
            'vendor' => $payment->relationLoaded('vendor') && $payment->vendor ? [
                'id' => $payment->vendor->id,
                'name' => $payment->vendor->name,
                'vendor_code' => $payment->vendor->vendor_code,
            ] : null,
            'date' => $payment->date?->toDateString(),
            'amount' => $payment->amount,
            'purpose' => $payment->purpose,
            'payment_method' => $payment->payment_method,
            'reference' => $payment->reference,
            'notes' => $payment->notes,
        ];
    }
}
