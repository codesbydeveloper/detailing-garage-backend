<?php

namespace App\Http\Controllers\Api\Settings;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\WorkType;
use App\Services\ActivityLogService;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkTypeController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkType::query()->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->success($query->get(), 'Work types loaded');
    }

    public function store(Request $request, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:work_types,name'],
            'description' => ['nullable', 'string'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(RecordStatus::values())],
        ]);

        $workType = WorkType::query()->create([
            ...$data,
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $activity->log('WORK_TYPE_CREATED', 'settings', 'Work type created', $workType, null, $activity->snapshot($workType), 'success', $request->user());

        return $this->success($workType, 'Work type created', 201);
    }

    public function show(WorkType $workType): JsonResponse
    {
        return $this->success($workType, 'Work type loaded');
    }

    public function update(Request $request, WorkType $workType, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('work_types', 'name')->ignore($workType->id)],
            'description' => ['nullable', 'string'],
            'default_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(RecordStatus::values())],
        ]);
        $workType->fill($data);
        $workType->updated_by = $request->user()->id;
        $workType->save();
        [$old, $new] = $activity->changed($workType);
        $activity->log('WORK_TYPE_UPDATED', 'settings', 'Work type updated', $workType, $old, $new, 'success', $request->user());

        return $this->success($workType, 'Work type updated');
    }

    public function destroy(Request $request, WorkType $workType, CatalogService $catalog): JsonResponse
    {
        $result = DB::transaction(fn () => $catalog->remove(
            $workType,
            'jobs',
            'settings',
            'WORK_TYPE_UPDATED',
            'WORK_TYPE_DELETED',
            'Work type',
            $request->user(),
        ));

        return $this->success(null, $result === 'deactivated' ? 'Work type deactivated' : 'Work type deleted');
    }
}
