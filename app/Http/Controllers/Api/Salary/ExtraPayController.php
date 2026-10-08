<?php

namespace App\Http\Controllers\Api\Salary;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreExtraPayRequest;
use App\Http\Requests\UpdateExtraPayRequest;
use App\Models\ExtraPay;
use App\Services\ExtraPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtraPayController extends ApiController
{
    public function index(Request $request, ExtraPayService $extraPay): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'staff_id' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:100'],
        ]);
        $page = $extraPay->paginate($filters);

        return $this->paginated($page, \App\Http\Resources\ExtraPayResource::class);
    }

    public function store(StoreExtraPayRequest $request, ExtraPayService $extraPay): JsonResponse
    {
        return $this->success($this->payload($extraPay->create($request->validated(), $request->user())), 'Extra pay created', 201);
    }

    public function show(ExtraPay $extraPay): JsonResponse
    {
        $extraPay->load('staff:id,full_name,staff_code');

        return $this->success($this->payload($extraPay), 'Extra pay loaded');
    }

    public function update(UpdateExtraPayRequest $request, ExtraPay $extraPay, ExtraPayService $service): JsonResponse
    {
        return $this->success($this->payload($service->update($extraPay, $request->validated(), $request->user())), 'Extra pay updated');
    }

    public function destroy(Request $request, ExtraPay $extraPay, ExtraPayService $service): JsonResponse
    {
        $service->delete($extraPay, $request->user());

        return $this->success(null, 'Extra pay deleted');
    }

    private function payload(ExtraPay $record): array
    {
        return [
            'id' => $record->id,
            'staff_id' => $record->staff_id,
            'staff' => $record->relationLoaded('staff') && $record->staff ? [
                'id' => $record->staff->id,
                'full_name' => $record->staff->full_name,
                'staff_code' => $record->staff->staff_code,
            ] : null,
            'date' => $record->date?->toDateString(),
            'amount' => $record->amount,
            'reason' => $record->reason,
            'notes' => $record->notes,
        ];
    }
}
