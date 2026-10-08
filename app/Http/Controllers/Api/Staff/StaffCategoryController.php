<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreStaffCategoryRequest;
use App\Models\StaffCategory;
use App\Services\ActivityLogService;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffCategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = StaffCategory::query()->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->success($query->get(), 'Staff categories loaded');
    }

    public function store(StoreStaffCategoryRequest $request, ActivityLogService $activity): JsonResponse
    {
        $category = StaffCategory::query()->create([
            ...$request->validated(),
            'status' => $request->input('status', 'active'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $activity->log('STAFF_CATEGORY_CREATED', 'settings', 'Staff category created', $category, null, $activity->snapshot($category), 'success', $request->user());

        return $this->success($category, 'Staff category created', 201);
    }

    public function show(StaffCategory $staffCategory): JsonResponse
    {
        return $this->success($staffCategory, 'Staff category loaded');
    }

    public function update(Request $request, StaffCategory $staffCategory, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('staff_categories', 'name')->ignore($staffCategory->id)],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(RecordStatus::values())],
        ]);
        $staffCategory->fill($data);
        $staffCategory->updated_by = $request->user()->id;
        $staffCategory->save();
        [$old, $new] = $activity->changed($staffCategory);
        $activity->log('STAFF_CATEGORY_UPDATED', 'settings', 'Staff category updated', $staffCategory, $old, $new, 'success', $request->user());

        return $this->success($staffCategory, 'Staff category updated');
    }

    public function destroy(Request $request, StaffCategory $staffCategory, CatalogService $catalog): JsonResponse
    {
        $result = DB::transaction(fn () => $catalog->remove(
            $staffCategory,
            'staff',
            'settings',
            'STAFF_CATEGORY_UPDATED',
            'STAFF_CATEGORY_DELETED',
            'Staff category',
            $request->user(),
        ));

        return $this->success(null, $result === 'deactivated' ? 'Staff category deactivated' : 'Staff category deleted');
    }
}
